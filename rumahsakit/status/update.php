<?php 
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

if (!isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php"); 
    exit;
}
$id_petugas = $_SESSION['idpetugas'];

$id_bed = null;
if (isset($_GET['id'])) {
    $id_bed = (int)$_GET['id'];
} else {
    $_SESSION['flash'] = 'error';
    $_SESSION['flash_message'] = 'ID Tempat Tidur tidak ditemukan!';
    header("Location: select.php");
    exit;
}

// Proses form submit
if (isset($_POST['simpan_status'])) {
    $status_baru = $_POST['status_baru'];
    $keterangan = $db->escapeString($_POST['keterangan']);
    $waktu_mulai = date("Y-m-d H:i:s");

    try {
        $db->runSQL("START TRANSACTION");

        // 1. Cek apakah ada pasien aktif (tidak boleh ubah status kalau ada pasien)
        $sql_active_ri = "SELECT f_idrawatinap FROM t_rawatinap 
                          WHERE f_idbed = $id_bed AND f_waktukeluar IS NULL";
        $active_ri_data = $db->getALL($sql_active_ri);
        
        if (!empty($active_ri_data)) {
            $db->runSQL("ROLLBACK");
            $_SESSION['flash'] = 'error';
            $_SESSION['flash_message'] = 'Bed sedang ditempati pasien! Tidak dapat mengubah status.';
            header("Location: select.php");
            exit;
        }

        // 2. Cek status aktif saat ini
        $sql_last_status = "SELECT f_idbedsts, f_sts FROM t_bedstatus 
                            WHERE f_idbed = $id_bed AND f_waktuselesai IS NULL 
                            ORDER BY f_waktumulai DESC LIMIT 1";
        $last_status_data = $db->getALL($sql_last_status);

        // 3. Tutup status lama (jika ada)
        if ($last_status_data) {
            $last_status = $last_status_data[0];
            $sts_lama = $last_status['f_sts'];

            // Cek apakah status sama
            if ($sts_lama == $status_baru) {
                $db->runSQL("ROLLBACK");
                $_SESSION['flash'] = 'warning';
                $_SESSION['flash_message'] = 'Status saat ini sudah <strong>' . htmlspecialchars($status_baru) . '</strong>. Tidak ada perubahan yang disimpan.';
                header("Location: select.php"); 
                exit;
            }
            
            // Tutup status lama
            $id_bedsts_lama = $last_status['f_idbedsts'];
            $sql_tutup_lama = "UPDATE t_bedstatus SET 
                                f_waktuselesai = '$waktu_mulai' 
                                WHERE f_idbedsts = $id_bedsts_lama";
            $db->runSQL($sql_tutup_lama);
        }

        // 4. Proses berdasarkan status baru
        if ($status_baru == 'Maintenance') {
            // Update status fisik master ke Maintenance
            $sql_update_master = "UPDATE t_tempattidur 
                                  SET f_stsfisik = 'Maintenance',
                                      f_updated = '$waktu_mulai'
                                  WHERE f_idbed = $id_bed";
            $db->runSQL($sql_update_master);
            
            $keterangan_final = $keterangan ?: "Bed dalam maintenance (Set dari Manajemen Bed Status)";
            $sql_insert_baru = "INSERT INTO t_bedstatus 
                                (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created) 
                                VALUES ('$id_petugas', '$id_bed', 'Maintenance', '$waktu_mulai', '$keterangan_final', '$waktu_mulai')";
            $db->runSQL($sql_insert_baru);
            
            $pesan_sukses = 'Status bed berhasil diubah menjadi <strong>Maintenance</strong>. Status Fisik Master Data juga diperbarui ke <strong>Maintenance</strong>.';

        } else {
            // Pastikan status fisik master adalah Aktif
            $sql_ensure_active = "UPDATE t_tempattidur 
                                  SET f_stsfisik = 'Aktif',
                                      f_updated = '$waktu_mulai'
                                  WHERE f_idbed = $id_bed 
                                  AND f_stsfisik != 'Aktif'";
            $db->runSQL($sql_ensure_active);
            
            $keterangan_final = $keterangan ?: "Status diubah menjadi $status_baru";
            $sql_insert_baru = "INSERT INTO t_bedstatus 
                                (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created) 
                                VALUES ('$id_petugas', '$id_bed', '$status_baru', '$waktu_mulai', '$keterangan_final', '$waktu_mulai')";
            $db->runSQL($sql_insert_baru);
            
            $pesan_sukses = 'Status tempat tidur berhasil diubah menjadi <strong>' . htmlspecialchars($status_baru) . '</strong>.';
        }

        $db->runSQL("COMMIT");
        $_SESSION['flash'] = 'success';
        $_SESSION['flash_message'] = $pesan_sukses;
        
    } catch (Exception $e) {
        $db->runSQL("ROLLBACK");
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Gagal mengubah status: ' . $e->getMessage();
    }
    
    header("Location: select.php"); 
    exit;
}

// Ambil info bed
$sql_bed_info = "SELECT 
                    tt.f_idbed, tt.f_nomorbed, tt.f_stsfisik,
                    tr.f_nama AS nama_ruangan, tr.f_kelas, tr.f_lantai
                  FROM t_tempattidur tt
                  JOIN t_ruangan tr ON tt.f_idruangan = tr.f_idruangan
                  WHERE tt.f_idbed = $id_bed";
$bed_info = $db->getALL($sql_bed_info);
if (!$bed_info) {
    header("Location: select.php?msg=data_not_found");
    exit;
}
$bed_info = $bed_info[0]; 

// Ambil status saat ini (jika ada)
$status_saat_ini = 'Belum Ada Data'; 
$waktu_status = '-';

$sql_active_status = "SELECT f_sts, f_waktumulai FROM t_bedstatus 
                      WHERE f_idbed = $id_bed AND f_waktuselesai IS NULL
                      ORDER BY f_waktumulai DESC LIMIT 1";
$active_status_data = $db->getALL($sql_active_status);

if ($active_status_data) {
    $status_saat_ini = $active_status_data[0]['f_sts'];
    $waktu_status = $active_status_data[0]['f_waktumulai'];
}

$list_status = ['Kosong', 'Siap', 'Pembersihan', 'Maintenance']; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Atur Status Tempat Tidur - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

<div id="wrapper">

    <?php include '../sidebar.php'; ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../topbar.php'; ?>
            
            <div class="container-fluid mt-4">

                <h1 class="h3 mb-4 text-gray-800">
                    <i class="fas fa-sync-alt"></i> Atur Status Tempat Tidur
                </h1>

                <?php if (isset($_SESSION['flash_message'])) { 
                    echo $_SESSION['flash_message']; 
                    unset($_SESSION['flash_message']); 
                } ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Info Sinkronisasi:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Mengubah status ke <strong>Maintenance</strong> akan otomatis mengupdate <strong>Status Fisik Master Data</strong> menjadi Maintenance</li>
                        <li>Mengubah ke status lain (Siap, Pembersihan, Kosong) akan memastikan Status Fisik Master adalah <strong>Aktif</strong></li>
                        <li>Tidak dapat mengubah status jika bed sedang ditempati pasien</li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-primary text-white">
                                <h6 class="m-0 font-weight-bold"><i class="fas fa-bed"></i> Detail Tempat Tidur</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>ID Bed:</strong> <?= $bed_info['f_idbed'] ?></p>
                                <p><strong>Nomor Bed:</strong> <span class="badge badge-primary p-2"><?= htmlspecialchars($bed_info['f_nomorbed']) ?></span></p>
                                <p><strong>Ruangan:</strong> <?= htmlspecialchars($bed_info['nama_ruangan']) ?> (Kelas <?= $bed_info['f_kelas'] ?>)</p>
                                <p><strong>Lantai:</strong> <?= $bed_info['f_lantai'] ?></p>
                                <p><strong>Status Fisik Master:</strong> 
                                    <span class="badge 
                                        <?php 
                                            if ($bed_info['f_stsfisik'] == 'Aktif') echo 'badge-success';
                                            elseif ($bed_info['f_stsfisik'] == 'Nonaktif') echo 'badge-danger';
                                            else echo 'badge-warning';
                                        ?>
                                    "> <?= htmlspecialchars($bed_info['f_stsfisik']) ?></span>
                                </p>
                                <hr>
                                <h5>Status Operasional Saat Ini</h5>
                                <h4 class="font-weight-bold">
                                    <span class="badge 
                                        <?php 
                                            if ($status_saat_ini == 'Kosong' || $status_saat_ini == 'Siap') echo 'badge-success';
                                            elseif ($status_saat_ini == 'Pembersihan') echo 'badge-info';
                                            elseif ($status_saat_ini == 'Maintenance') echo 'badge-warning';
                                            else echo 'badge-secondary';
                                        ?>
                                    " style="font-size: 1.25rem;"><?= htmlspecialchars($status_saat_ini) ?></span>
                                </h4>
                                <?php if ($waktu_status != '-'): ?>
                                <small class="text-muted">
                                    Sejak: <?= date('d/m/Y H:i', strtotime($waktu_status)) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-warning text-white">
                                <h6 class="m-0 font-weight-bold"><i class="fas fa-edit"></i> Form Update Status Operasional</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">

                                    <div class="form-group">
                                        <label for="status_baru">
                                            <i class="fas fa-tasks"></i> Pilih Status Baru <span class="text-danger">*</span>
                                        </label>
                                        <select name="status_baru" id="status_baru" class="form-control" required>
                                            <option value="">-- Pilih Status --</option>
                                            <?php foreach($list_status as $status): ?>
                                                <option value="<?php echo $status; ?>"
                                                    <?php if (($status_saat_ini ?? '') == $status) echo "disabled"; ?>>
                                                    <?php echo htmlspecialchars($status); ?>
                                                    <?php if (($status_saat_ini ?? '') == $status) echo " (Status Saat Ini)"; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="keterangan"><i class="fas fa-comment"></i> Keterangan (Opsional)</label>
                                        <textarea name="keterangan" id="keterangan" class="form-control" rows="3"
                                            placeholder="Contoh: Mulai pembersihan rutin, Perbaikan AC, dll."></textarea>
                                    </div>
                                    
                                    <div class="alert alert-warning small">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Perhatian:</strong>
                                        <ul class="mb-0 mt-1">
                                            <li>Mengubah status akan menutup riwayat status sebelumnya</li>
                                            <li>Status <strong>Maintenance</strong> akan mengupdate Master Data</li>
                                            <li>Waktu mulai otomatis dicatat saat ini</li>
                                        </ul>
                                    </div>

                                    <button type="submit" name="simpan_status" class="btn btn-warning btn-icon-split btn-lg">
                                        <span class="icon text-white-50"><i class="fas fa-save"></i></span>
                                        <span class="text">Simpan Status Baru</span>
                                    </button>
                                    
                                    <a href="select.php" class="btn btn-secondary btn-lg ml-2">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </a>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; RS InsanMedika <?= date('Y') ?></span>
                </div>
            </div>
        </footer>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

</body>
</html>