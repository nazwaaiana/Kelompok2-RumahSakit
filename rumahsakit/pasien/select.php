<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();
require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

if (!isset($_SESSION['petugas'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php"); 
    exit;
}

$jumlahdata = $db->rowCOUNT("SELECT f_idpasien FROM t_pasien");
$banyak = 10;
$halaman = ceil($jumlahdata / $banyak);

$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$mulai = ($p - 1) * $banyak;

$sql = "SELECT 
            p.*,
            (
                SELECT ri.f_waktumasuk 
                FROM t_rawatinap ri 
                WHERE ri.f_idpasien = p.f_idpasien 
                ORDER BY f_waktumasuk DESC LIMIT 1
            ) as f_waktumasuk_terakhir,
            (
                SELECT ri.f_waktukeluar 
                FROM t_rawatinap ri 
                WHERE ri.f_idpasien = p.f_idpasien 
                ORDER BY f_waktumasuk DESC LIMIT 1
            ) as f_waktukeluar_terakhir,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM t_rawatinap ri 
                    WHERE ri.f_idpasien = p.f_idpasien 
                    AND ri.f_waktukeluar IS NULL
                ) THEN 'Sedang RI'
                WHEN (
                    SELECT ri.f_waktukeluar 
                    FROM t_rawatinap ri 
                    WHERE ri.f_idpasien = p.f_idpasien 
                    ORDER BY f_waktumasuk DESC LIMIT 1
                ) IS NOT NULL THEN 'Sudah Keluar RI'
                ELSE 'Belum RI'
            END as status_ri,
            (SELECT f_idrawatinap FROM t_rawatinap ri WHERE ri.f_idpasien = p.f_idpasien AND ri.f_waktukeluar IS NULL ORDER BY f_waktumasuk DESC LIMIT 1) as f_idrawatinap_aktif
        FROM t_pasien p
        ORDER BY p.f_idpasien DESC 
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
    <title>Data Pasien - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .badge-kapasitas {
            font-size: 0.85rem;
            padding: 4px 8px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
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
        .table-hover tbody tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        .card {
            border: none;
            border-radius: 10px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px 10px 0 0 !important;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .status-sedang-ri { background-color: #e74a3b; }
        .status-sudah-keluar { background-color: #1cc88a; }
        .status-belum-ri { background-color: #858796; }
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
                    $alert_class = "alert-info";
                    $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : 'Proses selesai.';

                    switch ($_SESSION['flash']) {
                        case 'success':
                            $alert_class = "alert-success";
                            $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : "<strong>Berhasil!</strong> Data pasien berhasil ditambahkan.";
                            break;
                        case 'ri_success':
                            $alert_class = "alert-success";
                            $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : "<strong>Berhasil!</strong> Pasien berhasil masuk rawat inap.";
                            break;
                        case 'pelepasan_success':
                            $alert_class = "alert-success";
                            $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : "<strong>Berhasil!</strong> Pasien berhasil keluar rawat inap.";
                            break;
                        case 'del_success':
                            $alert_class = "alert-warning";
                            $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : "<strong>Berhasil!</strong> Data pasien berhasil dihapus.";
                            break;
                        case 'error':
                            $alert_class = "alert-danger";
                            $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : "<strong>Gagal!</strong> Terjadi kesalahan.";
                            break;
                    }

                    echo '
                    <div class="container-fluid">
                        <div class="alert '.$alert_class.' alert-dismissible fade show">
                            '.$message.'
                            <button class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    </div>';

                    unset($_SESSION['flash']);
                    unset($_SESSION['flash_message']);
                }
                ?>
                
                <div class="container-fluid">
                    <div class="page-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h1 class="h2 mb-2"><i class="fas fa-user"></i> Manajemen Pasien</h1>
                                    <p class="mb-0 opacity-75">Kelola data Pasien</p>
                                </div>
                                <a href="insert.php" class="btn btn-light btn-md shadow">
                                    <i class="fas fa-plus-circle mr-2"></i> Tambah Ruangan
                                </a>
                            </div>
                    </div>

                   

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-table"></i> Daftar Pasien & Status Rawat Inap
                            </h6>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr class="text-center">
                                            <th width="3%">No</th>
                                            <th width="8%">No. RM</th>
                                            <th width="15%">Nama Pasien</th>
                                            <th width="8%">Tgl Lahir</th>
                                            <th width="7%">JK</th>
                                            <th width="12%">No. Telp</th>
                                            <th width="12%">Waktu Masuk RI</th>
                                            <th width="12%">Waktu Keluar RI</th>
                                            <th width="10%">Status RI</th>
                                            <th width="7%">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                       <?php if (!empty($row)) { ?>
                                            <?php foreach ($row as $r) { 
                                                $status_ri = $r['status_ri'];
                                                $badge_class = '';
                                                $status_indicator = '';
                                                
                                                if ($status_ri == 'Sedang RI') {
                                                    $badge_class = 'badge-danger';
                                                    $status_indicator = 'status-sedang-ri';
                                                } elseif ($status_ri == 'Sudah Keluar RI') {
                                                    $badge_class = 'badge-success';
                                                    $status_indicator = 'status-sudah-keluar';
                                                } else { 
                                                    $badge_class = 'badge-secondary';
                                                    $status_indicator = 'status-belum-ri';
                                                }
                                            ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td>
                                                        <span class="badge badge-info badge-kapasitas">
                                                            <?= htmlspecialchars($r['f_norekmed']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($r['f_nama']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <small><?= date('d M Y', strtotime($r['f_tgllahir'])) ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($r['f_jnskelamin'] == 'Laki-laki'): ?>
                                                            <i class="fas fa-mars text-primary"></i> L
                                                        <?php else: ?>
                                                            <i class="fas fa-venus text-danger"></i> P
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-phone-alt text-muted"></i> 
                                                        <?= htmlspecialchars($r['f_notlp']) ?>
                                                    </td>
                                                    
                                                    <td>
                                                        <?php if ($r['f_waktumasuk_terakhir']): ?>
                                                            <small>
                                                                <i class="far fa-calendar-plus text-success"></i>
                                                                <?= date('d/m/Y', strtotime($r['f_waktumasuk_terakhir'])) ?>
                                                                <br>
                                                                <i class="far fa-clock text-muted"></i>
                                                                <?= date('H:i', strtotime($r['f_waktumasuk_terakhir'])) ?> WIB
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <td>
                                                        <?php if ($status_ri == 'Sudah Keluar RI'): ?>
                                                            <small>
                                                                <i class="far fa-calendar-check text-warning"></i>
                                                                <?= date('d/m/Y', strtotime($r['f_waktukeluar_terakhir'])) ?>
                                                                <br>
                                                                <i class="far fa-clock text-muted"></i>
                                                                <?= date('H:i', strtotime($r['f_waktukeluar_terakhir'])) ?> WIB
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <span class="badge <?= $badge_class ?> badge-kapasitas">
                                                            <span class="status-indicator <?= $status_indicator ?>"></span>
                                                            <?= $status_ri ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <a href="detail.php?id=<?= $r['f_idpasien']; ?>" 
                                                            class="btn btn-info btn-sm" 
                                                            title="Lihat Detail Pasien">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="10" class="text-center">
                                                    <div class="py-4">
                                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">Data pasien belum tersedia.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($p > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?p=<?= $p - 1 ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $halaman; $i++): ?>
                                            <li class="page-item <?= ($p == $i ? 'active' : '') ?>">
                                                <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($p < $halaman): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?p=<?= $p + 1 ?>">Next</a>
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

</body>

</html>