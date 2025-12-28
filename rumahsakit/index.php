<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once "dbcontroller.php";
require_once 'config.php';
$db = new dbcontroller();

if (!isset($_SESSION['idpetugas'])) {
    header("location:login.php");
    exit;
}

if (isset($_GET['log'])) {
    session_unset();
    session_destroy();
    header("location:login.php");
    exit;
}

$role_petugas = $_SESSION['role'] ?? 'Guest';
$nama_petugas = $_SESSION['petugas'] ?? 'Guest';

if ($role_petugas === 'Petugas Kebersihan') {
    if (file_exists('dashboard_kebersihan.php')) {
        header("Location: dashboard_kebersihan.php");
        exit;
    }
}

function hasRole($allowed_roles) {
    global $role_petugas;
    return in_array($role_petugas, $allowed_roles);
}

$filter_ruangan = isset($_GET['ruangan']) ? (int)$_GET['ruangan'] : 0;
$filter_kelas = isset($_GET['kelas']) ? $db->escapeString(trim(strip_tags($_GET['kelas']))) : '';
$filter_status = isset($_GET['status']) ? trim(strip_tags($_GET['status'])) : '';

$sql_ruangan = "SELECT f_idruangan, f_nama, f_kelas FROM t_ruangan ORDER BY f_nama";
$data_ruangan = $db->getLIST($sql_ruangan);

$sql_kelas = "SELECT DISTINCT f_kelas FROM t_ruangan ORDER BY 
              CASE f_kelas 
                WHEN 'VVIP' THEN 1 
                WHEN 'VIP' THEN 2 
                WHEN 'Kelas 1' THEN 3 
                WHEN 'Kelas 2' THEN 4 
                WHEN 'Kelas 3' THEN 5 
              END";
$data_kelas = $db->getLIST($sql_kelas);

function loadDataBed($db, $filter_ruangan, $filter_kelas, $filter_status) {
    $where_clause = "WHERE 1=1";
    
    if ($filter_ruangan > 0) {
        $where_clause .= " AND r.f_idruangan = $filter_ruangan";
    }
    
    if (!empty($filter_kelas)) {
        $where_clause .= " AND r.f_kelas = '$filter_kelas'";
    }

    $sql = "SELECT 
                tt.f_idbed,
                tt.f_nomorbed,
                tt.f_stsfisik,
                r.f_idruangan,
                r.f_nama AS nama_ruangan,
                r.f_kelas,
                r.f_lantai,
                r.f_kapasitas,
                bs.f_sts AS status_bedstatus,
                bs.f_waktumulai AS waktu_status,
                bs.f_keterangan AS keterangan_status,
                ri.f_idrawatinap,
                p.f_nama AS nama_pasien
              FROM t_tempattidur tt
              JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
              LEFT JOIN t_bedstatus bs ON tt.f_idbed = bs.f_idbed AND bs.f_waktuselesai IS NULL
              LEFT JOIN t_rawatinap ri ON tt.f_idbed = ri.f_idbed AND ri.f_waktukeluar IS NULL
              LEFT JOIN t_pasien p ON ri.f_idpasien = p.f_idpasien
              $where_clause
              ORDER BY r.f_nama, tt.f_nomorbed";
    
    $data = $db->getLIST($sql);
    
    if ($data) {
        $filtered_data = [];
        foreach ($data as $bed) {
            $ada_pasien = !empty($bed['f_idrawatinap']);
            $status_fisik = $bed['f_stsfisik'];
            $status_bedstatus = $bed['status_bedstatus'];
            $status_final = '';
            
            if ($status_fisik == 'Nonaktif') {
                $status_final = 'Nonaktif'; 
            } elseif ($status_fisik == 'Maintenance') {
                $status_final = 'Maintenance (Fisik)';
            } elseif ($ada_pasien) {
                $status_final = 'Terisi';
            } elseif (!empty($status_bedstatus)) {
                $status_final = $status_bedstatus; 
            } else {
                $status_final = 'Kosong';
            }

            $bed['status_final'] = $status_final;
            $bed['ada_pasien'] = $ada_pasien;

            if (empty($filter_status) || $status_final == $filter_status) {
                $filtered_data[] = $bed;
            }
        }
        return $filtered_data;
    }
    
    return [];
}

$data_beds = loadDataBed($db, $filter_ruangan, $filter_kelas, $filter_status);

$beds_by_ruangan = [];
if (!empty($data_beds)) {
    foreach ($data_beds as $bed) {
        $ruangan_key = $bed['f_idruangan'];
        if (!isset($beds_by_ruangan[$ruangan_key])) {
            $beds_by_ruangan[$ruangan_key] = [
                'info' => [
                    'nama' => $bed['nama_ruangan'],
                    'kelas' => $bed['f_kelas'],
                    'lantai' => $bed['f_lantai'],
                    'kapasitas' => $bed['f_kapasitas']
                ],
                'beds' => []
            ];
        }
        $beds_by_ruangan[$ruangan_key]['beds'][] = $bed;
    }
}

$sql_stats = "
SELECT
    COUNT(tt.f_idbed) as total_beds,
    SUM(CASE WHEN ri.f_idrawatinap IS NOT NULL THEN 1 ELSE 0 END) as bed_terisi,
    SUM(CASE 
        WHEN ri.f_idrawatinap IS NULL 
        AND tt.f_stsfisik = 'Aktif'
        AND (bs.f_sts IS NULL OR bs.f_sts IN ('Kosong', 'Siap'))
        THEN 1 ELSE 0 
    END) as bed_tersedia,
    SUM(CASE 
        WHEN ri.f_idrawatinap IS NULL 
        AND tt.f_stsfisik = 'Aktif'
        AND bs.f_sts IN ('Kotor', 'Pembersihan') 
        THEN 1 ELSE 0 
    END) as bed_perlu_bersih,
    SUM(CASE 
        WHEN tt.f_stsfisik = 'Maintenance'
        OR (tt.f_stsfisik = 'Aktif' AND bs.f_sts = 'Maintenance')
        THEN 1 ELSE 0 
    END) as bed_maintenance
FROM t_tempattidur tt
LEFT JOIN t_bedstatus bs ON tt.f_idbed = bs.f_idbed AND bs.f_waktuselesai IS NULL
LEFT JOIN t_rawatinap ri ON tt.f_idbed = ri.f_idbed AND ri.f_waktukeluar IS NULL
WHERE tt.f_stsfisik != 'Nonaktif';
";

$stats = $db->getITEM($sql_stats);
if ($stats) {
    $total_beds = $stats['total_beds'];
    $bed_terisi = $stats['bed_terisi'];
    $bed_tersedia = $stats['bed_tersedia'];
    $bed_perlu_bersih = $stats['bed_perlu_bersih'];
    $bed_maintenance = $stats['bed_maintenance'];
} else {
    $total_beds = $bed_terisi = $bed_tersedia = $bed_perlu_bersih = $bed_maintenance = 0;
}

$okupansi_persen = $total_beds > 0 ? round(($bed_terisi / $total_beds) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard - RS InsanMedika</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        #wrapper {
            background: linear-gradient(180deg, #eef6fc 0%, #f6fbff 60%, #ffffff 100%);
        }

        .sidebar {
            background-color: #6fb6e9 !important;
        }

        .sidebar .nav-item .nav-link,
        .sidebar .sidebar-brand {
            color: #ffffff !important;
        }

        .sidebar .nav-item .nav-link:hover {
            background-color: #5aa7df !important;
        }

        .bed-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #e3e6f0;
        }

        .bed-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }

        .bed-status-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            min-width: 75px;
            text-align: center;
        }
    
        .status-Terisi { background-color: #e74a3b; color: white; }
        .status-Kosong { background-color: #1cc88a; color: white; }
        .status-Siap { background-color: #36b9cc; color: white; }
        .status-Kotor { background-color: #f6c23e; color: black; }
        .status-Pembersihan { background-color: #fd7e14; color: white; }
        .status-Maintenance { background-color: #858796; color: white; }
        .status-MaintenanceFisik { background-color: #5a5c69; color: white; }
        .status-Nonaktif { background-color: #3a3b45; color: white; }

        .filter-section {
            background: #ffffff !important;
            border: 1px solid #e2eef9;
            padding: 1.5rem;
            border-radius: 0.35rem;
            margin-bottom: 1.5rem;
        }

        .ruangan-section {
            margin-bottom: 2rem;
        }

        .ruangan-header {
            background: #4fa1dd !important;
            color: white;
            padding: 1rem;
            border-radius: 0.35rem 0.35rem 0 0;
            margin-bottom: 1rem;
        }

        .ruangan-section .card-body {
            border: 1px solid #e3e6f0;
            border-top: none;
            border-radius: 0 0 0.35rem 0.35rem;
        }

        .bed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
        }

        .card.border-left-primary {
            border-left-color: #6fb6e9 !important;
        }

        .card.border-left-danger {
            border-left-color: #f26b6b !important;
        }

        .card.border-left-success {
            border-left-color: #5fd3a2 !important;
        }

        .card.border-left-warning {
            border-left-color: #f6c23e !important;
        }

        .card.border-left-info {
            border-left-color: #6fc7e9 !important;
        }

        .bed-card {
            border: 1px solid #e1ecf6;
        }

        #content {
            background: linear-gradient(180deg, #eef6fc 0%, #f6fbff 60%, #ffffff 100%) !important;
        }

        .card,
        .filter-section {
            background: rgba(255,255,255,0.97) !important;
        }
    </style>

</head>

<body id="page-top">

    <div id="wrapper">

        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php include 'topbar.php'; ?>

                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-line"></i> Dashboard Monitoring Tempat Tidur
                        </h1>
                        <button class="btn btn-primary btn-sm" onclick="window.location='index.php'">
                            <i class="fas fa-sync-alt"></i> Reset Filter
                        </button>
                    </div>

                    <div class="row">

                        <div class="col-xl col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Tempat Tidur</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_beds ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bed fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Bed Terisi</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $bed_terisi ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-injured fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Bed Siap / Kosong</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $bed_tersedia ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Perlu Dibersihkan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $bed_perlu_bersih ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-broom fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Okupansi</div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?= $okupansi_persen ?>%</div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: <?= $okupansi_persen ?>%" aria-valuenow="<?= $okupansi_persen ?>" 
                                                            aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($bed_maintenance > 0): ?>
                    <div class="alert alert-secondary alert-dismissible fade show">
                        <i class="fas fa-tools"></i> <strong>Info:</strong> Terdapat <strong><?= $bed_maintenance ?></strong> bed yang sedang dalam maintenance (tidak tersedia untuk rawat inap).
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    <?php endif; ?>

                    <div class="filter-section">
                        <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Pencarian</h5>
                        <form method="GET" action="index.php" class="row">
                            <div class="col-md-3 mb-3">
                                <label>Ruangan:</label>
                                <select name="ruangan" class="form-control">
                                    <option value="0">-- Semua Ruangan --</option>
                                    <?php if ($data_ruangan): foreach ($data_ruangan as $r): ?>
                                        <option value="<?= $r['f_idruangan'] ?>" <?= $filter_ruangan == $r['f_idruangan'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['f_nama']) ?> (<?= htmlspecialchars($r['f_kelas']) ?>)
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Kelas:</label>
                                <select name="kelas" class="form-control">
                                    <option value="">-- Semua Kelas --</option>
                                    <?php if ($data_kelas): foreach ($data_kelas as $k): ?>
                                        <option value="<?= htmlspecialchars($k['f_kelas']) ?>" <?= $filter_kelas == $k['f_kelas'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['f_kelas']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Status:</label>
                                <select name="status" class="form-control">
                                    <option value="">-- Semua Status --</option>
                                    <option value="Terisi" <?= $filter_status == 'Terisi' ? 'selected' : '' ?>>Terisi (Pasien Ada)</option>
                                    <option value="Kosong" <?= $filter_status == 'Kosong' ? 'selected' : '' ?>>Kosong</option>
                                    <option value="Siap" <?= $filter_status == 'Siap' ? 'selected' : '' ?>>Siap</option>
                                    <option value="Kotor" <?= $filter_status == 'Kotor' ? 'selected' : '' ?>>Kotor</option>
                                    <option value="Pembersihan" <?= $filter_status == 'Pembersihan' ? 'selected' : '' ?>>Pembersihan</option>
                                    <option value="Maintenance (Fisik)" <?= $filter_status == 'Maintenance (Fisik)' ? 'selected' : '' ?>>Maintenance</option>
                                    <option value="Nonaktif" <?= $filter_status == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <h5 class="mb-3"><i class="fas fa-th-list"></i> Keterangan Status</h5>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color status-Terisi"></div>
                            <span>Terisi</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color status-Kosong"></div>
                            <span>Kosong</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color status-Siap"></div>
                            <span>Siap</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color status-Kotor"></div>
                            <span>Kotor</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color status-Pembersihan"></div>
                            <span>Pembersihan</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color status-Maintenance"></div>
                            <span>Maintenance</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color status-Nonaktif"></div>
                            <span>Nonaktif</span>
                        </div>
                    </div>

                    <?php if (empty($beds_by_ruangan)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>Tidak Ada Data Bed</h5>
                            <p>Tidak ditemukan data tempat tidur sesuai filter yang dipilih.</p>
                            <?php if (!empty($filter_status) || $filter_ruangan > 0 || !empty($filter_kelas)): ?>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Reset Filter
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($beds_by_ruangan as $id_ruangan => $ruangan_data): ?>
                            <div class="ruangan-section card shadow mb-4">
                                <div class="ruangan-header card-header py-3 d-flex align-items-center justify-content-between">
                                    <h4 class="mb-0 text-white">
                                        <i class="fas fa-hospital"></i> <?= htmlspecialchars($ruangan_data['info']['nama']) ?>
                                        <small class="text-white-50 ml-3">
                                            Kelas: <?= htmlspecialchars($ruangan_data['info']['kelas']) ?> | 
                                            Lantai: <?= $ruangan_data['info']['lantai'] ?> | 
                                            Kapasitas: <?= $ruangan_data['info']['kapasitas'] ?> Bed
                                        </small>
                                    </h4>
                                    <span class="badge badge-light p-2">
                                        <?= count($ruangan_data['beds']) ?> Bed Ditampilkan
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="bed-grid">
                                        <?php foreach ($ruangan_data['beds'] as $bed): 
                                            $status_tampil = $bed['status_final'];
                                            $status_class = 'status-' . str_replace([' ', '(', ')'], '', $status_tampil);
                                            $border_color = 'secondary';
                                            switch ($status_tampil) {
                                                case 'Terisi':
                                                    $border_color = 'danger';
                                                    break;
                                                case 'Kosong':
                                                case 'Siap':
                                                    $border_color = 'success';
                                                    break;
                                                case 'Kotor':
                                                case 'Pembersihan':
                                                    $border_color = 'warning';
                                                    break;
                                                case 'Maintenance (Fisik)':
                                                case 'Maintenance':
                                                case 'Nonaktif':
                                                    $border_color = 'secondary';
                                                    break;
                                            }
                                        ?>
                                        <div class="bed-card card border-left-<?= $border_color ?> shadow h-100" 
                                            onclick="window.location='detail.php?id=<?= $bed['f_idbed'] ?>'">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                                            Bed No. <?= htmlspecialchars($bed['f_nomorbed']) ?>
                                                        </div>
                                                        <span class="badge bed-status-badge <?= $status_class ?> mb-2">
                                                            <?= htmlspecialchars($status_tampil) ?>
                                                        </span>
                                                        <?php if ($bed['ada_pasien']): ?>
                                                            <div class="text-xs text-danger font-weight-bold">
                                                                Pasien: <?= htmlspecialchars($bed['nama_pasien'] ?? 'N/A') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($bed['waktu_status'])): ?>
                                                            <div class="text-xs text-muted mt-1">
                                                                Sejak: <?= date('d/m H:i', strtotime($bed['waktu_status'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-procedures fa-2x text-gray-200"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
                </div>
            <footer class="sticky-footer bg-white shadow-sm mt-4">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; RS InsanMedika 2025</span>
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

    <script>
        $(document).ready(function() {
            setTimeout(function() {
                location.reload();
            }, 300000);

            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>

</body>

</html>