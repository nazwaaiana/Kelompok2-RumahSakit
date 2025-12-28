<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php"); 
    exit;
}

$jumlahdata = $db->rowCOUNT("SELECT f_idbed FROM t_tempattidur");
$banyak = 10;
$halaman = ceil($jumlahdata / $banyak);

$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$mulai = ($p - 1) * $banyak;

$sql = "SELECT 
            t_tempattidur.*, 
            t_ruangan.f_nama, 
            t_ruangan.f_kelas,
            t_ruangan.f_lantai
        FROM t_tempattidur 
        INNER JOIN t_ruangan ON t_tempattidur.f_idruangan = t_ruangan.f_idruangan
        ORDER BY t_tempattidur.f_idbed DESC 
        LIMIT $mulai, $banyak";

$row = $db->getALL($sql);
$no = 1 + $mulai;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Tempat Tidur - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .badge-kapasitas {
            font-size: 0.85rem;
            padding: 5px 10px;
        }
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
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
                            <h1 class="h2 mb-2"><i class="fas fa-bed"></i> Master Data Tempat Tidur</h1>
                            <p class="mb-0 opacity-75">Kelola data Tempat Tidur rumah sakit</p>
                        </div>
                        <a href="insert.php" class="btn btn-light btn-md shadow">
                            <i class="fas fa-plus-circle mr-2"></i> Tambah Ruangan
                        </a>
                    </div>
                </div>

                <?php
                    if (isset($_SESSION['flash'])) {
                        $flash_type = $_SESSION['flash'];
                        $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';

                        $alert_class = "alert-info";
                        $icon = "fa-info-circle";
                        $title = "Info!";

                        if ($flash_type === 'success' || $flash_type === 'success_update') {
                            $alert_class = "alert-success";
                            $icon = "fa-check-circle";
                            $title = "Berhasil!";
                            if(empty($message)) $message = "Data berhasil disimpan.";
                        } elseif ($flash_type === 'deleted') {
                            $alert_class = "alert-warning";
                            $icon = "fa-trash";
                            $title = "Dihapus!";
                            if(empty($message)) $message = "Data berhasil dihapus.";
                        } elseif (in_array($flash_type, ['error', 'error_update', 'delete_error'])) {
                            $alert_class = "alert-danger";
                            $icon = "fa-exclamation-triangle";
                            $title = "Gagal!";
                            if(empty($message)) $message = "Terjadi kesalahan pada sistem.";
                        }

                        echo '
                        <div class="alert ' . $alert_class . ' alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas ' . $icon . ' mr-2"></i> 
                            <strong>' . $title . '</strong> ' . htmlspecialchars($message) . '
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';

                        unset($_SESSION['flash']);
                        unset($_SESSION['flash_message']);
                    }
                ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-table"></i> Daftar Tempat Tidur
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr class="text-center">
                                        <th width="5%">No</th>
                                        <th width="12%">Nomor Bed</th>
                                        <th width="18%">Ruangan</th>
                                        <th width="12%">Kelas</th>
                                        <th width="8%">Lantai</th>
                                        <th width="12%">Status Fisik</th>
                                        <th width="13%">Created</th>
                                        <th width="13%">Updated</th>
                                        <th width="7%">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (empty($row)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                Belum ada data tempat tidur
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($row as $r): ?>
                                            <tr>
                                                <td class="text-center"><?= $no++ ?></td>
                                                <td>
                                                    <span class="badge badge-primary badge-kapasitas">
                                                        <i class="fas fa-bed"></i> <?= htmlspecialchars($r['f_nomorbed']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($r['f_nama']) ?></td>
                                                <td>
                                                    <span class="badge badge-info badge-kapasitas">
                                                        <?= htmlspecialchars($r['f_kelas']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($r['f_lantai']) ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-kapasitas
                                                        <?php 
                                                            if ($r['f_stsfisik'] == 'Aktif') echo 'badge-success';
                                                            elseif ($r['f_stsfisik'] == 'Nonaktif') echo 'badge-danger';
                                                            elseif ($r['f_stsfisik'] == 'Maintenance') echo 'badge-warning';
                                                            else echo 'badge-secondary';
                                                        ?>">
                                                        <i class="fas 
                                                            <?php 
                                                                if ($r['f_stsfisik'] == 'Aktif') echo 'fa-check-circle';
                                                                elseif ($r['f_stsfisik'] == 'Nonaktif') echo 'fa-ban';
                                                                elseif ($r['f_stsfisik'] == 'Maintenance') echo 'fa-tools';
                                                                else echo 'fa-question-circle';
                                                            ?>"></i>
                                                        <?= htmlspecialchars($r['f_stsfisik']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= date('d M Y H:i', strtotime($r['f_created'])) ?></small>
                                                </td>
                                                <td>
                                                    <small><?= date('d M Y H:i', strtotime($r['f_updated'])) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <a href="detail.php?id=<?= $r['f_idbed'] ?>" 
                                                       class="btn btn-info btn-sm"
                                                       title="Lihat Detail"> 
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <?php if ($halaman > 1): ?>
                                <nav>
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

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

</body>
</html>