<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once "dbcontroller.php";
$db = new dbcontroller();

if (!isset($_SESSION['idpetugas'])) {
    header("location:login.php");
    exit;
}
$idpetugas = $_SESSION['idpetugas'];
$sql_petugas = "SELECT f_nama FROM t_petugas WHERE f_idpetugas = $idpetugas LIMIT 1";
$data_petugas = $db->getITEM($sql_petugas);
$nama_petugas = $data_petugas ? $data_petugas['f_nama'] : 'Petugas Tidak Dikenal';
$idbed = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idbed == 0) {
    $_SESSION['pesan'] = ['jenis' => 'danger', 'teks' => 'ID Tempat Tidur tidak valid.'];
    header("location:../index.php");
    exit;
}

$error_message = '';
$success_message = '';

function loadDetailBed($db, $idbed) {
    $sql_detail = "SELECT 
                        tt.f_idbed,
                        tt.f_nomorbed,
                        tt.f_stsfisik,
                        r.f_idruangan,
                        r.f_nama AS nama_ruangan,
                        r.f_kelas,
                        r.f_lantai,
                        
                        (SELECT COUNT(*) 
                         FROM t_rawatinap ri 
                         WHERE ri.f_idbed = tt.f_idbed 
                           AND ri.f_waktukeluar IS NULL) AS ada_pasien,
                        
                        (SELECT p.f_nama 
                         FROM t_rawatinap ri
                         JOIN t_pasien p ON ri.f_idpasien = p.f_idpasien
                         WHERE ri.f_idbed = tt.f_idbed 
                           AND ri.f_waktukeluar IS NULL 
                         LIMIT 1) AS nama_pasien_aktif,
                         
                        (SELECT ri.f_waktumasuk
                         FROM t_rawatinap ri
                         WHERE ri.f_idbed = tt.f_idbed 
                           AND ri.f_waktukeluar IS NULL 
                         LIMIT 1) AS waktu_masuk_pasien,

                        (SELECT bs.f_sts 
                         FROM t_bedstatus bs 
                         WHERE bs.f_idbed = tt.f_idbed 
                           AND bs.f_waktuselesai IS NULL
                         ORDER BY bs.f_created DESC 
                         LIMIT 1) AS status_bedstatus,
                        
                        (SELECT bs.f_waktumulai 
                         FROM t_bedstatus bs 
                         WHERE bs.f_idbed = tt.f_idbed 
                           AND bs.f_waktuselesai IS NULL
                         ORDER BY bs.f_created DESC 
                         LIMIT 1) AS waktu_status_mulai,
                        
                        (SELECT bs.f_keterangan 
                         FROM t_bedstatus bs 
                         WHERE bs.f_idbed = tt.f_idbed 
                           AND bs.f_waktuselesai IS NULL
                         ORDER BY bs.f_created DESC 
                         LIMIT 1) AS keterangan_status
                        
                     FROM t_tempattidur tt
                     JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                     WHERE tt.f_idbed = $idbed";
    
    $bed_detail = $db->getITEM($sql_detail);
    
    if (!$bed_detail) {
        return null;
    }

    $ada_pasien = $bed_detail['ada_pasien'] > 0;
    $status_fisik = $bed_detail['f_stsfisik'];
    $status_bedstatus = $bed_detail['status_bedstatus'];
    
    // Logika penentuan Status Final
    if ($status_fisik == 'Nonaktif' || $status_fisik == 'Maintenance') {
        $status_final = $status_fisik; 
    } elseif ($ada_pasien) {
        $status_final = 'Terisi';
    } elseif (!empty($status_bedstatus)) {
        $status_final = $status_bedstatus; 
    } else {
        $status_final = 'Kosong';
    }
    
    $bed_detail['status_final'] = $status_final;
    
    $sql_riwayat = "SELECT 
                        bs.f_sts, 
                        bs.f_waktumulai, 
                        bs.f_waktuselesai, 
                        bs.f_keterangan,
                        p.f_nama AS nama_petugas
                    FROM t_bedstatus bs
                    JOIN t_petugas p ON bs.f_idpetugas = p.f_idpetugas
                    WHERE bs.f_idbed = $idbed
                    ORDER BY bs.f_waktumulai DESC
                    LIMIT 10";
    
    $bed_detail['riwayat_status'] = $db->getLIST($sql_riwayat);

    return $bed_detail;
}

$data_bed = loadDetailBed($db, $idbed);

if (!$data_bed) {
    $_SESSION['pesan'] = ['jenis' => 'danger', 'teks' => 'Data Tempat Tidur tidak ditemukan.'];
    header("location:../index.php");
    exit;
}

function getStatusClass($status) {
    switch ($status) {
        case 'Terisi': return 'danger';
        case 'Kosong': return 'success';
        case 'Siap': return 'info';
        case 'Kotor': return 'warning';
        case 'Pembersihan': return 'primary';
        case 'Maintenance': 
        case 'Nonaktif': return 'secondary';
        default: return 'light';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Bed <?= htmlspecialchars($data_bed['f_nomorbed']) ?> - RS InsanMedika</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .detail-card-header {
            background-color: #4e73df;
            color: white;
        }
        .bed-status-final {
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php 
            include 'sidebar.php'; 
        ?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php 
                    include 'topbar.php'; 
                ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Informasi Tempat Tidur (Bed)</h1> 
                        <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-home fa-sm text-white-50"></i> Kembali ke Dashboard
                        </a>
                    </div>
                    
                    <?php 
                        if (!empty($error_message)) {
                            echo '<div class="alert alert-danger" role="alert">'.$error_message.'</div>';
                        }
                        if (!empty($success_message)) {
                            echo '<div class="alert alert-success" role="alert">'.$success_message.'</div>';
                        }
                    ?>
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 detail-card-header">
                                    <h6 class="m-0 font-weight-bold">Informasi Bed: <span class="badge badge-light p-2"><?= htmlspecialchars($data_bed['f_nomorbed']) ?></span></h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">Ruangan / Kelas</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($data_bed['nama_ruangan']) ?> (<?= htmlspecialchars($data_bed['f_kelas']) ?>)</dd>

                                        <dt class="col-sm-4">Nomor Bed</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($data_bed['f_nomorbed']) ?></dd>
                                        
                                        <dt class="col-sm-4">Status Fisik</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge badge-<?= getStatusClass($data_bed['f_stsfisik']) ?>">
                                                <?= htmlspecialchars($data_bed['f_stsfisik']) ?>
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-4">Status PRIORITAS</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bed-status-final badge-<?= getStatusClass($data_bed['status_final']) ?>">
                                                <?= htmlspecialchars($data_bed['status_final']) ?>
                                            </span>
                                        </dd>
                                    </dl>
                                    
                                    <hr>
                                    
                                    <h5><i class="fas fa-user-injured"></i> Informasi Pasien Aktif</h5>
                                    <?php if ($data_bed['ada_pasien'] > 0): ?>
                                        <dl class="row mt-3">
                                            <dt class="col-sm-4 text-danger">Terisi Oleh</dt>
                                            <dd class="col-sm-8 text-danger font-weight-bold"><?= htmlspecialchars($data_bed['nama_pasien_aktif']) ?></dd>
                                            
                                            <dt class="col-sm-4">Waktu Masuk</dt>
                                            <dd class="col-sm-8"><?= date('d F Y H:i', strtotime($data_bed['waktu_masuk_pasien'])) ?></dd>
                                        
                                        </dl>
                                    <?php else: ?>
                                        <div class="alert alert-info">Bed ini sedang tidak diisi pasien.</div>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-info text-white">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-history"></i> Riwayat Status (10 Terbaru)</h6>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (!empty($data_bed['riwayat_status'])): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($data_bed['riwayat_status'] as $riwayat): ?>
                                                <li class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge badge-<?= getStatusClass($riwayat['f_sts']) ?> p-2">
                                                            <?= htmlspecialchars($riwayat['f_sts']) ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            Oleh: <?= htmlspecialchars($riwayat['nama_petugas']) ?>
                                                        </small>
                                                    </div>
                                                    <small class="text-secondary d-block mt-1">
                                                        Mulai: <?= date('d M Y H:i:s', strtotime($riwayat['f_waktumulai'])) ?>
                                                    </small>
                                                    <?php if ($riwayat['f_waktuselesai']): ?>
                                                        <small class="text-secondary d-block">
                                                            Selesai: <?= date('d M Y H:i:s', strtotime($riwayat['f_waktuselesai'])) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Status Aktif</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($riwayat['f_keterangan'])): ?>
                                                        <p class="mb-0 mt-2" style="font-size: 0.9rem; font-style: italic;">
                                                            Ket: <?= htmlspecialchars($riwayat['f_keterangan']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-center text-muted">Belum ada riwayat perubahan status operasional.</p>
                                    <?php endif; ?>
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

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="js/sb-admin-2.min.js"></script>
</body>
</html>