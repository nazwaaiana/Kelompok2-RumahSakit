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

$role = $_SESSION['role'] ?? '';

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$jumlahdata = $db->rowCOUNT("SELECT f_idruangan FROM t_ruangan");
$banyak = 10;
$halaman = ceil($jumlahdata / $banyak);
$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$mulai = ($p - 1) * $banyak;

$sql = "SELECT * FROM t_ruangan ORDER BY f_idruangan DESC LIMIT $mulai, $banyak";
$row = $db->getALL($sql);
$no = 1 + $mulai;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Ruangan - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .badge-kapasitas {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #fff;
        }
        
        .progress-mini {
            height: 8px;
            border-radius: 10px;
            margin-top: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            transition: width 0.6s ease;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
            border-left: 4px solid #0ea5e9;
            color: #0c4a6e;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
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
                $alert_class = "alert-info";
                $icon = '<i class="fas fa-info-circle mr-2"></i>';
                $message = $_SESSION['flash'];

                if ($_SESSION['flash'] === 'success') {
                    $alert_class = "alert-success";
                    $icon = '<i class="fas fa-check-circle mr-2"></i>';
                    $message = "<strong>Berhasil!</strong> Data ruangan berhasil ditambahkan.";
                } elseif ($_SESSION['flash'] === 'success_update') {
                    $alert_class = "alert-success";
                    $icon = '<i class="fas fa-check-circle mr-2"></i>';
                    $message = "<strong>Berhasil!</strong> Data ruangan berhasil diupdate.";
                } elseif ($_SESSION['flash'] === 'deleted') {
                    $alert_class = "alert-warning";
                    $icon = '<i class="fas fa-trash-alt mr-2"></i>';
                    $message = "<strong>Berhasil!</strong> Data ruangan berhasil dihapus.";
                } elseif (
                    $_SESSION['flash'] === 'error' ||
                    $_SESSION['flash'] === 'error_update' ||
                    $_SESSION['flash'] === 'delete_error'
                ) {
                    $alert_class = "alert-danger";
                    $icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
                    $message = "<strong>Gagal!</strong> Terjadi kesalahan.";
                }

                echo '
                <div class="container-fluid">
                    <div class="alert '.$alert_class.' alert-dismissible fade show">
                        '.$icon.$message.'
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                </div>';

                unset($_SESSION['flash']);
            }
            ?>

            <div class="container-fluid">

                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="h2 mb-2"><i class="fas fa-door-open mr-3"></i>Master Data Ruangan</h1>
                            <p class="mb-0 opacity-75">Kelola data ruangan rawat inap rumah sakit</p>
                        </div>
                        <a href="insert.php" class="btn btn-light btn-md shadow">
                            <i class="fas fa-plus-circle mr-2"></i> Tambah Ruangan
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Info:</strong> Kapasitas bed dihitung berdasarkan luas ruangan dan standar Kemenkes RI.
                    <a href="#" data-toggle="modal" data-target="#modalStandar" class="font-weight-bold">Lihat standar <i class="fas fa-external-link-alt"></i></a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-table mr-2"></i>Tabel Data Ruangan Rawat Inap
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">

                            <table class="table table-hover">
                                <thead>
                                    <tr class="text-center">
                                        <th width="5%">No</th>
                                        <th width="15%">Nama Ruangan</th>
                                        <th width="10%">Kelas</th>
                                        <th width="8%">Lantai</th>
                                        <th width="12%">Luas / Kapasitas</th>
                                        <th width="18%">Status Bed</th>
                                        <th width="12%">Utilisasi</th>
                                        <th width="12%">Updated</th>
                                        <th width="8%">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php foreach ($row as $r) { 
                                    $id = $r['f_idruangan'];

                                    $sql_stats = "
                                        SELECT
                                            COUNT(*) as total_bed,
                                            SUM(CASE WHEN f_stsfisik = 'Aktif' THEN 1 ELSE 0 END) as bed_aktif,
                                            SUM(CASE WHEN f_stsfisik = 'Nonaktif' THEN 1 ELSE 0 END) as bed_nonaktif,
                                            SUM(CASE WHEN f_stsfisik = 'Maintenance' THEN 1 ELSE 0 END) as bed_maintenance
                                        FROM t_tempattidur
                                        WHERE f_idruangan = $id
                                    ";
                                    $stats = $db->getITEM($sql_stats);
                                    $total_bed = (int)($stats['total_bed'] ?? 0);
                                    $bed_aktif = (int)($stats['bed_aktif'] ?? 0);

                                    $sql_terisi = "
                                        SELECT COUNT(*) as terisi
                                        FROM t_rawatinap ri
                                        JOIN t_tempattidur tt ON ri.f_idbed = tt.f_idbed
                                        WHERE tt.f_idruangan = $id
                                        AND ri.f_waktukeluar IS NULL
                                    ";
                                    $terisi_data = $db->getITEM($sql_terisi);
                                    $bed_terisi = (int)($terisi_data['terisi'] ?? 0);
                                    $bed_tersedia = $bed_aktif - $bed_terisi;

                                    $utilisasi_persen = $bed_aktif > 0 ? round(($bed_terisi / $bed_aktif) * 100, 1) : 0;
                            
                                    if ($utilisasi_persen >= 90) {
                                        $progress_color = 'bg-danger';
                                        $status_badge = '<span class="badge badge-danger">PENUH</span>';
                                    } elseif ($utilisasi_persen >= 70) {
                                        $progress_color = 'bg-warning';
                                        $status_badge = '<span class="badge badge-warning">HAMPIR PENUH</span>';
                                    } else {
                                        $progress_color = 'bg-success';
                                        $status_badge = '<span class="badge badge-success">TERSEDIA</span>';
                                    }
                                    
                                    $luas_ruangan = $r['f_luasruangan'] ?? null;
                                    $kapasitas_maks = (int)($r['f_kapasitasmaks'] ?? 0);
                                    $kapasitas_manual = (int)($r['f_kapasitas'] ?? 0);
                                ?>
                                    <tr>
                                        <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                        <td>
                                            <i class="fas fa-door-open mr-2 text-primary"></i>
                                            <strong><?= htmlspecialchars($r['f_nama']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info badge-kapasitas">
                                                <?= htmlspecialchars($r['f_kelas']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <i class="fas fa-layer-group text-muted"></i> <?= htmlspecialchars($r['f_lantai']) ?>
                                        </td>
                                        <td>
                                            <?php if ($luas_ruangan): ?>
                                                <div>
                                                    <i class="fas fa-ruler-combined text-primary"></i> 
                                                    <strong><?= number_format($luas_ruangan, 1) ?> m²</strong>
                                                </div>
                                                <small class="text-muted">
                                                    Maks: <?= $kapasitas_maks ?> bed
                                                    <?php if ($total_bed > $kapasitas_maks): ?>
                                                        <i class="fas fa-exclamation-triangle text-warning" 
                                                           title="Bed terdaftar melebihi kapasitas standar"></i>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <div>
                                                    <i class="fas fa-users text-secondary"></i> 
                                                    <strong><?= $kapasitas_manual ?> bed</strong>
                                                </div>
                                                <small class="text-muted">Manual</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <span class="badge badge-danger badge-kapasitas">
                                                    <i class="fas fa-bed"></i> Terisi: <?= $bed_terisi ?>
                                                </span>
                                                <span class="badge badge-success badge-kapasitas">
                                                    <i class="fas fa-check"></i> Tersedia: <?= $bed_tersedia ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                Total: <?= $total_bed ?> | Aktif: <?= $bed_aktif ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <strong><?= $utilisasi_persen ?>%</strong>
                                                <?= $status_badge ?>
                                            </div>
                                            <div class="progress progress-mini">
                                                <div class="progress-bar <?= $progress_color ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $utilisasi_persen ?>%" 
                                                     aria-valuenow="<?= $utilisasi_persen ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-check mr-1"></i>
                                                <?= date('d/m/Y', strtotime($r['f_updated'])) ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="detail.php?id=<?= $id ?>" 
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

                        </div>

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
        
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; 2025 RS InsanMedika | Kelompok Dua</span>
                </div>
            </div>
        </footer>

    </div>

</div>

<div class="modal fade" id="modalStandar" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title">
                    <i class="fas fa-book mr-2"></i>Standar Luas Ruangan per Bed (Kemenkes RI)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php
                $sql_standar = "SELECT * FROM t_standar_luasbed ORDER BY f_luasmin ASC";
                $standar_list = $db->getALL($sql_standar);
                
                if ($standar_list):
                ?>
                    <table class="table table-bordered">
                        <thead class="font-weight-bold text-white">
                            <tr>
                                <th>Kelas Ruangan</th>
                                <th>Luas Min/Max per Bed</th>
                                <th>Jarak per Bed</th>
                                <th>Faktor Efisiensi</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standar_list as $std): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($std['f_kelas']) ?></strong></td>
                                    <td>
                                        <?= $std['f_luasmin'] ?> - <?= $std['f_luasmaks'] ?> m²
                                    </td>
                                    <td>
                                        <?= $std['f_jarakminbed'] ?> m
                                    </td>
                                    <td><?= ($std['f_faktorefisiensi'] * 100) ?>%</td>
                                    <td><small><?= htmlspecialchars($std['f_keterangan']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Formula Perhitungan:</strong><br>
                        <code>Kapasitas Maksimal = (Luas Ruangan × Faktor Efisiensi) ÷ Luas per Bed</code>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Data standar belum tersedia. Silakan hubungi administrator.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<!-- <script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script> -->


</body