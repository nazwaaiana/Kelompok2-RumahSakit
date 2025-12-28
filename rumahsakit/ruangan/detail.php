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

if (isset($_GET['log']) && $_GET['log'] == 'logout') {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}

$id_ruangan = (int)$_GET['id'];

// Query detail ruangan dengan JOIN ke standar
$sql = "SELECT 
            r.*,
            s.f_luasmin AS standar_luasmin,
            s.f_luasmaks AS standar_luasmaks,
            s.f_faktorefisiensi AS standar_faktor,
            s.f_keterangan AS standar_keterangan
        FROM t_ruangan r
        LEFT JOIN t_standar_luasbed s ON r.f_kelas = s.f_kelas
        WHERE r.f_idruangan = $id_ruangan";
$row = $db->getALL($sql);

if (!$row) {
    $_SESSION['flash'] = 'Data ruangan tidak ditemukan.';
    header("Location: select.php");
    exit;
}

$ruangan = $row[0];

// Hitung statistik bed di ruangan ini
$sql_stats = "
    SELECT 
        COUNT(*) as total_bed,
        SUM(CASE WHEN f_stsfisik = 'Aktif' THEN 1 ELSE 0 END) as bed_aktif,
        SUM(CASE WHEN f_stsfisik = 'Nonaktif' THEN 1 ELSE 0 END) as bed_nonaktif,
        SUM(CASE WHEN f_stsfisik = 'Maintenance' THEN 1 ELSE 0 END) as bed_maintenance
    FROM t_tempattidur
    WHERE f_idruangan = $id_ruangan
";
$stats = $db->getITEM($sql_stats);

// Hitung bed yang terisi (Rawat Inap aktif)
$sql_terisi = "
    SELECT COUNT(*) as terisi
    FROM t_rawatinap ri
    JOIN t_tempattidur tt ON ri.f_idbed = tt.f_idbed
    WHERE tt.f_idruangan = $id_ruangan 
    AND ri.f_waktukeluar IS NULL
";
$terisi_data = $db->getITEM($sql_terisi);
$bed_terisi = $terisi_data['terisi'] ?? 0;

$total_bed = $stats['total_bed'] ?? 0;
$bed_aktif = $stats['bed_aktif'] ?? 0;
$bed_nonaktif = $stats['bed_nonaktif'] ?? 0;
$bed_maintenance = $stats['bed_maintenance'] ?? 0;
$bed_tersedia = $bed_aktif - $bed_terisi;

// Hitung persentase utilisasi
$utilisasi_persen = $bed_aktif > 0 ? round(($bed_terisi / $bed_aktif) * 100, 1) : 0;

// Status kapasitas
$kapasitas_maks = $ruangan['f_kapasitasmaks'] ?? 0;
$kapasitas_manual = $ruangan['f_kapasitas'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Data Ruangan - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .stat-card {
            border-left: 4px solid;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card.blue { border-left-color: #4e73df; }
        .stat-card.green { border-left-color: #1cc88a; }
        .stat-card.yellow { border-left-color: #f6c23e; }
        .stat-card.red { border-left-color: #e74a3b; }
        .stat-card.gray { border-left-color: #858796; }
        
        .progress-custom {
            height: 30px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-grid .label {
            font-weight: 600;
            color: #5a5c69;
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
                            <i class="fas fa-door-open"></i> Detail Data Ruangan
                        </h1>
                        <a href="select.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ruangan
                        </a>
                    </div>
                    
                    <!-- Card Info Ruangan -->
                    <div class="row">
                        <!-- Kolom Kiri: Informasi Dasar -->
                        <div class="col-xl-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-primary text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-info-circle"></i> Informasi Ruangan: <?= htmlspecialchars($ruangan['f_nama']) ?>
                                    </h6>
                                </div>

                                <div class="card-body">
                                    <div class="info-grid">
                                        <div class="label">ID Ruangan</div>
                                        <div><span class="badge badge-secondary"><?= $ruangan['f_idruangan'] ?></span></div>
                                        
                                        <div class="label">Nama Ruangan</div>
                                        <div><strong><?= htmlspecialchars($ruangan['f_nama']) ?></strong></div>
                                        
                                        <div class="label">Kelas</div>
                                        <div><span class="badge badge-info p-2"><?= htmlspecialchars($ruangan['f_kelas']) ?></span></div>
                                        
                                        <div class="label">Lantai</div>
                                        <div>Lantai <?= htmlspecialchars($ruangan['f_lantai']) ?></div>
                                        
                                        <div class="label">Tanggal Dibuat</div>
                                        <div><?= date('d M Y H:i', strtotime($ruangan['f_created'])) ?></div>
                                        
                                        <div class="label">Terakhir Diperbarui</div>
                                        <div><?= date('d M Y H:i', strtotime($ruangan['f_updated'])) ?></div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="mt-3">
                                        <a href="update.php?id=<?= $ruangan['f_idruangan']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit / Update
                                        </a>
                                        
                                        <a href="delete.php?id=<?= $ruangan['f_idruangan']; ?>" 
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Yakin ingin menghapus data Ruangan <?= htmlspecialchars($ruangan['f_nama']) ?>?');">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kolom Kanan: Informasi Kapasitas & Luas -->
                        <div class="col-xl-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-ruler-combined"></i> Informasi Kapasitas & Luas Ruangan
                                    </h6>
                                </div>

                                <div class="card-body">
                                    <?php if ($ruangan['f_luasruangan']): ?>
                                        <!-- Ada data luas -->
                                        <div class="info-grid">
                                            <div class="label"><i class="fas fa-ruler-combined"></i> Luas Ruangan</div>
                                            <div><strong><?= number_format($ruangan['f_luasruangan'], 2) ?> m²</strong></div>
                                            
                                            <div class="label"><i class="fas fa-bed"></i> Luas per Bed</div>
                                            <div>
                                                <?php if ($ruangan['f_luasperbed']): ?>
                                                    <?= number_format($ruangan['f_luasperbed'], 2) ?> m² 
                                                    <small class="text-muted">(Custom)</small>
                                                <?php else: ?>
                                                    <?= number_format($ruangan['standar_luasmin'], 2) ?> m² 
                                                    <small class="text-muted">(Standar)</small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="label"><i class="fas fa-percentage"></i> Faktor Efisiensi</div>
                                            <div><?= ($ruangan['f_faktorefisiensi'] * 100) ?>%</div>
                                            
                                            <div class="label"><i class="fas fa-calculator"></i> Area Efektif</div>
                                            <div>
                                                <?php 
                                                $area_efektif = $ruangan['f_luasruangan'] * $ruangan['f_faktorefisiensi'];
                                                echo number_format($area_efektif, 2); 
                                                ?> m²
                                            </div>
                                            
                                            <div class="label"><i class="fas fa-check-circle"></i> Kapasitas Maksimal</div>
                                            <div>
                                                <span class="badge badge-success p-2" style="font-size: 1.1rem;">
                                                    <?= $kapasitas_maks ?> bed
                                                </span>
                                                <small class="text-muted">(Berdasarkan regulasi)</small>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="fas fa-info-circle"></i> 
                                            <strong>Standar Kemenkes untuk <?= $ruangan['f_kelas'] ?>:</strong><br>
                                            <small>
                                                <?= $ruangan['standar_keterangan'] ?><br>
                                                Luas: <?= $ruangan['standar_luasmin'] ?> - <?= $ruangan['standar_luasmaks'] ?> m² per bed
                                            </small>
                                        </div>
                                        
                                    <?php else: ?>
                                        <!-- Tidak ada data luas -->
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <strong>Data Luas Ruangan Belum Diisi</strong><br>
                                            <small>
                                                Untuk menghitung kapasitas berdasarkan regulasi, silakan 
                                                <a href="update.php?id=<?= $ruangan['f_idruangan'] ?>">edit ruangan</a> 
                                                dan tambahkan data luas ruangan.
                                            </small>
                                        </div>
                                        
                                        <div class="info-grid">
                                            <div class="label"><i class="fas fa-users"></i> Kapasitas Manual</div>
                                            <div><strong><?= $kapasitas_manual ?> bed</strong></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Statistik Bed -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-warning text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-chart-bar"></i> Statistik & Utilisasi Tempat Tidur
                            </h6>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <!-- Total Bed -->
                                <div class="col-md-3">
                                    <div class="stat-card blue">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Bed Terdaftar
                                        </div>
                                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?= $total_bed ?></div>
                                        <small class="text-muted">Di sistem</small>
                                    </div>
                                </div>
                                
                                <!-- Bed Aktif -->
                                <div class="col-md-3">
                                    <div class="stat-card green">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Bed Aktif
                                        </div>
                                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?= $bed_aktif ?></div>
                                        <small class="text-muted">Operasional</small>
                                    </div>
                                </div>
                                
                                <!-- Bed Terisi -->
                                <div class="col-md-3">
                                    <div class="stat-card red">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Bed Terisi (RI)
                                        </div>
                                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?= $bed_terisi ?></div>
                                        <small class="text-muted">Ada pasien</small>
                                    </div>
                                </div>
                                
                                <!-- Bed Tersedia -->
                                <div class="col-md-3">
                                    <div class="stat-card yellow">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Bed Tersedia
                                        </div>
                                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?= $bed_tersedia ?></div>
                                        <small class="text-muted">Siap digunakan</small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <!-- Maintenance -->
                                <div class="col-md-6">
                                    <div class="stat-card gray">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                            Bed Maintenance
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $bed_maintenance ?></div>
                                        <small class="text-muted">Dalam perbaikan</small>
                                    </div>
                                </div>
                                
                                <!-- Nonaktif -->
                                <div class="col-md-6">
                                    <div class="stat-card gray">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                            Bed Nonaktif
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $bed_nonaktif ?></div>
                                        <small class="text-muted">Tidak operasional</small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Progress Bar Utilisasi -->
                            <h6 class="font-weight-bold text-dark mb-3">
                                <i class="fas fa-chart-line"></i> Tingkat Utilisasi Ruangan
                            </h6>
                            
                            <div class="progress progress-custom mb-2">
                                <div class="progress-bar 
                                    <?php 
                                    if ($utilisasi_persen >= 90) echo 'bg-danger';
                                    elseif ($utilisasi_persen >= 70) echo 'bg-warning';
                                    else echo 'bg-success';
                                    ?>" 
                                    role="progressbar" 
                                    style="width: <?= $utilisasi_persen ?>%" 
                                    aria-valuenow="<?= $utilisasi_persen ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    <?= $utilisasi_persen ?>% Terisi
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    <?= $bed_terisi ?> dari <?= $bed_aktif ?> bed aktif sedang terisi pasien
                                    <?php if ($utilisasi_persen >= 90): ?>
                                        <span class="badge badge-danger ml-2">PENUH</span>
                                    <?php elseif ($utilisasi_persen >= 70): ?>
                                        <span class="badge badge-warning ml-2">HAMPIR PENUH</span>
                                    <?php else: ?>
                                        <span class="badge badge-success ml-2">TERSEDIA</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <?php if ($kapasitas_maks > 0): ?>
                                <hr>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Analisis Kapasitas:</strong><br>
                                    <ul class="mb-0 mt-2">
                                        <li>Kapasitas maksimal berdasarkan luas: <strong><?= $kapasitas_maks ?> bed</strong></li>
                                        <li>Bed aktif saat ini: <strong><?= $bed_aktif ?> bed</strong></li>
                                        <li>Sisa slot untuk bed baru: <strong><?= max(0, $kapasitas_maks - $bed_aktif) ?> bed</strong></li>
                                        <?php if ($bed_aktif > $kapasitas_maks): ?>
                                            <li class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <strong>Peringatan:</strong> Jumlah bed aktif melebihi kapasitas maksimal standar!
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
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