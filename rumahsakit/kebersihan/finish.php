<?php
ob_start(); 
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Petugas Kebersihan']);

if (!isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php");
    exit;
}
$id_petugas = $_SESSION['idpetugas'];
$role = $_SESSION['role'];

$sql_petugas = "SELECT f_unitkerja, f_nama FROM t_petugas WHERE f_idpetugas = '$id_petugas'";
$data_petugas = $db->getALL($sql_petugas);

$lantai_petugas = null;
$nama_petugas = isset($data_petugas[0]['f_nama']) ? $data_petugas[0]['f_nama'] : '';

if (!empty($data_petugas)) {
    $unit_kerja = $data_petugas[0]['f_unitkerja'];
    if (preg_match('/lantai\s*(\d+)/i', $unit_kerja, $matches)) {
        $lantai_petugas = (int)$matches[1];
    }
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: select.php");
    exit;
}

$id_bedsts = (int)$_GET['id'];
$waktu_selesai = date('Y-m-d H:i:s');

$sql_status = "SELECT
                    bs.f_idbedsts,
                    bs.f_idbed,
                    bs.f_sts,
                    bs.f_waktumulai,
                    bs.f_keterangan,
                    bs.f_idpetugas as petugas_awal,
                    tt.f_nomorbed,
                    tt.f_stsfisik,
                    r.f_nama AS nama_ruangan,
                    r.f_kelas,
                    r.f_lantai,
                    ri.f_idrawatinap,
                    ri.f_waktukeluar,
                    p.f_nama AS nama_pasien,
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
                    bs.f_idbedsts = $id_bedsts
                    AND bs.f_waktuselesai IS NULL
                    AND bs.f_sts IN ('Pembersihan', 'Kotor')";

$status_data = $db->getALL($sql_status);

if (empty($status_data)) {
    $_SESSION['flash'] = 'error';
    $_SESSION['flash_message'] = 'Data pembersihan tidak ditemukan atau sudah diselesaikan.';
    header("Location: select.php");
    exit;
}

$status = $status_data[0];
$id_bed = $status['f_idbed'];
$lantai_bed = $status['f_lantai'];

if ($role === 'Petugas Kebersihan' && $lantai_petugas !== null) {
    if ($lantai_bed != $lantai_petugas) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = '<i class="fas fa-ban"></i> <strong>Akses Ditolak!</strong><br>Anda hanya dapat membersihkan bed di <strong>Lantai ' . $lantai_petugas . '</strong>. Bed ini berada di <strong>Lantai ' . $lantai_bed . '</strong> dan harus ditangani oleh petugas kebersihan lantai tersebut.';
        header("Location: select.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->runSQL("START TRANSACTION");
        $sql_finish = "UPDATE t_bedstatus
                       SET f_waktuselesai = '$waktu_selesai',
                           f_idpetugas = '$id_petugas'
                       WHERE f_idbedsts = $id_bedsts";
        $db->runSQL($sql_finish);

        $keterangan_siap = addslashes("Pembersihan selesai dilakukan oleh " . $nama_petugas . " pada " . date('d/m/Y H:i:s'));
        
        $sql_insert_siap = "INSERT INTO t_bedstatus
                            (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                            VALUES ('$id_petugas', '$id_bed', 'Siap', '$waktu_selesai', 
                            '$keterangan_siap', '$waktu_selesai')";
        $db->runSQL($sql_insert_siap);

        $sql_master = "UPDATE t_tempattidur 
                       SET f_stsfisik = 'Aktif'
                       WHERE f_idbed = '$id_bed'";
        $db->runSQL($sql_master);

        if (!empty($status['f_idrawatinap'])) {
            $id_rawatinap = $status['f_idrawatinap'];
            $sql_update_ri = "UPDATE t_rawatinap 
                             SET f_stsbersih = 'Siap'
                             WHERE f_idrawatinap = $id_rawatinap";
            $db->runSQL($sql_update_ri);
        }
        
        $db->runSQL("COMMIT");
        
        $_SESSION['flash'] = 'success';
        $_SESSION['flash_message'] = '<i class="fas fa-check-circle"></i> <strong>Pembersihan Berhasil Diselesaikan!</strong><br>Bed <strong>' . htmlspecialchars($status['f_nomorbed']) . '</strong> di <strong>' . htmlspecialchars($status['nama_ruangan']) . '</strong> kini berstatus <strong>SIAP</strong> untuk digunakan.<br><small class="text-muted">Diselesaikan oleh: ' . htmlspecialchars($nama_petugas) . ' pada ' . date('d/m/Y H:i:s') . '</small>';
        header("Location: select.php");
        exit;

    } catch (Exception $e) {
        $db->runSQL("ROLLBACK");
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = '<i class="fas fa-exclamation-triangle"></i> <strong>Terjadi Kesalahan:</strong> ' . $e->getMessage();
        header("Location: select.php");
        exit;
    }
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

$waktu_untuk_durasi = $status['waktu_referensi'] ?? $status['f_waktumulai'];
$durasi = formatDurasi($waktu_untuk_durasi);
$durasi_jam = $durasi['jam'];
$durasi_menit = $durasi['menit'];
$durasi_detik = $durasi['detik'];
$waktu_keluar_display = isset($status['f_waktukeluar']) && $status['f_waktukeluar'] 
    ? date('d/m/Y H:i', strtotime($status['f_waktukeluar'])) 
    : '-';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Selesaikan Pembersihan - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .confirmation-card {
            border: 3px solid #1cc88a;
            border-radius: 15px;
        }
        
        .check-animation {
            animation: checkmark 0.5s ease-in-out;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .petugas-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .floor-match {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            font-size: 0.85rem;
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

                <div class="container-fluid mt-4">

                    <h1 class="h3 mb-4 text-gray-800">
                        <i class="fas fa-broom text-info"></i> Konfirmasi Penyelesaian Pembersihan
                    </h1>

                    <div class="petugas-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle fa-2x mr-3"></i>
                            <div>
                                <strong>Petugas yang Menyelesaikan:</strong> <?= htmlspecialchars($nama_petugas) ?>
                                <?php if ($lantai_petugas !== null): ?>
                                    <br><small>Area Kerja: Lantai <?= $lantai_petugas ?></small>
                                <?php else: ?>
                                    <br><small>Area Kerja: Semua Lantai</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($role === 'Petugas Kebersihan' && $lantai_petugas === null): ?>
                        <?php 
                        $unit_kerja_display = isset($data_petugas[0]['f_unitkerja']) ? $data_petugas[0]['f_unitkerja'] : 'Tidak ada';
                        ?>
                        <div class="warning-no-floor">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Perhatian:</strong> 
                            Unit kerja Anda (<strong><?= htmlspecialchars($unit_kerja_display) ?></strong>) belum terkonfigurasi dengan lantai tertentu. 
                            <br>Hubungi admin untuk mengubah unit kerja Anda menjadi format seperti: <strong>"Kebersihan Lantai 1"</strong>, <strong>"Lantai 2"</strong>, dll.
                        </div>
                    <?php endif; ?>

                    <div class="card confirmation-card shadow mb-4">
                        <div class="card-header py-3 bg-gradient-success text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-check-circle check-animation"></i> Selesaikan Pembersihan Bed #<?= htmlspecialchars($status['f_nomorbed']) ?>
                            </h6>
                        </div>
                        <div class="card-body">

                            <?php if ($role === 'Petugas Kebersihan' && $lantai_petugas !== null): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <strong>Validasi Lantai Berhasil</strong>
                                    <div class="mt-2">
                                        <span class="floor-match">
                                            <i class="fas fa-layer-group mr-2"></i>
                                            Bed Lantai <?= $lantai_bed ?> = Area Kerja Anda (Lantai <?= $lantai_petugas ?>)
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="alert alert-info">
                                <strong><i class="fas fa-info-circle"></i> Informasi Proses:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Status pembersihan akan ditutup dan <strong>tercatat sebagai riwayat Anda</strong></li>
                                    <li>Bed akan otomatis berstatus <strong>SIAP</strong> untuk digunakan</li>
                                    <li>Status kebersihan di Rawat Inap akan diperbarui</li>
                                    <li>Status fisik master bed akan diset ke <strong>AKTIF</strong></li>
                                    <li>Data pembersihan oleh <strong><?= htmlspecialchars($nama_petugas) ?></strong> akan tersimpan di sistem</li>
                                </ul>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="text-primary font-weight-bold mb-3">
                                                <i class="fas fa-bed"></i> Detail Bed
                                            </h6>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td width="140"><strong>Nomor Bed:</strong></td>
                                                    <td><span class="badge badge-primary p-2"><?= htmlspecialchars($status['f_nomorbed']) ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Ruangan:</strong></td>
                                                    <td><?= htmlspecialchars($status['nama_ruangan']) ?> (<?= htmlspecialchars($status['f_kelas']) ?>)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Lantai:</strong></td>
                                                    <td><span class="badge badge-info">Lantai <?= $lantai_bed ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Status Saat Ini:</strong></td>
                                                    <td><span class="badge badge-warning p-2"><?= htmlspecialchars($status['f_sts']) ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Pasien Terakhir:</strong></td>
                                                    <td><?= htmlspecialchars($status['nama_pasien'] ?? '-') ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="text-success font-weight-bold mb-3">
                                                <i class="fas fa-clock"></i> Informasi Waktu
                                            </h6>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td width="140"><strong>Waktu Keluar Pasien:</strong></td>
                                                    <td><?= $waktu_keluar_display ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Mulai Kotor:</strong></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($status['f_waktumulai'])) ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Waktu Sekarang:</strong></td>
                                                    <td><?= date('d/m/Y H:i') ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Durasi Menunggu:</strong></td>
                                                    <td>
                                                        <strong class="<?= $durasi['total_jam'] >= 2 ? 'text-danger' : ($durasi['total_jam'] >= 1 ? 'text-warning' : 'text-success') ?>">
                                                            <?= $durasi_jam ?> jam <?= $durasi_menit ?> menit <?= $durasi_detik ?> detik
                                                        </strong>
                                                    </td>
                                                </tr>
                                                <?php if ($durasi['total_jam'] >= 2): ?>
                                                <tr>
                                                    <td colspan="2">
                                                        <div class="alert alert-danger mb-0 py-2">
                                                            <small><i class="fas fa-exclamation-triangle"></i> <strong>Perhatian:</strong> Pembersihan melebihi target waktu (2 jam)</small>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($status['f_keterangan'])): ?>
                            <div class="alert alert-secondary">
                                <strong><i class="fas fa-comment"></i> Keterangan:</strong><br>
                                <?= htmlspecialchars($status['f_keterangan']) ?>
                            </div>
                            <?php endif; ?>

                            <hr>

                            <form action="finish.php?id=<?= $id_bedsts ?>" method="POST">
                                <p class="text-center font-weight-bold text-success mb-4" style="font-size: 1.1rem;">
                                    <i class="fas fa-question-circle"></i> Apakah pembersihan untuk bed ini sudah selesai?
                                </p>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-lg btn-icon-split shadow">
                                        <span class="icon text-white-50"><i class="fas fa-check-circle"></i></span>
                                        <span class="text">Ya, Pembersihan Selesai</span>
                                    </button>
                                    <a href="select.php" class="btn btn-secondary btn-lg ml-2">
                                        <i class="fas fa-times"></i> Batalkan
                                    </a>
                                </div>
                            </form>

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