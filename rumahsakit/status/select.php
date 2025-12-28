<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

if (!isset($_SESSION['petugas']) || !isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$jumlahdata = $db->rowCOUNT("SELECT f_idbed FROM t_tempattidur");
$banyak = 10;
$halaman = ceil($jumlahdata / $banyak);

$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$p = max(1, min($p, $halaman));
$mulai = ($p - 1) * $banyak;

$sql = "SELECT
            tt.f_idbed,
            tt.f_nomorbed,
            tt.f_stsfisik AS stsfisik_bed,
            r.f_nama AS nama_ruangan,
            r.f_kelas AS kelas_ruangan,
            ri.f_idrawatinap,
            p.f_nama AS nama_pasien_ri,
            ri.f_waktumasuk AS waktu_masuk_ri,
            bs.f_idbedsts,
            bs.f_sts AS status_non_ri,
            bs.f_waktumulai AS waktu_status_non_ri_mulai,
            bs.f_keterangan AS keterangan_non_ri
        FROM
            t_tempattidur tt
        JOIN
            t_ruangan r ON tt.f_idruangan = r.f_idruangan
        LEFT JOIN
            t_rawatinap ri ON tt.f_idbed = ri.f_idbed AND ri.f_waktukeluar IS NULL
        LEFT JOIN
            t_pasien p ON ri.f_idpasien = p.f_idpasien
        LEFT JOIN
            t_bedstatus bs ON tt.f_idbed = bs.f_idbed AND bs.f_waktuselesai IS NULL
        WHERE 
            tt.f_stsfisik != 'Nonaktif'
        ORDER BY
            r.f_nama ASC, tt.f_nomorbed ASC
        LIMIT $mulai, $banyak";

$row = $db->getALL($sql);
$no = 1 + $mulai;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Bed Status - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px 10px 0 0 !important;
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
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
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
        
        .status-terisi { background-color: #e74a3b; }
        .status-pembersihan { background-color: #36b9cc; }
        .status-siap { background-color: #1cc88a; }
        .status-maintenance { background-color: #f6c23e; }
        .status-kosong { background-color: #858796; }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .page-link {
            color: #667eea;
            border-radius: 8px;
            margin: 0 3px;
        }
        
        .page-link:hover {
            background-color: #667eea;
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
        
        .status-card {
            background: white;
            border-radius: 8px;
            padding: 0.5rem;
            border-left: 3px solid #667eea;
        }
        
        .btn-sm {
            border-radius: 8px;
            padding: 0.4rem 1rem;
            font-weight: 600;
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

                <?php
                if (isset($_SESSION['flash'])) {
                    $alert_class = "alert-info";
                    $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : 'Proses selesai.';

                    switch ($_SESSION['flash']) {
                        case 'success':
                            $alert_class = "alert-success";
                            break;
                        case 'error':
                            $alert_class = "alert-danger";
                            break;
                        case 'warning':
                            $alert_class = "alert-warning";
                            break;
                    }

                    echo '<div class="alert '.$alert_class.' alert-dismissible fade show">
                            '.$message.'
                            <button class="close" data-dismiss="alert"><span>&times;</span></button>
                          </div>';

                    unset($_SESSION['flash']);
                    unset($_SESSION['flash_message']);
                }
                ?>

                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="h2 mb-2">
                                <i class="fas fa-bed mr-2"></i>Manajemen Status Bed
                            </h1>
                            <p class="mb-0 opacity-75">Monitor dan kelola status operasional tempat tidur</p>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-table mr-2"></i>Status Tempat Tidur Real-Time
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr class="text-center">
                                        <th width="3%">No</th>
                                        <th width="15%">Ruangan</th>
                                        <th width="8%">Kelas</th>
                                        <th width="8%">No. Bed</th>
                                        <th width="10%">Status Fisik</th>
                                        <th width="12%">Status Operasional</th>
                                        <th width="20%">Keterangan</th>
                                        <th width="12%">Waktu Status</th>
                                        <th width="12%">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    if (empty($row)) {
                                        echo '<tr><td colspan="9" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">Data tempat tidur belum tersedia</p>
                                                </div>
                                              </td></tr>';
                                    }

                                    foreach ($row as $r) {
                                        $status_final = 'Kosong';
                                        $keterangan_status = 'Siap diisi pasien';
                                        $badge_class = 'badge-secondary';
                                        $status_indicator = 'status-kosong';
                                        $link_aksi_utama = 'update.php?id=' . $r['f_idbed'];
                                        $tombol_aksi = '<i class="fas fa-wrench"></i> Atur Status';
                                        $tombol_class = 'btn btn-warning btn-sm';
                                        $waktu_status = '-';

                                        $status_fisik = htmlspecialchars($r['stsfisik_bed']);
                                        
                                        if ($status_fisik == 'Maintenance') {
                                            $status_final = 'Maintenance';
                                            $badge_class = 'badge-dark';
                                            $status_indicator = 'status-maintenance';
                                            $keterangan_status = 'Bed rusak/perbaikan';
                                            
                                            if (!empty($r['f_idbedsts'])) {
                                                $link_aksi_utama = 'finish.php?id=' . $r['f_idbedsts'];
                                                $tombol_aksi = '<i class="fas fa-check-circle"></i> Selesaikan';
                                                $tombol_class = 'btn btn-success btn-sm';
                                            } else {
                                                $link_aksi_utama = '../tempattidur/detail.php?id=' . $r['f_idbed'];
                                                $tombol_aksi = '<i class="fas fa-search"></i> Detail';
                                                $tombol_class = 'btn btn-dark btn-sm';
                                            }
                                        }
                                        
                                        if (!empty($r['f_idrawatinap'])) {
                                            $status_final = 'Terisi (RI)';
                                            $keterangan_status = htmlspecialchars($r['nama_pasien_ri']);
                                            $badge_class = 'badge-danger';
                                            $status_indicator = 'status-terisi';
                                            $link_aksi_utama = '../rawatinap/detail.php?id=' . $r['f_idrawatinap'];
                                            $tombol_aksi = '<i class="fas fa-procedures"></i> Lihat RI';
                                            $tombol_class = 'btn btn-info btn-sm';
                                            if (!empty($r['waktu_masuk_ri'])) {
                                                $waktu_status = date('d/m/Y H:i', strtotime($r['waktu_masuk_ri']));
                                            }
                                        } 
                                        elseif (!empty($r['status_non_ri'])) {
                                            $status_final = htmlspecialchars($r['status_non_ri']);
                                            $id_bedsts = $r['f_idbedsts'];

                                            if ($status_final == 'Maintenance') {
                                                $badge_class = 'badge-warning';
                                                $status_indicator = 'status-maintenance';
                                                $keterangan_status = 'Maintenance operasional';
                                                $link_aksi_utama = 'finish.php?id=' . $id_bedsts;
                                                $tombol_aksi = '<i class="fas fa-check-circle"></i> Selesaikan';
                                                $tombol_class = 'btn btn-success btn-sm';
                                            } elseif ($status_final == 'Pembersihan' || $status_final == 'Kotor') {
                                                $badge_class = 'badge-info';
                                                $status_indicator = 'status-pembersihan';
                                                $keterangan_status = 'Perlu dibersihkan';
                                                $link_aksi_utama = '../kebersihan/select.php';
                                                $tombol_aksi = '<i class="fas fa-broom"></i> Kebersihan';
                                                $tombol_class = 'btn btn-info btn-sm';
                                            } else { 
                                                // Status Siap atau Kosong - bisa diubah statusnya
                                                $badge_class = 'badge-success';
                                                $status_indicator = 'status-siap';
                                                $keterangan_status = 'Siap digunakan';
                                                $link_aksi_utama = 'update.php?id=' . $r['f_idbed'];
                                                $tombol_aksi = '<i class="fas fa-wrench"></i> Atur Status';
                                                $tombol_class = 'btn btn-warning btn-sm';
                                            }

                                            if (!empty($r['waktu_status_non_ri_mulai'])) {
                                                $waktu_status = date('d/m/Y H:i', strtotime($r['waktu_status_non_ri_mulai']));
                                            }
                                            if (!empty($r['keterangan_non_ri'])) {
                                                $keterangan_status = htmlspecialchars($r['keterangan_non_ri']);
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                            <td>
                                                <i class="fas fa-door-open mr-2 text-primary"></i>
                                                <strong><?= htmlspecialchars($r['nama_ruangan']) ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= htmlspecialchars($r['kelas_ruangan']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary badge-kapasitas">
                                                    <i class="fas fa-bed"></i> <?= htmlspecialchars($r['f_nomorbed']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= $status_fisik ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badge_class ?> badge-kapasitas">
                                                    <span class="status-indicator <?= $status_indicator ?>"></span>
                                                    <?= $status_final ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <small><?= $keterangan_status ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="far fa-clock text-muted"></i>
                                                    <?= $waktu_status ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= $link_aksi_utama ?>" 
                                                   class="<?= $tombol_class ?>">
                                                    <?= $tombol_aksi ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>

                            <?php if ($halaman > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
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
                            <?php endif; ?>
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

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>

</body>
</html>