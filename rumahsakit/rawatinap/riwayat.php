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

if (!isset($_GET['pasien_id']) || !is_numeric($_GET['pasien_id'])) {
    $_SESSION['error'] = "ID Pasien tidak valid!";
    header("Location: select.php");
    exit;
}

$id_pasien = (int)$_GET['pasien_id'];
$sql_pasien = "SELECT * FROM t_pasien WHERE f_idpasien = $id_pasien";
$pasien_data = $db->getALL($sql_pasien);

if (empty($pasien_data)) {
    $_SESSION['error'] = "Data pasien tidak ditemukan!";
    header("Location: select.php");
    exit;
}

$pasien = $pasien_data[0];
$tgl_lahir = new DateTime($pasien['f_tgllahir']);
$hari_ini = new DateTime();
$umur = $hari_ini->diff($tgl_lahir)->y;

$sql_history = "SELECT 
                    ri.*,
                    r.f_nama AS nama_ruangan, 
                    r.f_kelas,
                    t.f_nomorbed,
                    p.f_nama AS nama_petugas
                FROM t_rawatinap ri
                LEFT JOIN t_tempattidur t ON ri.f_idbed = t.f_idbed
                LEFT JOIN t_ruangan r ON t.f_idruangan = r.f_idruangan
                LEFT JOIN t_petugas p ON ri.f_idpetugas = p.f_idpetugas
                WHERE ri.f_idpasien = $id_pasien
                ORDER BY ri.f_waktumasuk DESC";

$history = $db->getALL($sql_history);
$total_kunjungan = count($history);
$total_hari_rawat = 0;
$sedang_dirawat = 0;

foreach ($history as $h) {

    $masuk = new DateTime($h['f_waktumasuk']);

    if ($h['f_waktukeluar'] === null) {
        $sedang_dirawat++;
        $keluar = new DateTime();
    } else {
        $keluar = new DateTime($h['f_waktukeluar']);
    }

    $hari = $masuk->diff($keluar)->days + 1;

    $total_hari_rawat += $hari;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Riwayat Rawat Inap - <?= htmlspecialchars($pasien['f_nama']) ?></title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .patient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .timeline-container {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-line {
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #4e73df 0%, #e3e6f0 100%);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-dot {
            position: absolute;
            left: -22px;
            top: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: white;
            border: 4px solid #4e73df;
            z-index: 2;
        }
        
        .timeline-dot.active {
            border-color: #e74a3b;
            background-color: #e74a3b;
            box-shadow: 0 0 0 4px rgba(231, 74, 60, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 4px rgba(231, 74, 60, 0.2);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(231, 74, 60, 0.1);
            }
        }
        
        .timeline-card {
            transition: all 0.3s ease;
        }
        
        .timeline-card:hover {
            transform: translateX(5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
        }
        
        .timeline-card.active {
            border-left: 4px solid #e74a3b;
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .info-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #858796;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 0.95rem;
            color: #5a5c69;
        }
        
        .duration-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #f8f9fc;
            border-radius: 10px;
            font-size: 0.85rem;
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
                        <h1 class="h3 mb-0 text-gray-800">
                            Riwayat Rawat Inap
                        </h1>
                        <a href="select.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                        </a>
                    </div>

                    <div class="patient-header shadow">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-3">
                                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($pasien['f_nama']) ?>
                                </h3>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <small class="d-block opacity-75">No. Rekam Medis</small>
                                        <strong><?= htmlspecialchars($pasien['f_norekmed']) ?></strong>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <small class="d-block opacity-75">Jenis Kelamin</small>
                                        <strong><?= htmlspecialchars($pasien['f_jnskelamin']) ?></strong>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <small class="d-block opacity-75">Umur</small>
                                        <strong><?= $umur ?> Tahun</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-user-injured" style="font-size: 5rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow stat-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Kunjungan
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $total_kunjungan ?> Kali
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-danger shadow stat-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Sedang Dirawat
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $sedang_dirawat ?> Pasien
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-procedures fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow stat-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Hari Rawat
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $total_hari_rawat ?> Hari
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-history"></i> Timeline Riwayat Rawat Inap
                                    </h6>
                                </div>
                                <div class="card-body">

                                    <?php if (empty($history)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-folder-open fa-4x text-gray-300 mb-3"></i>
                                            <h5 class="text-gray-500">Belum Ada Riwayat</h5>
                                            <p class="text-muted">Pasien ini belum pernah menjalani rawat inap.</p>
                                        </div>
                                    <?php else: ?>
                                        
                                        <div class="timeline-container">
                                            <div class="timeline-line"></div>
                                            
                                            <?php foreach ($history as $index => $h): ?>
                                                <?php 
                                                    $is_active = ($h['f_waktukeluar'] === null);
                                                    $status_text = $is_active ? 'Sedang Dirawat' : 'Selesai';
                                                    $status_badge = $is_active ? 'badge-danger' : 'badge-success';
                                                    $card_class = $is_active ? 'active border-left-danger' : 'border-left-success';
                                                    
                                                    $tgl_masuk = date('d F Y', strtotime($h['f_waktumasuk']));
                                                    $jam_masuk = date('H:i', strtotime($h['f_waktumasuk']));
                                                    
                                                    $durasi = '';
                                                    if ($is_active) {
                                                        $masuk = strtotime($h['f_waktumasuk']);
                                                        $sekarang = time();
                                                        $hari = floor(($sekarang - $masuk) / (60 * 60 * 24)) + 1;
                                                        $durasi = "$hari hari (masih berlangsung)";
                                                    } else {
                                                        $masuk = strtotime($h['f_waktumasuk']);
                                                        $keluar = strtotime($h['f_waktukeluar']);
                                                        $hari = ceil(($keluar - $masuk) / (60 * 60 * 24));
                                                        $durasi = "$hari hari";
                                                    }
                                                ?>
                                                
                                                <div class="timeline-item">
                                                    <div class="timeline-dot <?= $is_active ? 'active' : '' ?>"></div>
                                                    
                                                    <div class="card timeline-card shadow-sm <?= $card_class ?>">
                                                        <div class="card-body">
                                                            
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                                    <h5 class="mb-1 text-primary">
                                                                        <i class="fas fa-calendar-day"></i> 
                                                                        Kunjungan ke-<?= $total_kunjungan - $index ?>
                                                                    </h5>
                                                                    <div class="text-muted small">
                                                                        <?= $tgl_masuk ?>
                                                                    </div>
                                                                </div>
                                                                <span class="badge <?= $status_badge ?> badge-pill">
                                                                    <?= $status_text ?>
                                                                </span>
                                                            </div>

                                                            <hr>

                                                            <!-- Info Grid -->
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <div class="info-label">
                                                                        <i class="fas fa-door-open"></i> Ruangan & Bed
                                                                    </div>
                                                                    <div class="info-value">
                                                                        <?= htmlspecialchars($h['nama_ruangan']) ?> 
                                                                        <span class="badge badge-secondary"><?= htmlspecialchars($h['f_kelas']) ?></span>
                                                                        <br>
                                                                        <strong>Bed: <?= htmlspecialchars($h['f_nomorbed']) ?></strong>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <div class="info-label">
                                                                        <i class="fas fa-user-nurse"></i> Petugas Penerima
                                                                    </div>
                                                                    <div class="info-value">
                                                                        <?= htmlspecialchars($h['nama_petugas'] ?? 'Tidak tercatat') ?>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <div class="info-label">
                                                                        <i class="fas fa-sign-in-alt"></i> Waktu Masuk
                                                                    </div>
                                                                    <div class="info-value">
                                                                        <?= $tgl_masuk ?>, <?= $jam_masuk ?> WIB
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <div class="info-label">
                                                                        <i class="fas fa-sign-out-alt"></i> Waktu Keluar
                                                                    </div>
                                                                    <div class="info-value">
                                                                        <?php if ($is_active): ?>
                                                                            <span class="text-danger font-weight-bold">
                                                                                <i class="fas fa-spinner fa-pulse"></i> Masih Dirawat
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <?= date('d F Y', strtotime($h['f_waktukeluar'])) ?>, 
                                                                            <?= date('H:i', strtotime($h['f_waktukeluar'])) ?> WIB
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <div class="info-label">
                                                                        <i class="fas fa-hourglass-half"></i> Durasi Rawat
                                                                    </div>
                                                                    <div class="info-value">
                                                                        <span class="duration-badge">
                                                                            <i class="fas fa-clock mr-2"></i>
                                                                            <?= $durasi ?>
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <div class="info-label">
                                                                        <i class="fas fa-info-circle"></i> Alasan Keluar
                                                                    </div>
                                                                    <div class="info-value">
                                                                        <?php if ($h['f_alasan'] == 'Null' || empty($h['f_alasan'])): ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-info">
                                                                                <?= htmlspecialchars($h['f_alasan']) ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endforeach; ?>
                                        </div>

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
                        <span>Copyright &copy; 2025 RS InsanMedika | Kelompok Dua</span>
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