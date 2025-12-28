<?php
require_once '../config.php'; 
require_once '../dbcontroller.php';
$db = new dbcontroller();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../check_role.php';

$id_petugas = $_SESSION['idpetugas'];

$query_profil = "SELECT f_idpetugas, f_nama, f_username, f_role, f_unitkerja, f_created, f_updated 
                 FROM t_petugas 
                 WHERE f_idpetugas = ?";
$result_profil = $db->runQueryWithParams($query_profil, "i", [$id_petugas]);

if ($result_profil && $result_profil->num_rows > 0) {
    $data_petugas = $result_profil->fetch_assoc();
} else {
    $_SESSION['error_message'] = "Data profil tidak ditemukan.";
    header("location: " . BASE_URL . "index.php");
    exit;
}

function formatTanggalID($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00 00:00:00' || strtotime($tanggal) < 0) {
        return 'Data Tanggal Tidak Tersedia';
    }
    
    $timestamp = strtotime($tanggal);
    $tanggal_en = date('d F Y', $timestamp);

    $bulan_id = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 
        'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 
        'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];

    return strtr($tanggal_en, $bulan_id);
}

$created_date_raw = $data_petugas['f_created'];
$updated_date_raw = $data_petugas['f_updated'];

$stats = [
    'total_login' => 0,
    'last_login' => date('d M Y H:i'),
    'account_age' => formatTanggalID($created_date_raw)
];

$last_update_display = 'Belum pernah diubah';
if (!empty($updated_date_raw) && $updated_date_raw !== '0000-00-00 00:00:00' && strtotime($updated_date_raw) > 0) {
    $last_update_display = formatTanggalID(date('d F Y H:i', strtotime($updated_date_raw)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Profil Saya - RS InsanMedika</title>
    
    <link href="<?= BASE_URL ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="<?= BASE_URL ?>css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 0.35rem;
            color: white;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .profile-avatar i {
            font-size: 60px;
            color: #667eea;
        }
        .info-card {
            transition: transform 0.3s;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
        .stat-box {
            background: #f8f9fc;
            padding: 1.5rem;
            border-radius: 0.35rem;
            text-align: center;
            border-left: 4px solid #4e73df;
        }
        .role-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
        .profile-detail-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e3e6f0; 
        }
        .profile-detail-item:last-child {
            border-bottom: none;
        }
        .profile-detail-item small {
            font-size: 0.9rem !important; 
        }
        .profile-detail-item strong {
            font-size: 1.15rem; 
            display: block;
            margin-top: 0.2rem;
        }
        .profile-detail-item i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }
        .full-height-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .full-height-body {
            flex-grow: 1;
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
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <strong>Berhasil!</strong> 
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php 
                        unset($_SESSION['success_message']);
                    endif; 
                    ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Error!</strong> 
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php 
                        unset($_SESSION['error_message']);
                    endif; 
                    ?>

                    <div class="profile-header">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <div class="profile-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <h2 class="mb-2"><?= htmlspecialchars($data_petugas['f_nama']) ?></h2>
                                <p class="mb-2">
                                    <span class="role-badge badge badge-light">
                                        <i class="fas fa-id-badge"></i> <?= htmlspecialchars($data_petugas['f_role']) ?>
                                    </span>
                                </p>
                                <?php if (!empty($data_petugas['f_unitkerja'])): ?>
                                <p class="mb-0 text-white-50">
                                    <i class="fas fa-hospital"></i> <?= htmlspecialchars($data_petugas['f_unitkerja']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow info-card full-height-card">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user-circle"></i> Informasi Profil
                                    </h6>
                                </div>
                                <div class="card-body full-height-body">
                                    <div class="row">
                                        <div class="col-12">
                                            
                                            <div class="profile-detail-item">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-user text-primary"></i> Nama Lengkap
                                                </small>
                                                <strong><?= htmlspecialchars($data_petugas['f_nama']) ?></strong>
                                            </div>
                                            
                                            <div class="profile-detail-item">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-fingerprint text-primary"></i> Username
                                                </small>
                                                <strong><?= htmlspecialchars($data_petugas['f_username']) ?></strong>
                                            </div>
                                            
                                            <div class="profile-detail-item">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-lock text-primary"></i> Password
                                                </small>
                                                <strong class="text-danger">
                                                    ******** <i class="fas fa-eye-slash ml-2 text-muted" title="Password tersembunyi. Gunakan tombol 'Ganti Password' untuk mengubahnya."></i>
                                                </strong>
                                            </div>
                                            
                                            <div class="profile-detail-item">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-hospital-user text-primary"></i> Unit Kerja
                                                </small>
                                                <strong><?= htmlspecialchars($data_petugas['f_unitkerja']) ?></strong>
                                            </div>
                                            
                                            <div class="profile-detail-item">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-shield-alt text-primary"></i> Role Sistem
                                                </small>
                                                <span class="badge badge-success badge-lg" style="font-size: 1.05rem; padding: 0.5rem;">
                                                    <?= htmlspecialchars($data_petugas['f_role']) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="profile-detail-item">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-calendar-plus text-primary"></i> Terdaftar Sejak
                                                </small>
                                                <strong><?= $stats['account_age'] ?></strong>
                                            </div>
                                            
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class="card-footer d-flex justify-content-end">
                                    <a href="update.php" class="btn btn-primary btn-icon-split mr-2">
                                        <span class="icon text-white-50"><i class="fas fa-edit"></i></span>
                                        <span class="text">Edit Profil</span>
                                    </a>
                                    <a href="gantipass.php" class="btn btn-warning btn-icon-split">
                                        <span class="icon text-white-50"><i class="fas fa-key"></i></span>
                                        <span class="text">Ganti Password</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-4">
                            <div class="card shadow info-card full-height-card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line"></i> Statistik Akun
                                    </h6>
                                </div>
                                <div class="card-body full-height-body d-flex flex-column justify-content-center">
                                    
                                    <div class="stat-box mb-3">
                                        <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                        <h6 class="text-muted mb-1">Akun Dibuat</h6>
                                        <strong><?= $stats['account_age'] ?></strong>
                                    </div>
                                    
                                    <div class="stat-box mb-3">
                                        <i class="fas fa-clock fa-2x text-success mb-2"></i>
                                        <h6 class="text-muted mb-1">Login Terakhir</h6>
                                        <strong><?= $stats['last_login'] ?></strong>
                                    </div>
                                    
                                    <?php if ($data_petugas['f_updated']): ?>
                                    <div class="stat-box">
                                        <i class="fas fa-edit fa-2x text-info mb-2"></i>
                                        <h6 class="text-muted mb-1">Update Terakhir</h6>
                                        <strong><?= $last_update_display ?></strong>
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
                        <span>Copyright &copy; RS InsanMedika <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="<?= BASE_URL ?>vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_URL ?>js/sb-admin-2.min.js"></script>
</body>
</html>