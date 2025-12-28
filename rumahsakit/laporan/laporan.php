<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once '../dbcontroller.php';
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

$tgl_awal  = $_GET['awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['akhir'] ?? date('Y-m-t');


$sql_bed = "
SELECT COUNT(*) AS total_bed
FROM t_tempattidur
WHERE f_stsfisik = 'Aktif'
";
$total_bed = (int)$db->getITEM($sql_bed)['total_bed'];

$sql_bed_kelas = "
SELECT r.f_kelas, COUNT(tt.f_idbed) AS jumlah_bed
FROM t_tempattidur tt
JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
WHERE tt.f_stsfisik = 'Aktif'
GROUP BY r.f_kelas
ORDER BY r.f_kelas
";
$bed_per_kelas = $db->getALL($sql_bed_kelas);


$sql_masuk = "
SELECT COUNT(*) AS pasien_masuk
FROM t_rawatinap
WHERE DATE(f_waktumasuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
";
$pasien_masuk = (int)$db->getITEM($sql_masuk)['pasien_masuk'];

$sql_keluar = "
SELECT COUNT(*) AS pasien_keluar
FROM t_rawatinap
WHERE f_waktukeluar IS NOT NULL
AND DATE(f_waktukeluar) BETWEEN '$tgl_awal' AND '$tgl_akhir'
";
$pasien_keluar = (int)$db->getITEM($sql_keluar)['pasien_keluar'];

$sql_keluar_alasan = "
SELECT f_alasan, COUNT(*) AS jumlah
FROM t_rawatinap
WHERE f_waktukeluar IS NOT NULL
AND DATE(f_waktukeluar) BETWEEN '$tgl_awal' AND '$tgl_akhir'
GROUP BY f_alasan
";
$keluar_per_alasan = $db->getALL($sql_keluar_alasan);

$sql_hp = "
SELECT SUM(
    CASE 
        WHEN DATEDIFF(LEAST(COALESCE(f_waktukeluar, '$tgl_akhir'), '$tgl_akhir'), GREATEST(f_waktumasuk, '$tgl_awal')) = 0 THEN 1
        ELSE DATEDIFF(LEAST(COALESCE(f_waktukeluar, '$tgl_akhir'), '$tgl_akhir'), GREATEST(f_waktumasuk, '$tgl_awal'))
    END
) AS hari_perawatan
FROM t_rawatinap
WHERE 
    f_waktumasuk <= '$tgl_akhir'
    AND (f_waktukeluar IS NULL OR f_waktukeluar >= '$tgl_awal')
";
$hari_perawatan = (int)($db->getITEM($sql_hp)['hari_perawatan'] ?? 0);

$sql_los = "
SELECT 
    SUM(CASE WHEN DATEDIFF(f_waktukeluar, f_waktumasuk) = 0 THEN 1 ELSE DATEDIFF(f_waktukeluar, f_waktumasuk) END) AS total_los,
    AVG(CASE WHEN DATEDIFF(f_waktukeluar, f_waktumasuk) = 0 THEN 1 ELSE DATEDIFF(f_waktukeluar, f_waktumasuk) END) AS avg_los,
    MIN(CASE WHEN DATEDIFF(f_waktukeluar, f_waktumasuk) = 0 THEN 1 ELSE DATEDIFF(f_waktukeluar, f_waktumasuk) END) AS min_los,
    MAX(CASE WHEN DATEDIFF(f_waktukeluar, f_waktumasuk) = 0 THEN 1 ELSE DATEDIFF(f_waktukeluar, f_waktumasuk) END) AS max_los
FROM t_rawatinap
WHERE f_waktukeluar IS NOT NULL
AND DATE(f_waktukeluar) BETWEEN '$tgl_awal' AND '$tgl_akhir'
";
$los_data = $db->getITEM($sql_los);

$total_los = (int)($los_data['total_los'] ?? 0);
$avg_los   = (float)($los_data['avg_los'] ?? 0);
$min_los   = (int)($los_data['min_los'] ?? 0);
$max_los   = (int)($los_data['max_los'] ?? 0);

$sql_masih_rawat = "
SELECT COUNT(*) AS masih_rawat
FROM t_rawatinap
WHERE f_waktukeluar IS NULL
AND DATE(f_waktumasuk) <= '$tgl_akhir'
";
$masih_rawat = (int)$db->getITEM($sql_masih_rawat)['masih_rawat'];

$sql_bed_status = "
SELECT 
    SUM(f_stsfisik = 'Maintenance') AS maintenance,
    SUM(f_stsfisik = 'Nonaktif') AS nonaktif
FROM t_tempattidur
";
$bed_status = $db->getITEM($sql_bed_status);

$bed_maintenance = (int)$bed_status['maintenance'];
$bed_nonaktif    = (int)$bed_status['nonaktif'];

$periode = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / 86400 + 1;

$BOR = ($total_bed > 0)
    ? ($hari_perawatan / ($total_bed * $periode)) * 100
    : 0;

$ALOS = ($pasien_keluar > 0)
    ? $total_los / $pasien_keluar
    : 0;

$TOI = ($pasien_keluar > 0)
    ? (($total_bed * $periode) - $hari_perawatan) / $pasien_keluar
    : 0;

$BTO = ($total_bed > 0)
    ? $pasien_keluar / $total_bed
    : 0;

$sql_meninggal_48 = "
SELECT COUNT(*) AS meninggal_48
FROM t_rawatinap
WHERE (f_alasan = 'Meninggal' OR f_alasan = 'meninggal')
AND f_waktukeluar IS NOT NULL 
AND f_waktumasuk IS NOT NULL
AND TIMESTAMPDIFF(HOUR, f_waktumasuk, f_waktukeluar) >= 48
AND DATE(f_waktukeluar) BETWEEN '$tgl_awal' AND '$tgl_akhir'
";
$res_meninggal = $db->getITEM($sql_meninggal_48);
$meninggal_48 = (int)($res_meninggal['meninggal_48'] ?? 0);

$NDR = ($pasien_keluar > 0)
    ? ($meninggal_48 / $pasien_keluar) * 100
    : 0;

$bed_available_rate = ($total_bed > 0)
    ? (($total_bed - $masih_rawat) / $total_bed) * 100
    : 0;

function evaluateIndicator($value, $min, $max) {
    if ($value >= $min && $value <= $max) {
        return ['status' => 'Baik', 'class' => 'success', 'icon' => 'check-circle'];
    } elseif ($value < $min) {
        return ['status' => 'Rendah', 'class' => 'warning', 'icon' => 'exclamation-triangle'];
    } else {
        return ['status' => 'Tinggi', 'class' => 'danger', 'icon' => 'times-circle'];
    }
}

$bor_eval  = evaluateIndicator($BOR, 60, 85);
$alos_eval = evaluateIndicator($ALOS, 6, 9);
$toi_eval  = evaluateIndicator($TOI, 1, 3);

$ndr_eval = ($NDR < 2.5)
    ? ['status' => 'Baik', 'class' => 'success', 'icon' => 'check-circle']
    : ['status' => 'Perlu Perhatian', 'class' => 'danger', 'icon' => 'times-circle'];
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bed Management</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            .card { page-break-inside: avoid; }
        }
        .indicator-card {
            transition: transform 0.2s;
        }
        .indicator-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
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

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                           Laporan Bed Management
                        </h1>
                        <button onclick="window.print()" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm no-print">
                            <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
                        </button>
                    </div>

                    <div class="card shadow mb-4 no-print">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-calendar-alt"></i> Filter Periode Laporan
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="form-inline">
                                <label class="mr-2 font-weight-bold">Periode:</label>
                                <input type="date" name="awal" value="<?= $tgl_awal ?>" class="form-control mr-2" required>
                                <span class="mr-2">s/d</span>
                                <input type="date" name="akhir" value="<?= $tgl_akhir ?>" class="form-control mr-2" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Periode Laporan:</strong> 
                        <?= date('d F Y', strtotime($tgl_awal)) ?> - <?= date('d F Y', strtotime($tgl_akhir)) ?>
                        (<?= $periode ?> hari)
                    </div>

                    <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Indikator Kinerja Utama (KPI)</h5>
                    <div class="row">

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-<?= $bor_eval['class'] ?> shadow h-100 py-2 indicator-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-<?= $bor_eval['class'] ?> text-uppercase mb-1">
                                                BOR (Bed Occupancy Rate)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($BOR, 2) ?>%
                                            </div>
                                            <div class="mt-2 text-xs">
                                                <span class="badge badge-<?= $bor_eval['class'] ?>">
                                                    <i class="fas fa-<?= $bor_eval['icon'] ?>"></i> <?= $bor_eval['status'] ?>
                                                </span>
                                                <br><small class="text-muted">Ideal: 60-85%</small>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bed fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-<?= $toi_eval['class'] ?> shadow h-100 py-2 indicator-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-<?= $toi_eval['class'] ?> text-uppercase mb-1">
                                                TOI (Turn Over Interval)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($TOI, 2) ?> hari
                                            </div>
                                            <div class="mt-2 text-xs">
                                                <span class="badge badge-<?= $toi_eval['class'] ?>">
                                                    <i class="fas fa-<?= $toi_eval['icon'] ?>"></i> <?= $toi_eval['status'] ?>
                                                </span>
                                                <br><small class="text-muted">Ideal: 1-3 hari</small>
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
                            <div class="card border-left-<?= $alos_eval['class'] ?> shadow h-100 py-2 indicator-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-<?= $alos_eval['class'] ?> text-uppercase mb-1">
                                                ALOS (Average Length of Stay)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($ALOS, 2) ?> hari
                                            </div>
                                            <div class="mt-2 text-xs">
                                                <span class="badge badge-<?= $alos_eval['class'] ?>">
                                                    <i class="fas fa-<?= $alos_eval['icon'] ?>"></i> <?= $alos_eval['status'] ?>
                                                </span>
                                                <br><small class="text-muted">Ideal: 6-9 hari</small>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 indicator-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                BTO (Bed Turn Over)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($BTO, 2) ?>x
                                            </div>
                                            <div class="mt-2 text-xs">
                                                <small class="text-muted">Pasien/Bed dalam periode</small>
                                                <br><small class="text-muted">Ideal: 40-50x/tahun</small>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-sync-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-<?= $ndr_eval['class'] ?> shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-<?= $ndr_eval['class'] ?> text-uppercase mb-1">
                                        NDR (Net Death Rate)
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($NDR, 2) ?>%
                                    </div>
                                    <small class="text-muted">Ideal: < 2.5%</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Bed Tersedia
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($bed_available_rate, 2) ?>%
                                    </div>
                                    <small class="text-muted"><?= $total_bed - $masih_rawat ?> dari <?= $total_bed ?> bed</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Pasien Masih Dirawat
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $masih_rawat ?> pasien
                                    </div>
                                    <small class="text-muted">Per <?= date('d M Y', strtotime($tgl_akhir)) ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Hari Perawatan
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($hari_perawatan) ?> hari
                                    </div>
                                    <small class="text-muted">Dalam periode laporan</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-users"></i> Ringkasan Pasien
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="60%">Pasien Masuk</th>
                                            <td class="text-right font-weight-bold"><?= $pasien_masuk ?> pasien</td>
                                        </tr>
                                        <tr>
                                            <th>Pasien Keluar</th>
                                            <td class="text-right font-weight-bold"><?= $pasien_keluar ?> pasien</td>
                                        </tr>
                                        <tr>
                                            <th>Pasien Masih Dirawat</th>
                                            <td class="text-right font-weight-bold text-primary"><?= $masih_rawat ?> pasien</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <th>Total Hari Perawatan</th>
                                            <td class="text-right font-weight-bold"><?= number_format($hari_perawatan) ?> hari</td>
                                        </tr>
                                    </table>

                                    <h6 class="mt-4 mb-3 font-weight-bold">Pasien Keluar Berdasarkan Alasan:</h6>
                                    <?php if (!empty($keluar_per_alasan)): ?>
                                        <table class="table table-sm table-bordered">
                                            <?php foreach ($keluar_per_alasan as $alasan): ?>
                                                <?php
                                                $badge_class = 'secondary';
                                                if ($alasan['f_alasan'] == 'Pulang') $badge_class = 'success';
                                                elseif ($alasan['f_alasan'] == 'Pindah') $badge_class = 'info';
                                                elseif ($alasan['f_alasan'] == 'Meninggal') $badge_class = 'danger';
                                                $persen = ($pasien_keluar > 0) ? ($alasan['jumlah'] / $pasien_keluar) * 100 : 0;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-<?= $badge_class ?>">
                                                            <?= htmlspecialchars($alasan['f_alasan']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-right">
                                                        <strong><?= $alasan['jumlah'] ?></strong> pasien 
                                                        <small class="text-muted">(<?= number_format($persen, 1) ?>%)</small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php else: ?>
                                        <p class="text-muted">Tidak ada data pasien keluar.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-bed"></i> Ringkasan Bed/Tempat Tidur
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="60%">Total Bed Aktif</th>
                                            <td class="text-right font-weight-bold text-success"><?= $total_bed ?> bed</td>
                                        </tr>
                                        <tr>
                                            <th>Bed Maintenance</th>
                                            <td class="text-right font-weight-bold text-warning"><?= $bed_maintenance ?> bed</td>
                                        </tr>
                                        <tr>
                                            <th>Bed Nonaktif/Rusak</th>
                                            <td class="text-right font-weight-bold text-danger"><?= $bed_nonaktif ?> bed</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <th>Total Seluruh Bed</th>
                                            <td class="text-right font-weight-bold"><?= ($total_bed + $bed_maintenance + $bed_nonaktif) ?> bed</td>
                                        </tr>
                                    </table>

                                    <h6 class="mt-4 mb-3 font-weight-bold">Bed Aktif Per Kelas Ruangan:</h6>
                                    <?php if (!empty($bed_per_kelas)): ?>
                                        <table class="table table-sm table-bordered">
                                            <?php foreach ($bed_per_kelas as $kelas): ?>
                                                <?php
                                                $persen = ($total_bed > 0) ? ($kelas['jumlah_bed'] / $total_bed) * 100 : 0;
                                                ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($kelas['f_kelas']) ?></strong></td>
                                                    <td class="text-right">
                                                        <?= $kelas['jumlah_bed'] ?> bed 
                                                        <small class="text-muted">(<?= number_format($persen, 1) ?>%)</small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php else: ?>
                                        <p class="text-muted">Tidak ada data bed.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-chart-area"></i> Statistik Lama Rawat (LOS)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h6 class="text-muted">Rata-rata (ALOS)</h6>
                                    <h3 class="text-primary"><?= number_format($avg_los, 2) ?> hari</h3>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Minimum</h6>
                                    <h3 class="text-success"><?= number_format($min_los, 0) ?> hari</h3>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Maximum</h6>
                                    <h3 class="text-danger"><?= number_format($max_los, 0) ?> hari</h3>
                                </div>
                                                                <div class="col-md-3">
                                    <h6 class="text-muted">Total</h6>
                                    <h3 class="text-warning"><?= number_format($total_los, 0) ?> hari</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-clipboard-check"></i> Kesimpulan & Evaluasi Manajemen Bed
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>
                                    <strong>BOR:</strong>
                                    <?= number_format($BOR, 2) ?>%
                                    (<?= $bor_eval['status'] ?>)
                                </li>
                                <li>
                                    <strong>ALOS:</strong>
                                    <?= number_format($ALOS, 2) ?> hari
                                    (<?= $alos_eval['status'] ?>)
                                </li>
                                <li>
                                    <strong>TOI:</strong>
                                    <?= number_format($TOI, 2) ?> hari
                                    (<?= $toi_eval['status'] ?>)
                                </li>
                                <li>
                                    <strong>BTO:</strong>
                                    <?= number_format($BTO, 2) ?> kali
                                </li>
                                <li>
                                    <strong>NDR:</strong>
                                    <?= number_format($NDR, 2) ?>%
                                    (<?= $ndr_eval['status'] ?>)
                                </li>
                            </ul>

                            <hr>

                            <p>
                                Berdasarkan indikator di atas, tingkat pemanfaatan tempat tidur rumah sakit
                                <strong>
                                    <?= ($bor_eval['status'] === 'Baik') ? 'sudah optimal' : 'perlu evaluasi lanjutan' ?>
                                </strong>.
                                Pengelolaan tempat tidur perlu memperhatikan keseimbangan antara jumlah bed aktif,
                                kecepatan pergantian pasien, serta waktu pembersihan dan kesiapan bed.
                            </p>

                            <p class="mb-0">
                                <em>
                                    Laporan ini dapat digunakan sebagai dasar evaluasi kinerja unit rawat inap,
                                    perencanaan kapasitas bed, serta peningkatan mutu pelayanan rumah sakit.
                                </em>
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto text-center">
                    <span>© <?= date('Y') ?> Sistem Bed Management Rumah Sakit</span>
                </div>
            </footer>

        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

</body>
</html>
