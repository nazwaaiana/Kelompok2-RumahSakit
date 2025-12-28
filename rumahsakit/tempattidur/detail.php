<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: select.php");
    exit;
}

$id_bed = (int)$_GET['id'];
$pesan_sukses = "";
$pesan_error = "";

$sql_detail = "SELECT 
                tt.f_idbed,
                tt.f_nomorbed,
                tt.f_stsfisik, 
                tt.f_created,
                tt.f_updated,
                r.f_idruangan,
                r.f_nama AS nama_ruangan,
                r.f_kelas AS kelas_ruangan,
                r.f_lantai
            FROM 
                t_tempattidur tt
            INNER JOIN 
                t_ruangan r ON tt.f_idruangan = r.f_idruangan
            WHERE 
                tt.f_idbed = $id_bed";

$bed_detail = $db->getITEM($sql_detail);

if (!$bed_detail) {
    header("Location: select.php?msg=Bed tidak ditemukan");
    exit;
}

$bed_fisik_status = $bed_detail['f_stsfisik']; 

if (isset($_POST['update_fisik'])) {
    $new_status = $_POST['f_stsfisik'];
    $old_status = $bed_fisik_status;
    $current_time = date('Y-m-d H:i:s');
    $id_petugas = $_SESSION['idpetugas'];

    if ($old_status == 'Nonaktif') {
        $pesan_error = "Tidak dapat mengubah status fisik karena tempat tidur ini sudah berstatus Nonaktif Permanen. Harus diganti dengan aset baru.";
    } else {
        $valid_statuses = ['Aktif', 'Nonaktif', 'Maintenance'];
        if (in_array($new_status, $valid_statuses)) {

            try {
                $db->runSQL("START TRANSACTION");
                
                $sql_update = "UPDATE t_tempattidur 
                                SET f_stsfisik = '$new_status', 
                                    f_updated = '$current_time' 
                                WHERE f_idbed = $id_bed";
                $db->runSQL($sql_update);
                
                if ($old_status !== $new_status) {
                    
                    $sql_close_active = "UPDATE t_bedstatus 
                                        SET f_waktuselesai = '$current_time',
                                            f_keterangan = CONCAT(f_keterangan, ' (Ditutup Otomatis oleh Update Master Data)')
                                        WHERE f_idbed = '$id_bed' 
                                        AND f_waktuselesai IS NULL";
                    $db->runSQL($sql_close_active);
                    
                    if ($new_status === 'Maintenance') {
                        $keterangan = "Master Data diubah menjadi Maintenance. Bed dalam perbaikan fisik berat.";
                        $sql_insert_log = "INSERT INTO t_bedstatus 
                                          (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                                          VALUES ('$id_petugas', '$id_bed', 'Maintenance', '$current_time', 
                                          '$keterangan', '$current_time')";
                        $db->runSQL($sql_insert_log);
                        
                        $pesan_sukses = "Status Fisik berhasil diubah menjadi <strong>Maintenance</strong>. Log otomatis dibuat di Manajemen Bed Status.";
                        
                    } elseif ($new_status === 'Aktif' && $old_status === 'Maintenance') {
                        $keterangan = "Master Data diubah menjadi Aktif dari Maintenance. Bed siap digunakan.";
                        $sql_insert_log = "INSERT INTO t_bedstatus 
                                          (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                                          VALUES ('$id_petugas', '$id_bed', 'Siap', '$current_time', 
                                          '$keterangan', '$current_time')";
                        $db->runSQL($sql_insert_log);
                        
                        $pesan_sukses = "Status Fisik berhasil diubah menjadi <strong>Aktif</strong>. Bed kini berstatus <strong>Siap</strong> digunakan.";
                        
                    } elseif ($new_status === 'Aktif') {
                        $keterangan = "Master Data diubah menjadi Aktif. Bed siap digunakan.";
                        $sql_insert_log = "INSERT INTO t_bedstatus 
                                          (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                                          VALUES ('$id_petugas', '$id_bed', 'Siap', '$current_time', 
                                          '$keterangan', '$current_time')";
                        $db->runSQL($sql_insert_log);
                        
                        $pesan_sukses = "Status Fisik berhasil diubah menjadi <strong>Aktif</strong>. Bed kini berstatus <strong>Siap</strong> digunakan.";
                        
                    } elseif ($new_status === 'Nonaktif') {
                        $pesan_sukses = "Status Fisik berhasil diubah menjadi <strong>Nonaktif Permanen</strong>. Bed ini tidak dapat digunakan lagi.";
                    }
                    
                } else {
                    $pesan_sukses = "Status Fisik Tempat Tidur berhasil diperbarui.";
                }
                
                $db->runSQL("COMMIT");
                $bed_fisik_status = $new_status;
                
            } catch (Exception $e) {
                $db->runSQL("ROLLBACK");
                $pesan_error = "Gagal memperbarui Status Fisik: " . $e->getMessage();
            }

        } else {
            $pesan_error = "Status yang dipilih tidak valid.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Tempat Tidur - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .info-box {
            background: #f8f9fc;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #4e73df;
        }
        .status-display {
            text-align: center;
            padding: 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .status-display .badge-lg {
            font-size: 1.5rem;
            padding: 1rem 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .badge-lg {
            font-size: 1.2rem;
            padding: 0.6rem 1.2rem;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>

<body id="page-top">

<div id="wrapper">
    <?php include '../sidebar.php'; ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../topbar.php'; ?>
            
            <div class="container-fluid">

                <?php if ($pesan_sukses): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm">
                        <i class="fas fa-check-circle"></i> <?= $pesan_sukses ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                        <i class="fas fa-exclamation-triangle"></i> <?= $pesan_error ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-bed"></i> Detail Tempat Tidur (Bed)
                    </h1>
                    <a href="select.php" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Bed
                    </a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-info-circle"></i> Detail Master Bed: <?= htmlspecialchars($bed_detail['f_nomorbed']) ?>
                        </h6>
                        
                        <a href="update.php?id=<?= $id_bed ?>" class="btn btn-warning btn-sm" title="Ubah Data Master Bed"> 
                            <i class="fas fa-edit"></i> Edit Data Master
                        </a>
                    </div>

                    <div class="card-body">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-clipboard-list"></i> Informasi Tempat Tidur
                                </h5>
                                <div class="info-box">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%">Nomor Bed</th>
                                            <td>: <span class="badge badge-primary badge-lg"><?= htmlspecialchars($bed_detail['f_nomorbed']) ?></span></td>
                                        </tr>
                                        <tr>
                                            <th>Ruangan</th>
                                            <td>: <strong><?= htmlspecialchars($bed_detail['nama_ruangan']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Kelas Ruangan</th>
                                            <td>: <span class="badge badge-info"><?= htmlspecialchars($bed_detail['kelas_ruangan']) ?></span></td>
                                        </tr>
                                        <tr>
                                            <th>Lantai</th>
                                            <td>: Lantai <?= htmlspecialchars($bed_detail['f_lantai']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Dibuat</th>
                                            <td>: <?= date('d M Y H:i', strtotime($bed_detail['f_created'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Terakhir Diperbarui</th>
                                            <td>: <?= date('d M Y H:i', strtotime($bed_detail['f_updated'])) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="text-danger mb-3">
                                    <i class="fas fa-cog"></i> Status Fisik Saat Ini
                                </h5>
                                <div class="status-display">
                                    <span class="badge badge-lg
                                        <?php 
                                            if ($bed_fisik_status == 'Aktif') echo 'badge-success';
                                            elseif ($bed_fisik_status == 'Nonaktif') echo 'badge-danger';
                                            elseif ($bed_fisik_status == 'Maintenance') echo 'badge-warning text-dark';
                                            else echo 'badge-secondary';
                                        ?>">
                                        <i class="fas 
                                            <?php 
                                                if ($bed_fisik_status == 'Aktif') echo 'fa-check-circle';
                                                elseif ($bed_fisik_status == 'Nonaktif') echo 'fa-ban';
                                                elseif ($bed_fisik_status == 'Maintenance') echo 'fa-tools';
                                            ?>"></i>
                                        <?= htmlspecialchars($bed_fisik_status) ?>
                                    </span>
                                    <p class="mb-0 mt-3 text-white">
                                        <?php 
                                        if ($bed_fisik_status == 'Aktif') echo '<i class="fas fa-check-circle"></i> Bed operasional dan dapat digunakan';
                                        elseif ($bed_fisik_status == 'Maintenance') echo '<i class="fas fa-tools"></i> Bed dalam perbaikan fisik berat';
                                        elseif ($bed_fisik_status == 'Nonaktif') echo '<i class="fas fa-ban"></i> Bed tidak dapat digunakan (permanen)';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">

                        <h5 class="mt-4 mb-3 text-warning">
                           <i class="fas fa-sync-alt"></i> Ubah Status Fisik Master Bed
                        </h5>
                        
                        <?php if ($bed_fisik_status == 'Nonaktif'): ?>
                            <div class="alert alert-danger shadow-sm">
                                <i class="fas fa-ban"></i> <strong>PERHATIAN KHUSUS:</strong> Tempat tidur ini berstatus <strong>Nonaktif Permanen</strong>. 
                                Status ini tidak dapat diubah kembali menjadi Aktif atau Maintenance.
                                <br>Jika aset fisik diganti, Anda harus membuat aset baru.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info shadow-sm">
                                <i class="fas fa-info-circle"></i> <strong>Info Sinkronisasi:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Aktif → Maintenance:</strong> Otomatis mencatat log "Maintenance" di Manajemen Bed Status</li>
                                    <li><strong>Maintenance → Aktif:</strong> Otomatis mencatat log "Siap" di Manajemen Bed Status</li>
                                    <li><strong>Nonaktif:</strong> Bed dikeluarkan permanen, tidak ada log di Bed Status</li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="mt-3">
                            <div class="form-group row">
                                <label for="f_stsfisik" class="col-sm-3 col-form-label font-weight-bold">
                                    Pilih Status Fisik Baru
                                </label>
                                <div class="col-sm-5">
                                    <select name="f_stsfisik" id="f_stsfisik" class="form-control" required
                                            <?= ($bed_fisik_status == 'Nonaktif') ? 'disabled' : '' ?>>
                                        <option value="Aktif" <?= ($bed_fisik_status == 'Aktif') ? 'selected' : '' ?>>Aktif (Tersedia)</option>
                                        <option value="Nonaktif" <?= ($bed_fisik_status == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif (Dikeluarkan/Rusak Permanen)</option>
                                        <option value="Maintenance" <?= ($bed_fisik_status == 'Maintenance') ? 'selected' : '' ?>>Maintenance (Perbaikan Fisik Berat)</option>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" name="update_fisik" class="btn btn-warning btn-block"
                                            <?= ($bed_fisik_status == 'Nonaktif') ? 'disabled' : '' ?>>
                                        <i class="fas fa-sync-alt"></i> Update Status Fisik
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                    </div>
                </div>
            </div>

        </div>
        
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; 2025 RS InsanMedika | Kelompok Dua</span>
                </div>
            </div>
        </footer>

    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

</body>
</html>