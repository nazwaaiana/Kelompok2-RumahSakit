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

$jumlahdata = $db->rowCOUNT("SELECT f_idpetugas FROM t_petugas");
$banyak = 10;
$halaman = ceil($jumlahdata / $banyak);

$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$mulai = ($p - 1) * $banyak;

$sql = "SELECT f_idpetugas, f_nama, f_username, f_role, f_unitkerja, f_created, f_updated 
        FROM t_petugas 
        ORDER BY f_idpetugas DESC 
        LIMIT $mulai, $banyak";
$row = $db->getALL($sql);
$no = 1 + $mulai;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Data Petugas - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.25);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(102, 126, 234, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
            border: none;
            border-radius: 8px;
            padding: 0.4rem 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-info:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(54, 209, 220, 0.4);
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .page-link {
            color: #667eea;
            border-radius: 8px;
            margin: 0 3px;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            animation: slideInDown 0.5s ease;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .badge-role {
            padding: 0.35rem 0.8rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            display: inline-block;
            white-space: nowrap; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        

    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include '../sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include '../topbar.php'; ?>
                
                <?php
                if (isset($_SESSION['flash'])) {
                    $alert_class = '';
                    $icon = '';
                    $message = '';
                    
                    switch ($_SESSION['flash']) {
                        case 'success':
                        case 'success_update':
                            $alert_class = 'alert-success';
                            $icon = '<i class="fas fa-check-circle mr-2"></i>';
                            $message = '<strong>Berhasil!</strong> Data berhasil diproses.';
                            break;
                        case 'error':
                        case 'eror_update':
                        case 'delete_error':
                            $alert_class = 'alert-danger';
                            $icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
                            $message = '<strong>Gagal!</strong> Terjadi kesalahan saat memproses data.';
                            break;
                        case 'deleted':
                            $alert_class = 'alert-warning';
                            $icon = '<i class="fas fa-trash-alt mr-2"></i>';
                            $message = '<strong>Berhasil!</strong> Data berhasil dihapus.';
                            break;
                        default:
                            $alert_class = 'alert-info';
                            $icon = '<i class="fas fa-info-circle mr-2"></i>';
                            $message = $_SESSION['flash'];
                    }

                    echo '<div class="container-fluid">
                            <div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">
                                ' . $icon . $message . '
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                          </div>';

                    unset($_SESSION['flash']);
                }
                
                if (isset($_SESSION['flash_message'])) {
                    echo '<div class="container-fluid">' . $_SESSION['flash_message'] . '</div>';
                    unset($_SESSION['flash_message']);
                }
                ?>
                
                <div class="container-fluid">

                    <div class="page-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h1 class="h2 mb-2"><i class="fas fa-users-cog mr-3"></i>Master Data Petugas</h1>
                                <p class="mb-0 opacity-75">Kelola data petugas rumah sakit</p>
                            </div>
                            <a href="insert.php" class="btn btn-light btn-md shadow">
                                <i class="fas fa-plus-circle mr-2"></i> Tambah Petugas
                            </a>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-table mr-2"></i>Tabel Data Petugas
                            </h6>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr class="text-center">
                                            <th style="width: 5%;">No</th>
                                            <th style="width: 20%;">Nama</th>
                                            <th style="width: 15%;">Username</th>
                                            <th style="width: 15%;">Role</th>
                                            <th style="width: 15%;">Unit Kerja</th>
                                            <th style="width: 10%;">Created</th>
                                            <th style="width: 10%;">Updated</th>
                                            <th style="width: 10%;">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($row as $r) { 
                                            $badge_class = 'badge-admin';
                                            if ($r['f_role'] == 'Admisi') $badge_class = 'badge-admisi';
                                            elseif ($r['f_role'] == 'Perawat') $badge_class = 'badge-perawat';
                                            elseif ($r['f_role'] == 'Petugas Kebersihan') $badge_class = 'badge-kebersihan';
                                        ?>
                                            <tr>
                                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                                <td>
                                                    <i class="fas fa-user-circle mr-2 text-primary"></i>
                                                    <?= htmlspecialchars($r['f_nama']) ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-at mr-2 text-muted"></i>
                                                    <?= htmlspecialchars($r['f_username']) ?>
                                                </td>
                                                <td>
                                                    <span class="badge-role <?= $badge_class ?>">
                                                        <?= htmlspecialchars($r['f_role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-building mr-2 text-muted"></i>
                                                    <?= htmlspecialchars($r['f_unitkerja']) ?: '-' ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar-plus mr-1"></i>
                                                        <?= date('d/m/Y', strtotime($r['f_created'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar-check mr-1"></i>
                                                        <?= date('d/m/Y', strtotime($r['f_updated'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <a href="detail.php?id=<?= $r['f_idpetugas']; ?>" 
                                                       class="btn btn-info btn-sm"
                                                       data-toggle="tooltip" 
                                                       title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($p > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?p=<?= $p - 1 ?>">
                                                    <i class="fas fa-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $halaman; $i++): ?>
                                            <li class="page-item <?= ($p == $i ? 'active' : '') ?>">
                                                <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($p < $halaman): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?p=<?= $p + 1 ?>">
                                                    Next <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
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
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>

</body>

</html>