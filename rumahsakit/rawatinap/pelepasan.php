<?php
require_once '../config.php';
date_default_timezone_set('Asia/Jakarta');
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Petugas Kebersihan', 'Admisi']);

if (!isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php"); 
    exit;
}

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php"); 
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}

$idrawatinap = (int)$_GET['id'];

$sql = "SELECT 
            ri.f_idrawatinap, ri.f_waktumasuk, ri.f_idbed,
            p.f_idpasien, p.f_norekmed, p.f_nama AS nama_pasien, p.f_tgllahir, p.f_jnskelamin,
            tt.f_nomorbed,
            tr.f_nama AS nama_ruangan, tr.f_kelas
        FROM t_rawatinap ri
        JOIN t_pasien p ON ri.f_idpasien = p.f_idpasien
        JOIN t_tempattidur tt ON ri.f_idbed = tt.f_idbed
        JOIN t_ruangan tr ON tt.f_idruangan = tr.f_idruangan
        WHERE ri.f_idrawatinap = $idrawatinap AND ri.f_waktukeluar IS NULL";
        
$data_ri = $db->getITEM($sql);

if (!$data_ri) {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}

$waktu_masuk = new DateTime($data_ri['f_waktumasuk']);
$waktu_sekarang = new DateTime();
$interval = $waktu_masuk->diff($waktu_sekarang);

$lama_rawat = $interval->format('%a hari, %h jam');

$nama_petugas = $_SESSION['petugas'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Pelepasan Rawat Inap</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <style>
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .radio-item {
            display: flex;
            align-items: center;
        }
        .radio-item input[type="radio"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
        }
        .radio-item label {
            margin-bottom: 0;
            cursor: pointer;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php 
            include '../sidebar.php'; 
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php 
                    include '../topbar.php'; 
                ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Pelepasan Pasien Rawat Inap</h1>
                        <a href="select.php" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali ke Daftar RI</a>
                    </div>

                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-warning">
                                    <h6 class="m-0 font-weight-bold text-white">Konfirmasi Pelepasan Pasien</h6>
                                </div>
                                <div class="card-body">
                                    <h4 class="text-danger mb-4"><i class="fas fa-exclamation-triangle"></i> Perhatian!</h4>
                                    <p class="mb-4">Pastikan pasien <strong><?= htmlspecialchars($data_ri['nama_pasien']) ?></strong> benar-benar akan dilepas dari layanan Rawat Inap (Discharge) sebelum melanjutkan. Tindakan ini akan mencatat waktu keluar pasien dan mengubah status tempat tidur menjadi "Kotor" yang perlu dibersihkan.</p>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>No. Rekam Medis:</strong> <?= htmlspecialchars($data_ri['f_norekmed']) ?></p>
                                            <p class="mb-1"><strong>Nama Pasien:</strong> <?= htmlspecialchars($data_ri['nama_pasien']) ?></p>
                                            <p class="mb-1"><strong>Tanggal Lahir:</strong> <?= date('d-m-Y', strtotime($data_ri['f_tgllahir'])) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Ruangan/Kelas:</strong> <?= htmlspecialchars($data_ri['nama_ruangan']) ?> / <?= htmlspecialchars($data_ri['f_kelas']) ?></p>
                                            <p class="mb-1"><strong>Nomor Bed:</strong> <strong><?= htmlspecialchars($data_ri['f_nomorbed']) ?></strong></p>
                                            <p class="mb-1"><strong>Waktu Masuk:</strong> <?= date('d-m-Y H:i', strtotime($data_ri['f_waktumasuk'])) ?></p>
                                            <p class="mb-1"><strong>Lama Rawat:</strong> <span class="badge badge-primary"><?= $lama_rawat ?></span></p>
                                        </div>
                                    </div>
                                    
                                    <hr>

                                    <form action="proses_pelepasan.php" method="POST" id="formPelepasan">
                                        <input type="hidden" name="idrawatinap" value="<?= $data_ri['f_idrawatinap'] ?>">
                                        <input type="hidden" name="idbed" value="<?= $data_ri['f_idbed'] ?>">
                                        <input type="hidden" name="nomorbed" value="<?= htmlspecialchars($data_ri['f_nomorbed']) ?>">

                                        <div class="form-group">
                                            <label for="waktu_keluar">Waktu Keluar (Otomatis Saat Ini)</label>
                                            <input type="text" class="form-control" id="waktu_keluar_display" 
                                                   value="<?= date('d-m-Y H:i:s') ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label><strong>Alasan Keluar <span class="text-danger">*</span></strong></label>
                                            <div class="radio-group">
                                                <div class="radio-item">
                                                    <input type="radio" name="alasan" id="pulang" value="Pulang" required>
                                                    <label for="pulang">Pulang</label>
                                                </div>
                                                <div class="radio-item">
                                                    <input type="radio" name="alasan" id="pindah" value="Pindah" required>
                                                    <label for="pindah">Pindah Ruangan</label>
                                                </div>
                                                <div class="radio-item">
                                                    <input type="radio" name="alasan" id="meninggal" value="Meninggal" required>
                                                    <label for="meninggal">Meninggal</label>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Pilih alasan keluar pasien dari rawat inap</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="keterangan">Keterangan Tambahan (Opsional)</label>
                                            <textarea class="form-control" name="keterangan" id="keterangan" rows="3" 
                                                      placeholder="Catatan tambahan terkait pelepasan pasien..."></textarea>
                                        </div>

                                        <div class="alert alert-info mt-4">
                                            <i class="fas fa-info-circle"></i> <strong>Informasi:</strong> Setelah pelepasan, tempat tidur akan otomatis berstatus <strong>"Kotor"</strong> dan memerlukan pembersihan oleh petugas kebersihan sebelum dapat digunakan kembali.
                                        </div>

                                        <p class="mt-4">
                                            Klik tombol di bawah untuk mencatat waktu keluar dan menyelesaikan Rawat Inap pasien ini:
                                        </p>
                                        
                                        <button type="submit" name="submit_pelepasan" class="btn btn-warning btn-lg">
                                            <i class="fas fa-sign-out-alt"></i> <strong>Konfirmasi Pelepasan (Discharge)</strong>
                                        </button>
                                        <a href="select.php" class="btn btn-secondary btn-lg">Batal</a>
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
                        <span>Copyright &copy; 2025 RS InsanMedika | Kelompok Dua</span>
                    </div>
                </div>
            </footer>

        </div>

    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Siap untuk keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" di bawah jika Anda siap untuk mengakhiri sesi Anda saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <a class="btn btn-primary" href="pelepasan.php?log=logout">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    <script>
        document.getElementById('formPelepasan').addEventListener('submit', function(e) {
            var alasan = document.querySelector('input[name="alasan"]:checked');
            if (!alasan) {
                e.preventDefault();
                alert('Silakan pilih alasan keluar pasien!');
                return false;
            }
        });
    </script>

</body>

</html>