<?php
session_start(); 
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

$idruangan_default = 0;
$nama_ruangan_default = '';
$is_pengganti = false;

if (isset($_GET['idruangan_default']) && is_numeric($_GET['idruangan_default'])) {
    $idruangan_default = (int)$_GET['idruangan_default'];
    $sql_get_ruangan = "SELECT f_nama, f_kelas, f_lantai FROM t_ruangan WHERE f_idruangan = $idruangan_default";
    $ruangan_default_data = $db->getITEM($sql_get_ruangan);
    
    if ($ruangan_default_data) {
        $nama_ruangan_default = $ruangan_default_data['f_nama'] . ' - ' . $ruangan_default_data['f_kelas'] . ' (Lantai ' . $ruangan_default_data['f_lantai'] . ')';
        $is_pengganti = true;
    } else {
        $idruangan_default = 0; 
    }
}

if (!$is_pengganti) {
    $sql_ruangan = "SELECT f_idruangan, f_nama, f_kelas, f_lantai FROM t_ruangan ORDER BY f_nama ASC";
    $ruangan_list = $db->getALL($sql_ruangan);
}

if (isset($_POST['simpan'])) {
    $idruangan = (int)$_POST['idruangan']; 
    $nomorbed = trim($_POST['nomorbed']);
    $stsfisik = $_POST['stsfisik'];
    $created = date("Y-m-d H:i:s");
    $updated = date("Y-m-d H:i:s");
    $id_petugas = $_SESSION['idpetugas'];
    
    if (empty($idruangan) || empty($nomorbed) || empty($stsfisik) || $idruangan == 0) {
        $pesan_error = 'Gagal menyimpan. Harap lengkapi semua data.';
         $_SESSION['flash_message'] = '
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-times-circle"></i> Gagal!</strong> ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        header("Location: " . ($is_pengganti ? "createbaru.php?idruangan_default=$idruangan" : "createbaru.php"));
        exit;
    }
    
    $valid_statuses = ['Aktif', 'Nonaktif', 'Maintenance'];
    if (!in_array($stsfisik, $valid_statuses)) {
         $pesan_error = 'Status Fisik yang dipilih tidak valid.';
         $_SESSION['flash_message'] = '
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-times-circle"></i> Gagal!</strong> ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        header("Location: " . ($is_pengganti ? "createbaru.php?idruangan_default=$idruangan" : "createbaru.php"));
        exit;
    }

    try {
        $db->runSQL("START TRANSACTION");
        
        $idruangan_safe = $db->escapeString($idruangan);
        $nomorbed_safe = $db->escapeString($nomorbed);
        $stsfisik_safe = $db->escapeString($stsfisik);

        $sql = "INSERT INTO t_tempattidur (f_idruangan, f_nomorbed, f_stsfisik, f_created, f_updated)
                VALUES ('$idruangan_safe', '$nomorbed_safe', '$stsfisik_safe', '$created', '$updated')";
        $db->runSQL($sql);
        
        $new_bed_id = $db->conn->insert_id;
        
        if ($stsfisik === 'Aktif') {
            $keterangan = ($is_pengganti ? "Bed pengganti baru" : "Bed baru") . " ditambahkan dengan status Aktif. Siap digunakan.";
            $sql_insert_log = "INSERT INTO t_bedstatus 
                              (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                              VALUES ('$id_petugas', '$new_bed_id', 'Siap', '$created', 
                              '$keterangan', '$created')";
            $db->runSQL($sql_insert_log);
            
        } elseif ($stsfisik === 'Maintenance') {
            $keterangan = ($is_pengganti ? "Bed pengganti baru" : "Bed baru") . " ditambahkan dengan status Maintenance.";
            $sql_insert_log = "INSERT INTO t_bedstatus 
                              (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                              VALUES ('$id_petugas', '$new_bed_id', 'Maintenance', '$created', 
                              '$keterangan', '$created')";
            $db->runSQL($sql_insert_log);
        }

        $db->runSQL("COMMIT");
        
        $pesan_sukses = ($is_pengganti ? "Tempat Tidur Pengganti" : "Tempat Tidur") . " baru (<strong>" . htmlspecialchars($nomorbed) . "</strong>) berhasil ditambahkan. Status fisik: <strong>" . htmlspecialchars($stsfisik) . "</strong>.";
        $_SESSION['flash_message'] = '
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-check-circle"></i> Sukses!</strong> ' . $pesan_sukses . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
            
    } catch (Exception $e) {
        $db->runSQL("ROLLBACK");
        $pesan_error = 'Gagal menyimpan data Tempat Tidur: ' . $e->getMessage();
        $_SESSION['flash_message'] = '
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-times-circle"></i> Gagal!</strong> ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
    }

    header("Location: select.php");
    exit;
}

$pesan_notifikasi = '';
if (isset($_SESSION['flash_message'])) {
    $pesan_notifikasi = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $is_pengganti ? 'Tambah Bed Pengganti' : 'Tambah Data Tempat Tidur' ?> - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,.25);
        }
        .card-header {
            background-color: #4e73df;
        }
        .ruangan-display {
            background-color: #e7f3ff;
            padding: 1rem;
            border-left: 4px solid #4e73df;
            border-radius: 5px;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">

        <?php include '../sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include '../topbar.php'; ?>
                
                <div class="container-fluid mt-4">
                    
                    <?php echo $pesan_notifikasi; ?>

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-<?= $is_pengganti ? 'sync-alt' : 'plus-circle' ?>"></i> 
                            <?= $is_pengganti ? 'Tambah Bed Pengganti (Aset Baru)' : 'Tambah Data Tempat Tidur' ?>
                        </h1>
                    </div>
                    
                    <?php if ($is_pengganti): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <strong>Mode Bed Pengganti:</strong> Anda sedang membuat <strong>BED PENGGANTI BARU</strong> untuk Ruangan: <strong><?= htmlspecialchars($nama_ruangan_default) ?></strong>. 
                            <br>Ruangan sudah otomatis terisi dan tidak dapat diubah.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Gunakan formulir ini untuk menambahkan aset tempat tidur baru ke sistem.
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-sync-alt"></i> <strong>Info Sinkronisasi Otomatis:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Status Aktif:</strong> Otomatis membuat log "Siap" di Manajemen Bed Status</li>
                            <li><strong>Status Maintenance:</strong> Otomatis membuat log "Maintenance" di Manajemen Bed Status</li>
                            <li><strong>Status Nonaktif:</strong> Tidak membuat log di Bed Status (aset tidak operasional)</li>
                        </ul>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-bed"></i> Form Insert Tempat Tidur Baru
                            </h6>
                        </div>

                        <div class="card-body">

                            <form action="<?= $is_pengganti ? "createbaru.php?idruangan_default=$idruangan_default" : "createbaru.php" ?>" method="POST">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="idruangan">
                                                <i class="fas fa-door-open"></i> Ruangan <span class="text-danger">*</span>
                                            </label>
                                            <?php if ($is_pengganti): ?>
                                                <div class="ruangan-display">
                                                    <strong><?= htmlspecialchars($nama_ruangan_default) ?></strong>
                                                </div>
                                                <input type="hidden" name="idruangan" value="<?= $idruangan_default ?>">
                                                <small class="form-text text-muted">Ruangan sudah ditentukan (mode bed pengganti)</small>
                                            <?php else: ?>
                                                <select name="idruangan" id="idruangan" class="form-control" required>
                                                    <option value="">-- Pilih Ruangan --</option>
                                                    <?php 
                                                    if (!empty($ruangan_list)) {
                                                        foreach ($ruangan_list as $ruangan) {
                                                            echo '<option value="' . htmlspecialchars($ruangan['f_idruangan']) . '">' 
                                                                 . htmlspecialchars($ruangan['f_nama']) . ' - ' . $ruangan['f_kelas'] 
                                                                 . ' (Lantai ' . $ruangan['f_lantai'] . ')</option>';
                                                        }
                                                    } else {
                                                        echo '<option disabled>Tidak ada data ruangan</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <small class="form-text text-muted">Pilih ruangan tempat bed akan ditempatkan</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nomorbed">
                                                <i class="fas fa-hashtag"></i> Nomor Bed / Aset Baru <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   name="nomorbed" 
                                                   id="nomorbed" 
                                                   class="form-control" 
                                                   required 
                                                   placeholder="<?= $is_pengganti ? 'Contoh: A01-P, B10-NEW' : 'Contoh: A01, B10, C-101' ?>">
                                            <?php if ($is_pengganti): ?>
                                                <small class="form-text text-muted">
                                                    <strong>Tips:</strong> Gunakan penamaan baru yang membedakan dari bed lama 
                                                    (misalnya Bed lama 'A01', Bed baru 'A01-P' atau 'A01-NEW')
                                                </small>
                                            <?php else: ?>
                                                <small class="form-text text-muted">Nomor identifikasi unik untuk bed ini</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="stsfisik">
                                        <i class="fas fa-cog"></i> Status Fisik <span class="text-danger">*</span>
                                    </label>
                                    <select name="stsfisik" id="stsfisik" class="form-control" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Aktif" selected>Aktif (Rekomendasi untuk aset baru)</option>
                                        <option value="Nonaktif">Nonaktif (Aset tidak operasional)</option>
                                        <option value="Maintenance">Maintenance (Perlu perbaikan)</option>
                                    </select>
                                    <small class="form-text text-muted">Pilih status fisik bed saat ini</small>
                                </div>

                                <?php if ($is_pengganti): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-lightbulb"></i> <strong>Rekomendasi untuk Bed Pengganti:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Pilih status <strong>"Aktif"</strong> jika bed baru siap digunakan</li>
                                            <li>Pilih status <strong>"Maintenance"</strong> jika perlu pemasangan/penyesuaian dulu</li>
                                            <li>Hindari status <strong>"Nonaktif"</strong> untuk aset baru</li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <button type="submit" name="simpan" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Tempat Tidur Baru
                                </button>

                                <a href="select.php" class="btn btn-secondary ml-2">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                                </a>

                            </form>

                        </div>
                    </div>

                    <?php if ($is_pengganti): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3" style="background-color: #36b9cc;">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-question-circle"></i> Catatan Penting - Bed Pengganti
                                </h6>
                            </div>
                            <div class="card-body">
                                <h6 class="font-weight-bold">Apa itu Bed Pengganti?</h6>
                                <p>
                                    Bed pengganti adalah aset tempat tidur baru yang dibuat untuk menggantikan bed lama 
                                    yang sudah berstatus <span class="badge badge-danger">Nonaktif Permanen</span>.
                                </p>
                                
                                <h6 class="font-weight-bold mt-3">Mengapa Perlu Bed Pengganti?</h6>
                                <ul>
                                    <li>Bed yang sudah Nonaktif tidak dapat diaktifkan kembali</li>
                                    <li>Aset fisik rusak/diganti memerlukan nomor aset baru</li>
                                    <li>Menjaga audit trail dan riwayat aset di sistem</li>
                                </ul>

                                <h6 class="font-weight-bold mt-3">Alur Penggantian Bed:</h6>
                                <ol>
                                    <li>Bed lama diubah status menjadi <strong>Nonaktif</strong> di halaman Detail</li>
                                    <li>Sistem mengarahkan untuk membuat bed pengganti (halaman ini)</li>
                                    <li>Bed baru dibuat dengan nomor berbeda di ruangan yang sama</li>
                                    <li>Bed baru otomatis terdaftar di Manajemen Bed Status (jika Aktif/Maintenance)</li>
                                </ol>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; <?= date('Y') ?> RS InsanMedika</span>
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
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

</body>

</html>