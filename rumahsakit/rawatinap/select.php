<?php
require_once '../config.php';
date_default_timezone_set('Asia/Jakarta');
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Petugas Kebersihan', 'Admisi']);

if (!isset($_SESSION['idpetugas'])) {
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
$p = max(1, min($p, $halaman > 0 ? $halaman : 1)); 
$mulai = ($p - 1) * $banyak;

$sql = "SELECT p.f_idpasien, p.f_norekmed, p.f_nama, p.f_tgllahir, p.f_jnskelamin, p.f_notlp, 
        ri_aktif.f_idrawatinap, ri_aktif.f_waktumasuk, ri_aktif.f_waktukeluar, 
        tt_aktif.f_nomorbed, tr_aktif.f_nama AS nama_ruangan_aktif, tr_aktif.f_kelas AS kelas_aktif,
        (SELECT ri_last.f_waktumasuk FROM t_rawatinap ri_last WHERE ri_last.f_idpasien = p.f_idpasien 
         AND ri_last.f_waktukeluar IS NOT NULL ORDER BY ri_last.f_waktukeluar DESC LIMIT 1) AS waktu_masuk_terakhir,
        (SELECT ri_last.f_waktukeluar FROM t_rawatinap ri_last WHERE ri_last.f_idpasien = p.f_idpasien 
         AND ri_last.f_waktukeluar IS NOT NULL ORDER BY ri_last.f_waktukeluar DESC LIMIT 1) AS waktu_keluar_terakhir,
        (SELECT tt_last.f_nomorbed FROM t_rawatinap ri_last
         LEFT JOIN t_tempattidur tt_last ON ri_last.f_idbed = tt_last.f_idbed
         WHERE ri_last.f_idpasien = p.f_idpasien AND ri_last.f_waktukeluar IS NOT NULL
         ORDER BY ri_last.f_waktukeluar DESC LIMIT 1) AS nomor_bed_terakhir,
        (SELECT tr_last.f_nama FROM t_rawatinap ri_last
         LEFT JOIN t_tempattidur tt_last ON ri_last.f_idbed = tt_last.f_idbed
         LEFT JOIN t_ruangan tr_last ON tt_last.f_idruangan = tr_last.f_idruangan
         WHERE ri_last.f_idpasien = p.f_idpasien AND ri_last.f_waktukeluar IS NOT NULL
         ORDER BY ri_last.f_waktukeluar DESC LIMIT 1) AS nama_ruangan_terakhir,
        (SELECT tr_last.f_kelas FROM t_rawatinap ri_last
         LEFT JOIN t_tempattidur tt_last ON ri_last.f_idbed = tt_last.f_idbed
         LEFT JOIN t_ruangan tr_last ON tt_last.f_idruangan = tr_last.f_idruangan
         WHERE ri_last.f_idpasien = p.f_idpasien AND ri_last.f_waktukeluar IS NOT NULL
         ORDER BY ri_last.f_waktukeluar DESC LIMIT 1) AS kelas_terakhir,
        CASE 
            WHEN ri_aktif.f_idrawatinap IS NOT NULL THEN 'Sedang RI'
            WHEN ri_aktif.f_idrawatinap IS NULL AND (SELECT COUNT(*) FROM t_rawatinap ri_old WHERE ri_old.f_idpasien = p.f_idpasien) > 0 THEN 'Sudah Keluar'
            ELSE 'Belum RI'
        END as status_ri,
        CASE
            WHEN ri_aktif.f_idrawatinap IS NOT NULL THEN 'badge-danger'
            WHEN ri_aktif.f_idrawatinap IS NULL AND (SELECT COUNT(*) FROM t_rawatinap ri_old WHERE ri_old.f_idpasien = p.f_idpasien) > 0 THEN 'badge-warning'
            ELSE 'badge-success'
        END as badge_ri
        FROM t_pasien p
        LEFT JOIN t_rawatinap ri_aktif ON p.f_idpasien = ri_aktif.f_idpasien AND ri_aktif.f_waktukeluar IS NULL
        LEFT JOIN t_tempattidur tt_aktif ON ri_aktif.f_idbed = tt_aktif.f_idbed
        LEFT JOIN t_ruangan tr_aktif ON tt_aktif.f_idruangan = tr_aktif.f_idruangan
        ORDER BY p.f_idpasien DESC 
        LIMIT $mulai, $banyak";
    
$row = $db->getALL($sql);
$no = 1 + $mulai;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Rawat Inap - RS InsanMedika</title>

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
        
        .status-sedang-ri { background-color: #e74a3b; }
        .status-sudah-keluar { background-color: #f6c23e; }
        .status-belum-ri { background-color: #1cc88a; }
        
        .btn-sm {
            border-radius: 8px;
            padding: 0.4rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(28, 200, 138, 0.4);
        }
        
        .btn-warning:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(246, 194, 62, 0.4);
        }
        
        .btn-info:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(54, 185, 204, 0.4);
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
        
        .alert {
            border-radius: 12px;
            border: none;
        }
        
        .info-card {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 0.75rem;
            border-left: 3px solid #667eea;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
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
                    $msg = '';
                    $alert_class = 'alert-info';
                    $icon = '<i class="fas fa-info-circle mr-2"></i>';

                    switch ($_SESSION['flash']) {
                        case 'ri_success':
                            $msg = 'Pasien berhasil didaftarkan <strong>Rawat Inap</strong>.';
                            $alert_class = 'alert-success';
                            $icon = '<i class="fas fa-check-circle mr-2"></i>';
                            break;
                        case 'pelepasan_success':
                            $msg = 'Pasien berhasil dilepas dari <strong>Rawat Inap</strong>.';
                            $alert_class = 'alert-warning';
                            $icon = '<i class="fas fa-sign-out-alt mr-2"></i>';
                            break;
                        case 'error':
                            $msg = 'Terjadi kesalahan operasional. Silakan coba lagi.';
                            $alert_class = 'alert-danger';
                            $icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
                            break;
                        case 'bed_unavailable':
                            $msg = 'Tempat tidur yang dipilih sudah terisi atau tidak siap. Operasi gagal.';
                            $alert_class = 'alert-danger';
                            $icon = '<i class="fas fa-exclamation-triangle mr-2"></i>';
                            break;
                    }

                    if ($msg) {
                        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show shadow-sm">
                                ' . $icon . $msg . '
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            </div>';
                    }
                    unset($_SESSION['flash']);
                }
                ?>

                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="h2 mb-2">
                                <i class="fas fa-procedures mr-2"></i>Manajemen Rawat Inap
                            </h1>
                            <p class="mb-0 opacity-75">Kelola data pasien dan status rawat inap</p>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-table mr-2"></i>Daftar Pasien & Status Rawat Inap
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr class="text-center">
                                        <th width="3%">No</th>
                                        <th width="8%">No. RM</th>
                                        <th width="15%">Nama Pasien</th>
                                        <th width="5%">JK</th>
                                        <th width="18%">Info Ruangan & Bed</th>
                                        <th width="12%">Waktu Masuk</th>
                                        <th width="12%">Waktu Keluar</th> 
                                        <th width="10%">Status RI</th>
                                        <th width="20%">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (!empty($row)): ?>
                                        <?php foreach ($row as $r) { 
                                            $status = $r['status_ri'];
                                            $status_indicator = '';

                                            if ($status == 'Sedang RI') {
                                                $nama_ruangan = htmlspecialchars($r['nama_ruangan_aktif']);
                                                $nomor_bed = htmlspecialchars($r['f_nomorbed']);
                                                $kelas = htmlspecialchars($r['kelas_aktif']);
                                                $waktu_masuk_str = date('d/m/Y H:i', strtotime($r['f_waktumasuk']));
                                                $waktu_keluar_str = 'Masih Dirawat';
                                                $riwayat_id = $r['f_idrawatinap'];
                                                $status_indicator = 'status-sedang-ri';
                                            } elseif ($status == 'Sudah Keluar') {
                                                $nama_ruangan = htmlspecialchars($r['nama_ruangan_terakhir'] ?? '-');
                                                $nomor_bed = htmlspecialchars($r['nomor_bed_terakhir'] ?? '-');
                                                $kelas = htmlspecialchars($r['kelas_terakhir'] ?? '-');
                                                $waktu_masuk_str = $r['waktu_masuk_terakhir'] ? date('d/m/Y H:i', strtotime($r['waktu_masuk_terakhir'])) : '-';
                                                $waktu_keluar_str = $r['waktu_keluar_terakhir'] ? date('d/m/Y H:i', strtotime($r['waktu_keluar_terakhir'])) : '-';
                                                $riwayat_id = null;
                                                $status_indicator = 'status-sudah-keluar';
                                            } else { 
                                                $nama_ruangan = '-';
                                                $nomor_bed = '-';
                                                $kelas = '-';
                                                $waktu_masuk_str = '<span class="text-muted">Belum pernah</span>';
                                                $waktu_keluar_str = '<span class="text-muted">-</span>';
                                                $riwayat_id = null;
                                                $status_indicator = 'status-belum-ri';
                                            }
                                        ?>
                                            <tr>
                                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                                <td>
                                                    <span class="badge badge-primary badge-kapasitas">
                                                        <?= htmlspecialchars($r['f_norekmed']) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <strong><?= htmlspecialchars($r['f_nama']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= date('d M Y', strtotime($r['f_tgllahir'])) ?>
                                                    </small>
                                                </td>
                                        
                                                <td class="text-center">
                                                        <?php if ($r['f_jnskelamin'] == 'Laki-laki'): ?>
                                                            <i class="fas fa-mars text-primary"></i> L
                                                        <?php else: ?>
                                                            <i class="fas fa-venus text-danger"></i> P
                                                        <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if ($nama_ruangan != '-'): ?>
                                                        <div>
                                                            <small>
                                                                <i class="fas fa-door-open text-primary"></i> <strong>Ruangan:</strong> <?= $nama_ruangan ?><br>
                                                                <i class="fas fa-tag text-info"></i> <strong>Kelas:</strong> <?= $kelas ?><br>
                                                                <i class="fas fa-bed text-success"></i> <strong>Bed:</strong> <?= $nomor_bed ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="far fa-calendar-plus text-success"></i>
                                                        <?= $waktu_masuk_str ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="far fa-calendar-check text-warning"></i>
                                                        <?= $waktu_keluar_str ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?= $r['badge_ri'] ?> badge-kapasitas">
                                                        <span class="status-indicator <?= $status_indicator ?>"></span>
                                                        <?= $r['status_ri'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($r['status_ri'] == 'Sedang RI'): ?>
                                                        <a href="pelepasan.php?id=<?= $riwayat_id ?>" 
                                                            class="btn btn-warning btn-sm mb-1" 
                                                            title="Pelepasan Pasien">
                                                            <i class="fas fa-sign-out-alt"></i> Pelepasan
                                                        </a>
                                                    <?php elseif ($r['status_ri'] == 'Belum RI' || $r['status_ri'] == 'Sudah Keluar'): ?>
                                                        <a href="insert.php?id=<?= $r['f_idpasien'] ?>" 
                                                            class="btn btn-success btn-sm mb-1" 
                                                            title="Daftarkan Rawat Inap">
                                                            <i class="fas fa-procedures"></i> Daftar RI
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="riwayat.php?pasien_id=<?= $r['f_idpasien'] ?>" 
                                                        class="btn btn-info btn-sm mb-1" 
                                                        title="Lihat Riwayat">
                                                        <i class="fas fa-history"></i> Riwayat
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">Data pasien tidak ditemukan</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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