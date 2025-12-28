<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']); 

if (!isset($_SESSION['petugas'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}

$id_petugas = (int)$_GET['id'];

$sql = "SELECT * FROM t_petugas WHERE f_idpetugas = $id_petugas";
$row = $db->getALL($sql);

if (!$row) {
    $_SESSION['flash'] = 'Data petugas tidak ditemukan.';
    header("Location: select.php");
    exit;
}

$petugas = $row[0];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Data Petugas - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            border: none;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .detail-table {
            margin: 0;
        }
        
        .detail-table tr {
            border-bottom: 1px solid #e3e6f0;
            transition: all 0.3s ease;
        }
        
        .detail-table tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
        }
        
        .detail-table th {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            color: #667eea;
            font-weight: 700;
            padding: 1rem;
            width: 30%;
            border-right: 3px solid #667eea;
        }
        
        .detail-table td {
            padding: 1rem;
            color: #5a5c69;
            font-weight: 500;
        }
        
        .detail-table tr:last-child {
            border-bottom: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(246, 185, 59, 0.4);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(238, 9, 121, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #858796 0%, #60616f 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(133, 135, 150, 0.4);
        }
        
        .badge-role {
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .badge-admisi {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .badge-perawat {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .badge-kebersihan {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .info-card .avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .info-card .avatar i {
            font-size: 4rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        .icon-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-box {
            background: #f8f9fc;
            padding: 0.75rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 0.85rem;
            color: #5a5c69;
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

                    <div class="page-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h1 class="h2 mb-2">
                                    <i class="fas fa-id-card mr-3"></i>Detail Data Petugas
                                </h1>
                                <p class="mb-0 opacity-75">Informasi lengkap petugas rumah sakit</p>
                            </div>
                            <a href="select.php" class="btn btn-light btn-md shadow">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali
                            </a>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="info-card">
                                <div class="avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <h3 class="mb-2"><?= htmlspecialchars($petugas['f_nama']) ?></h3>
                                <p class="mb-3 opacity-75">
                                    <i class="fas fa-at mr-2"></i><?= htmlspecialchars($petugas['f_username']) ?>
                                </p>
                                <?php
                                    $badge_class = 'badge-admin';
                                    if ($petugas['f_role'] == 'Admisi') $badge_class = 'badge-admisi';
                                    elseif ($petugas['f_role'] == 'Perawat') $badge_class = 'badge-perawat';
                                    elseif ($petugas['f_role'] == 'Petugas Kebersihan') $badge_class = 'badge-kebersihan';
                                ?>
                                <span class="badge-role <?= $badge_class ?>">
                                    <i class="fas fa-user-shield mr-2"></i><?= htmlspecialchars($petugas['f_role']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-white">
                                        <i class="fas fa-info-circle mr-2"></i>Informasi Detail Petugas
                                    </h6>
                                </div>

                                <div class="card-body p-0">
                                    <table class="table detail-table mb-0">
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="fas fa-hashtag"></i>
                                                    ID Petugas
                                                </span>
                                            </th>
                                            <td><strong><?= $petugas['f_idpetugas'] ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="fas fa-user"></i>
                                                    Nama Lengkap
                                                </span>
                                            </th>
                                            <td><?= htmlspecialchars($petugas['f_nama']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="fas fa-user-tag"></i>
                                                    Username
                                                </span>
                                            </th>
                                            <td><?= htmlspecialchars($petugas['f_username']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="fas fa-key"></i>
                                                    Password (Hash)
                                                </span>
                                            </th>
                                            <td>
                                                <div class="password-box">
                                                    <?= htmlspecialchars(substr($petugas['f_password'], 0, 50)) ?>...
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-shield-alt mr-1"></i>
                                                    Password terenkripsi dengan algoritma bcrypt
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="fas fa-user-shield"></i>
                                                    Role (Hak Akses)
                                                </span>
                                            </th>
                                            <td>
                                                <?php
                                                    $badge_class = 'badge-admin';
                                                    if ($petugas['f_role'] == 'Admisi') $badge_class = 'badge-admisi';
                                                    elseif ($petugas['f_role'] == 'Perawat') $badge_class = 'badge-perawat';
                                                    elseif ($petugas['f_role'] == 'Petugas Kebersihan') $badge_class = 'badge-kebersihan';
                                                ?>
                                                <span class="badge-role <?= $badge_class ?>" style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                                                    <?= htmlspecialchars($petugas['f_role']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="fas fa-building"></i>
                                                    Unit Kerja
                                                </span>
                                            </th>
                                            <td>
                                                <?php if (!empty($petugas['f_unitkerja'])): ?>
                                                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                                                    <?= htmlspecialchars($petugas['f_unitkerja']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="fas fa-minus-circle mr-2"></i>Tidak ada unit kerja
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="far fa-calendar-plus"></i>
                                                    Tanggal Dibuat
                                                </span>
                                            </th>
                                            <td>
                                                <i class="far fa-clock mr-2 text-success"></i>
                                                <?= date('l, d F Y - H:i:s', strtotime($petugas['f_created'])) ?> WIB
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="icon-label">
                                                    <i class="far fa-calendar-check"></i>
                                                    Terakhir Diperbarui
                                                </span>
                                            </th>
                                            <td>
                                                <i class="far fa-clock mr-2 text-warning"></i>
                                                <?= date('l, d F Y - H:i:s', strtotime($petugas['f_updated'])) ?> WIB
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card shadow">
                                <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <h6 class="m-0 font-weight-bold text-white">
                                        <i class="fas fa-cog mr-2"></i>Aksi & Pengaturan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Pilih aksi yang ingin Anda lakukan terhadap data petugas ini
                                    </p>
                                    
                                    <div class="action-buttons">
                                        <a href="update.php?id=<?= $petugas['f_idpetugas']; ?>" 
                                           class="btn btn-warning btn-md">
                                            <i class="fas fa-edit mr-2"></i> Edit / Update Data
                                        </a>
                                        
                                        <a href="delete.php?id=<?= $petugas['f_idpetugas']; ?>" 
                                            class="btn btn-danger btn-md"
                                            onclick="return confirm('⚠️ PERHATIAN!\n\nApakah Anda yakin ingin menghapus data petugas:\n\nNama:<?= htmlspecialchars($petugas['f_nama']) ?>\nUsername: <?= htmlspecialchars($petugas['f_username']) ?>\n\nData yang dihapus tidak dapat dikembalikan!');">
                                            <i class="fas fa-trash-alt mr-2"></i> Hapus Data
                                        </a>
                                        
                                        <a href="select.php" class="btn btn-secondary btn-md">
                                            <i class="fas fa-list mr-2"></i> Lihat Semua Data
                                        </a>
                                    </div>
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
    
    <script>
        $(document).ready(function() {
            $('.detail-table tr').each(function(i) {
                $(this).delay(100 * i).fadeIn(500);
            });
        });
    </script>

</body>

</html>