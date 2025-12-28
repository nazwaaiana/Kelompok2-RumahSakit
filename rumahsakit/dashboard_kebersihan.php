<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once "dbcontroller.php";
require_once 'config.php';
$db = new dbcontroller();

require_once "check_role.php";
requireRole(['Admin', 'Petugas Kebersihan']);

if (!isset($_SESSION['idpetugas'])) {
    header("location:login.php");
    exit;
}

$role_petugas = $_SESSION['role'] ?? 'Guest';
$nama_petugas = $_SESSION['petugas'] ?? 'Guest';
$unit_kerja = $_SESSION['unitkerja'] ?? null;
$lantai_petugas = null;
if ($unit_kerja && preg_match('/Lantai (\d+)/i', $unit_kerja, $matches)) {
    $lantai_petugas = (int)$matches[1];
}

function formatDurasi($waktu_mulai_str) {
    $waktu_mulai = strtotime($waktu_mulai_str);
    $waktu_sekarang = time();
    
    if ($waktu_mulai > $waktu_sekarang) {
        return ['jam' => 0, 'menit' => 0, 'detik' => 0, 'total_jam' => 0];
    }
    
    $selisih = $waktu_sekarang - $waktu_mulai;
    
    $jam = floor($selisih / 3600);
    $menit = floor(($selisih % 3600) / 60);
    $detik = $selisih % 60;
    
    return [
        'jam' => max(0, $jam),
        'menit' => max(0, $menit),
        'detik' => max(0, $detik),
        'total_jam' => max(0, $jam)
    ];
}

$filter_lantai = "";
if ($role_petugas == 'Petugas Kebersihan' && $lantai_petugas !== null) {
    $filter_lantai = " AND r.f_lantai = $lantai_petugas";
}

$sql_stats = "SELECT 
                COUNT(CASE WHEN bs.f_sts IN ('Pembersihan', 'Kotor') AND bs.f_waktuselesai IS NULL THEN 1 END) as total_perlu_bersih,
                COUNT(CASE WHEN bs.f_sts = 'Siap' AND bs.f_waktuselesai IS NULL THEN 1 END) as total_siap,
                COUNT(CASE WHEN bs.f_sts IN ('Pembersihan', 'Kotor') AND bs.f_waktuselesai IS NULL 
                           AND TIMESTAMPDIFF(HOUR, bs.f_waktumulai, NOW()) > 2 THEN 1 END) as total_terlambat,
                COUNT(CASE WHEN bs.f_sts IN ('Pembersihan', 'Kotor') AND bs.f_waktuselesai IS NULL 
                           AND TIMESTAMPDIFF(HOUR, bs.f_waktumulai, NOW()) BETWEEN 1 AND 2 THEN 1 END) as total_prioritas_tinggi
              FROM t_bedstatus bs
              JOIN t_tempattidur tt ON bs.f_idbed = tt.f_idbed
              JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
              WHERE 1=1 $filter_lantai";
$stats = $db->getITEM($sql_stats);

$sql_urgent = "SELECT
            bs.f_idbedsts,
            bs.f_idbed,
            bs.f_sts,
            bs.f_waktumulai,
            tt.f_nomorbed,
            r.f_nama AS nama_ruangan,
            r.f_kelas AS kelas_ruangan,
            r.f_lantai,
            p.f_nama AS nama_pasien_keluar,
            ri.f_waktukeluar,
            COALESCE(ri.f_waktukeluar, bs.f_waktumulai) as waktu_referensi
        FROM
            t_bedstatus bs
        JOIN
            t_tempattidur tt ON bs.f_idbed = tt.f_idbed
        JOIN
            t_ruangan r ON tt.f_idruangan = r.f_idruangan
        LEFT JOIN
            t_rawatinap ri ON tt.f_idbed = ri.f_idbed 
            AND ri.f_waktukeluar IS NOT NULL 
            AND ri.f_stsbersih = 'Kotor'
        LEFT JOIN
            t_pasien p ON ri.f_idpasien = p.f_idpasien
        WHERE 
            bs.f_waktuselesai IS NULL
            AND (bs.f_sts = 'Pembersihan' OR bs.f_sts = 'Kotor')
            $filter_lantai
        ORDER BY
            COALESCE(ri.f_waktukeluar, bs.f_waktumulai) ASC
        LIMIT 5";

$urgent_beds = $db->getALL($sql_urgent);
$banyak_history = 5;
$p_history = isset($_GET['ph']) ? (int)$_GET['ph'] : 1;
$p_history = max(1, $p_history);
$mulai_history = ($p_history - 1) * $banyak_history;

$jumlah_history = $db->rowCOUNT("SELECT bs_kotor.f_idbedsts 
                                FROM t_bedstatus bs_kotor
                                JOIN t_tempattidur tt ON bs_kotor.f_idbed = tt.f_idbed
                                JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                                WHERE bs_kotor.f_sts IN ('Pembersihan', 'Kotor')
                                AND bs_kotor.f_waktuselesai IS NOT NULL
                                AND DATE(bs_kotor.f_waktuselesai) = CURDATE()
                                $filter_lantai");

$halaman_history = $jumlah_history > 0 ? ceil($jumlah_history / $banyak_history) : 1;

$sql_history = "SELECT 
                    bs_kotor.f_idbedsts,
                    bs_kotor.f_idbed,
                    bs_kotor.f_sts AS status_awal,
                    bs_kotor.f_waktumulai AS waktu_mulai,
                    bs_kotor.f_waktuselesai AS waktu_selesai,
                    tt.f_nomorbed,
                    r.f_nama AS nama_ruangan,
                    r.f_lantai,
                    bs_siap.f_idpetugas,
                    pt.f_nama AS nama_petugas,
                    pt.f_unitkerja
                FROM t_bedstatus bs_kotor
                JOIN t_tempattidur tt ON bs_kotor.f_idbed = tt.f_idbed
                JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                LEFT JOIN t_bedstatus bs_siap ON bs_kotor.f_idbed = bs_siap.f_idbed 
                    AND bs_siap.f_sts = 'Siap'
                    AND bs_siap.f_waktumulai = bs_kotor.f_waktuselesai
                LEFT JOIN t_petugas pt ON bs_siap.f_idpetugas = pt.f_idpetugas
                WHERE bs_kotor.f_sts IN ('Pembersihan', 'Kotor')
                AND bs_kotor.f_waktuselesai IS NOT NULL
                AND DATE(bs_kotor.f_waktuselesai) = CURDATE()
                $filter_lantai
                ORDER BY bs_kotor.f_waktuselesai DESC
                LIMIT $mulai_history, $banyak_history";

$history_today = $db->getALL($sql_history);

if ($role_petugas == 'Admin') {
    $sql_lantai_stats = "SELECT 
                            r.f_lantai,
                            COUNT(CASE WHEN bs.f_sts IN ('Pembersihan', 'Kotor') AND bs.f_waktuselesai IS NULL THEN 1 END) as perlu_bersih
                         FROM t_bedstatus bs
                         JOIN t_tempattidur tt ON bs.f_idbed = tt.f_idbed
                         JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                         WHERE bs.f_waktuselesai IS NULL
                         GROUP BY r.f_lantai
                         HAVING perlu_bersih > 0
                         ORDER BY r.f_lantai";
} else {
    $sql_lantai_stats = "SELECT 
                            r.f_lantai,
                            COUNT(CASE WHEN bs.f_sts IN ('Pembersihan', 'Kotor') AND bs.f_waktuselesai IS NULL THEN 1 END) as perlu_bersih
                         FROM t_bedstatus bs
                         JOIN t_tempattidur tt ON bs.f_idbed = tt.f_idbed
                         JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                         WHERE bs.f_waktuselesai IS NULL
                         $filter_lantai
                         GROUP BY r.f_lantai
                         HAVING perlu_bersih > 0
                         ORDER BY r.f_lantai";
}
$lantai_stats = $db->getALL($sql_lantai_stats);

$sql_total_selesai = "SELECT COUNT(*) as total 
                      FROM t_bedstatus bs
                      JOIN t_tempattidur tt ON bs.f_idbed = tt.f_idbed
                      JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                      WHERE bs.f_sts IN ('Pembersihan', 'Kotor')
                      AND bs.f_waktuselesai IS NOT NULL
                      AND DATE(bs.f_waktuselesai) = CURDATE()
                      $filter_lantai";
$total_selesai_result = $db->getITEM($sql_total_selesai);
$total_selesai_hari_ini = $total_selesai_result['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard Kebersihan - RS InsanMedika</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .priority-high {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .urgent-card {
            border-left: 4px solid #e74a3b;
        }
        
        .urgent-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(231, 74, 59, 0.3);
        }

        .lantai-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.35rem;
        }

        .history-pagination {
            margin-top: 1rem;
        }

        .history-pagination .page-link {
            color: #1cc88a;
            border-radius: 5px;
            margin: 0 2px;
        }

        .history-pagination .page-item.active .page-link {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        
        .btn-disabled-custom {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .unit-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            display: inline-block;
            font-weight: 600;
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
                        <div>
                            <h1 class="h3 mb-0 text-gray-800">
                                <i class="fas fa-broom"></i> Dashboard Manajemen Kebersihan
                            </h1>
                            <?php if ($role_petugas == 'Petugas Kebersihan' && $unit_kerja): ?>
                            <div class="mt-2">
                                <span class="unit-info">
                                    <i class="fas fa-building"></i> <?= htmlspecialchars($unit_kerja) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <small class="text-muted d-block">
                                <i class="fas fa-info-circle"></i> 
                                <?= $role_petugas == 'Admin' ? 'Monitoring Real-time Seluruh Rumah Sakit' : 'Monitoring Unit Kerja Anda' ?>
                            </small>
                            <?php if ($total_selesai_hari_ini > 0): ?>
                            <span class="badge badge-success p-2 mt-1">
                                <i class="fas fa-check-circle"></i> <?= $total_selesai_hari_ini ?> Pembersihan Selesai Hari Ini
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alert Urgent -->
                    <?php if ($stats['total_terlambat'] > 0): ?>
                    <div class="alert alert-danger alert-dismissible fade show priority-high" role="alert">
                        <strong><i class="fas fa-exclamation-triangle"></i> Perhatian!</strong> 
                        Ada <strong><?= $stats['total_terlambat'] ?></strong> bed <?= $lantai_petugas !== null ? "di lantai $lantai_petugas" : "" ?> yang sudah menunggu lebih dari 2 jam untuk dibersihkan!
                        <a href="kebersihan/select.php" class="btn btn-danger btn-sm ml-3">
                            <i class="fas fa-broom"></i> Lihat Sekarang
                        </a>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($role_petugas == 'Petugas Kebersihan' && $lantai_petugas !== null): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Info:</strong> 
                        Anda hanya dapat menyelesaikan pembersihan bed yang berada di <strong><?= htmlspecialchars($unit_kerja) ?></strong> sesuai tanggung jawab Anda.
                    </div>
                    <?php endif; ?>

                    <div class="row">

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Perlu Dibersihkan
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $stats['total_perlu_bersih'] ?> Bed
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Urgent (>2 Jam)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $stats['total_terlambat'] ?> Bed
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Prioritas Tinggi
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $stats['total_prioritas_tinggi'] ?> Bed
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Sudah Siap
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $stats['total_siap'] ?> Bed
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($lantai_stats)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-warning text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-layer-group"></i> Status Lantai yang Memerlukan Perhatian
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <?php foreach ($lantai_stats as $lantai): ?>
                                        <div class="col-md-2 col-sm-4 mb-2">
                                            <div class="p-3 border rounded bg-light">
                                                <h5 class="text-primary mb-2">
                                                    <i class="fas fa-building"></i> Lantai <?= $lantai['f_lantai'] ?>
                                                </h5>
                                                <span class="badge badge-warning badge-lg lantai-badge">
                                                    <i class="fas fa-exclamation-circle"></i> <?= $lantai['perlu_bersih'] ?> Bed Kotor
                                                </span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">

                        <!-- Bed Prioritas Urgent -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-danger text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-exclamation-circle"></i> Bed Prioritas (5 Teratas)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($urgent_beds)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="text-muted">Tidak ada bed yang perlu dibersihkan saat ini</p>
                                            <p class="text-success font-weight-bold">Semua bed dalam kondisi bersih! 🎉</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($urgent_beds as $bed): 
                                            $waktu_untuk_durasi = $bed['waktu_referensi'] ?? $bed['f_waktumulai'];
                                            $durasi = formatDurasi($waktu_untuk_durasi);
                                            
                                            $prioritas = 'Normal';
                                            $badge_prioritas = 'badge-info';
                                            $card_class = '';
                                            
                                            if ($durasi['total_jam'] >= 2) {
                                                $prioritas = 'Urgent';
                                                $badge_prioritas = 'badge-danger';
                                                $card_class = 'urgent-card priority-high';
                                            } elseif ($durasi['total_jam'] >= 1) {
                                                $prioritas = 'Tinggi';
                                                $badge_prioritas = 'badge-warning';
                                            }
                                            
                                            $durasi_text = sprintf('%d jam %d menit %d detik', 
                                                                $durasi['jam'], 
                                                                $durasi['menit'], 
                                                                $durasi['detik']);
                                            
                                            $bisa_akses = true;
                                            $btn_class = 'btn-success';
                                            $btn_disabled = '';
                                            
                                            if ($role_petugas == 'Petugas Kebersihan' && $lantai_petugas !== null) {
                                                if ((int)$bed['f_lantai'] !== $lantai_petugas) {
                                                    $bisa_akses = false;
                                                    $btn_class = 'btn-secondary btn-disabled-custom';
                                                    $btn_disabled = 'disabled';
                                                }
                                            }
                                        ?>
                                        <div class="card <?= $card_class ?> mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="font-weight-bold text-primary">
                                                            <i class="fas fa-bed"></i> Bed <?= htmlspecialchars($bed['f_nomorbed']) ?>
                                                            <span class="badge badge-secondary ml-2">Lantai <?= $bed['f_lantai'] ?></span>
                                                        </h6>
                                                        <p class="mb-1">
                                                            <i class="fas fa-door-open text-info"></i> 
                                                            <strong><?= htmlspecialchars($bed['nama_ruangan']) ?></strong> 
                                                            <small class="text-muted">(<?= htmlspecialchars($bed['kelas_ruangan']) ?>)</small>
                                                        </p>
                                                        <p class="mb-1">
                                                            <i class="fas fa-user-injured text-secondary"></i> 
                                                            Pasien: <?= htmlspecialchars($bed['nama_pasien_keluar'] ?? '-') ?>
                                                        </p>
                                                        <p class="mb-0">
                                                            <i class="far fa-clock text-muted"></i> 
                                                            Menunggu: <strong class="<?= $durasi['total_jam'] >= 2 ? 'text-danger' : ($durasi['total_jam'] >= 1 ? 'text-warning' : 'text-info') ?>">
                                                                <?= $durasi_text ?>
                                                            </strong>
                                                        </p>
                                                        <?php if (!$bisa_akses): ?>
                                                        <small class="text-danger">
                                                            <i class="fas fa-lock"></i> Bukan unit kerja Anda
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="badge <?= $badge_prioritas ?> p-2 mb-2">
                                                            <?= $prioritas ?>
                                                        </span>
                                                        <br>
                                                        <?php if ($bisa_akses): ?>
                                                        <a href="kebersihan/finish.php?id=<?= $bed['f_idbedsts'] ?>" 
                                                           class="btn <?= $btn_class ?> btn-sm">
                                                            <i class="fas fa-check"></i> Selesai
                                                        </a>
                                                        <?php else: ?>
                                                        <button class="btn <?= $btn_class ?> btn-sm" disabled title="Bukan unit kerja Anda">
                                                            <i class="fas fa-lock"></i> Terkunci
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="text-center mt-3">
                                            <a href="kebersihan/select.php" class="btn btn-primary">
                                                <i class="fas fa-list"></i> Lihat Semua Bed
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-success text-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-history"></i> Riwayat Pembersihan Hari Ini
                                    </h6>
                                    <span class="badge badge-light">
                                        <i class="fas fa-check-double"></i> <?= $jumlah_history ?> Total
                                    </span>
                                </div>
                                <div class="card-body">
                                   <?php if (empty($history_today)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Belum ada pembersihan yang diselesaikan hari ini</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Bed & Ruang</th>
                                                        <th>Waktu</th>
                                                        <th>Petugas</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($history_today as $h): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="font-weight-bold text-dark">Bed <?= htmlspecialchars($h['f_nomorbed']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($h['nama_ruangan']) ?> (Lt. <?= $h['f_lantai'] ?>)</small>
                                                        </td>
                                                        <td>
                                                            <div class="text-xs">
                                                                <span class="text-danger"><i class="fas fa-arrow-right"></i> <?= date('H:i', strtotime($h['waktu_mulai'])) ?></span><br>
                                                                <span class="text-success"><i class="fas fa-check"></i> <?= date('H:i', strtotime($h['waktu_selesai'])) ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="text-xs font-weight-bold text-primary">
                                                                <?= htmlspecialchars($h['nama_petugas'] ?? 'Sistem') ?>
                                                            </div>
                                                            <small class="badge badge-light"><?= htmlspecialchars($h['f_unitkerja'] ?? '-') ?></small>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <nav aria-label="Page navigation" class="history-pagination">
                                            <ul class="pagination pagination-sm justify-content-center">
                                                <?php for ($i = 1; $i <= $halaman_history; $i++): ?>
                                                    <li class="page-item <?= ($p_history == $i) ? 'active' : '' ?>">
                                                        <a class="page-link" href="?ph=<?= $i ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div></div><footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; RS InsanMedika 2025</span>
                    </div>
                </div>
            </footer>

        </div></div><a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        setTimeout(function() {
            window.location.reload();
        }, 30000);

        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        });
    </script>
</body>
</html>