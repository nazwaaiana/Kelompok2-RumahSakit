<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Petugas Kebersihan']);

if (!isset($_SESSION['petugas']) || !isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php");
    exit;
}

$id_petugas = $_SESSION['idpetugas'];
$role = $_SESSION['role'];

$sql_petugas = "SELECT f_unitkerja FROM t_petugas WHERE f_idpetugas = '$id_petugas'";
$data_petugas = $db->getALL($sql_petugas);

$lantai_petugas = null;
$filter_lantai = "";

if (!empty($data_petugas)) {
    $unit_kerja = $data_petugas[0]['f_unitkerja'];
    if (preg_match('/lantai\s*(\d+)/i', $unit_kerja, $matches)) {
        $lantai_petugas = (int)$matches[1];
    }
}

if ($role === 'Petugas Kebersihan' && $lantai_petugas !== null) {
    $filter_lantai = "AND r.f_lantai = '$lantai_petugas'";
}

$jumlahdata = $db->rowCOUNT("SELECT f_idbedsts FROM t_bedstatus bs
                             JOIN t_tempattidur tt ON bs.f_idbed = tt.f_idbed
                             JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
                             WHERE bs.f_waktuselesai IS NULL 
                             AND (bs.f_sts = 'Pembersihan' OR bs.f_sts = 'Kotor')
                             $filter_lantai");
$banyak = 10;
$halaman = ceil($jumlahdata / $banyak);

$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$p = max(1, min($p, $halaman));
$mulai = ($p - 1) * $banyak;

$sql = "SELECT
            bs.f_idbedsts,
            bs.f_idbed,
            bs.f_sts,
            bs.f_waktumulai,
            bs.f_keterangan,
            tt.f_nomorbed,
            r.f_nama AS nama_ruangan,
            r.f_kelas AS kelas_ruangan,
            r.f_lantai,
            pt.f_nama AS nama_petugas,
            ri.f_idrawatinap,
            p.f_nama AS nama_pasien_keluar,
            ri.f_waktukeluar
        FROM
            t_bedstatus bs
        JOIN
            t_tempattidur tt ON bs.f_idbed = tt.f_idbed
        JOIN
            t_ruangan r ON tt.f_idruangan = r.f_idruangan
        LEFT JOIN
            t_petugas pt ON bs.f_idpetugas = pt.f_idpetugas
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
            bs.f_waktumulai ASC
        LIMIT $mulai, $banyak";

$row = $db->getALL($sql);
$no = 1 + $mulai;

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Kebersihan Bed - RS InsanMedika</title>
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
        
        .priority-high {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
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

        .floor-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .warning-no-floor {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #2d3436;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
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

                if ($role === 'Petugas Kebersihan' && $lantai_petugas === null) {
                    $unit_kerja_display = isset($data_petugas[0]['f_unitkerja']) ? $data_petugas[0]['f_unitkerja'] : 'Tidak ada';
                    echo '<div class="warning-no-floor">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Perhatian:</strong> 
                            Unit kerja Anda (<strong>'.$unit_kerja_display.'</strong>) belum terkonfigurasi dengan lantai tertentu. 
                            <br>Hubungi admin untuk mengubah unit kerja Anda menjadi format seperti: <strong>"Kebersihan Lantai 1"</strong>, <strong>"Lantai 2"</strong>, dll.
                            <br><small>Saat ini Anda dapat melihat semua bed di semua lantai.</small>
                          </div>';
                }
                ?>

                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="h2 mb-2">
                                <i class="fas fa-broom mr-2"></i>Manajemen Kebersihan Bed
                            </h1>
                            <p class="mb-0 opacity-75">Kelola pembersihan tempat tidur setelah pasien keluar</p>
                            <?php if ($role === 'Petugas Kebersihan' && $lantai_petugas !== null): ?>
                                <div class="mt-2">
                                    <span class="floor-badge">
                                        <i class="fas fa-layer-group mr-2"></i>Area Kerja: Lantai <?= $lantai_petugas ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-list mr-2"></i>Daftar Bed yang Perlu Dibersihkan
                            <?php if ($role === 'Petugas Kebersihan' && $lantai_petugas !== null): ?>
                                <span class="badge badge-light ml-2">Lantai <?= $lantai_petugas ?></span>
                            <?php endif; ?>
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr class="text-center">
                                        <th width="3%">No</th>
                                        <th width="12%">Ruangan</th>
                                        <th width="8%">No. Bed</th>
                                        <th width="8%">Kelas</th>
                                        <th width="10%">Status</th>
                                        <th width="15%">Pasien Terakhir</th>
                                        <th width="12%">Waktu Keluar</th>
                                        <th width="12%">Durasi Menunggu</th>
                                        <th width="10%">Prioritas</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    if (empty($row)) {
                                        $empty_message = 'Tidak ada bed yang perlu dibersihkan saat ini';
                                        if ($role === 'Petugas Kebersihan' && $lantai_petugas !== null) {
                                            $empty_message .= ' di Lantai ' . $lantai_petugas;
                                        }
                                        echo '<tr><td colspan="10" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted">' . $empty_message . '</p>
                                                </div>
                                              </td></tr>';
                                    }

                                    foreach ($row as $r) {
                                        $durasi = formatDurasi($r['f_waktumulai']);
                                        
                                        $prioritas = 'Normal';
                                        $badge_prioritas = 'badge-info';
                                        $priority_class = '';
                                        
                                        if ($durasi['total_jam'] >= 2) {
                                            $prioritas = 'Urgent';
                                            $badge_prioritas = 'badge-danger';
                                            $priority_class = 'priority-high';
                                        } elseif ($durasi['total_jam'] >= 1) {
                                            $prioritas = 'Tinggi';
                                            $badge_prioritas = 'badge-warning';
                                        }
                                        
                                        $status_badge = $r['f_sts'] == 'Kotor' ? 'badge-danger' : 'badge-warning';
                                        $pasien_terakhir = $r['nama_pasien_keluar'] ?? '-';
                                        $waktu_keluar = $r['f_waktukeluar'] ? date('d/m/Y H:i', strtotime($r['f_waktukeluar'])) : '-';
                                        
                                        $durasi_text = sprintf('%d jam %d menit %d detik', 
                                                              $durasi['jam'], 
                                                              $durasi['menit'], 
                                                              $durasi['detik']);
                                        
                                        $durasi_color = 'text-info';
                                        if ($durasi['total_jam'] >= 2) {
                                            $durasi_color = 'text-danger';
                                        } elseif ($durasi['total_jam'] >= 1) {
                                            $durasi_color = 'text-warning';
                                        }
                                    ?>
                                        <tr class="<?= $priority_class ?>">
                                            <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                            <td>
                                                <i class="fas fa-door-open mr-2 text-primary"></i>
                                                <strong><?= htmlspecialchars($r['nama_ruangan']) ?></strong>
                                                <br><small class="text-muted">Lantai <?= $r['f_lantai'] ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary p-2">
                                                    <i class="fas fa-bed"></i> <?= htmlspecialchars($r['f_nomorbed']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted"><?= htmlspecialchars($r['kelas_ruangan']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $status_badge ?> p-2">
                                                    <?= htmlspecialchars($r['f_sts']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-user-injured mr-1"></i>
                                                    <?= htmlspecialchars($pasien_terakhir) ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <small>
                                                    <i class="far fa-clock text-muted mr-1"></i>
                                                    <?= $waktu_keluar ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <strong class="<?= $durasi_color ?>">
                                                    <?= $durasi_text ?>
                                                </strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badge_prioritas ?> p-2">
                                                    <?= $prioritas ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="finish.php?id=<?= $r['f_idbedsts'] ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Selesai
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