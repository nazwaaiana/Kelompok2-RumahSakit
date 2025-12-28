<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

$id_petugas = $_SESSION['idpetugas'] ?? null;

if (!$id_petugas) {
    header("Location: ../login.php"); 
    exit;
}

$id_pasien = null;
if (isset($_GET['id'])) {
    $id_pasien = (int)$_GET['id'];
} else {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}

if (isset($_POST['simpan_ri'])) {
    $id_bed_terpilih = (int)$_POST['id_bed'];
    $kelas_diminta = $_POST['kelas_diminta'];
    $kelas_ditempatkan = $_POST['kelas_ditempatkan'];
    $status_penempatan = $_POST['status_penempatan'];
    $waktu_masuk = date("Y-m-d H:i:s");
    
    $kelas_diminta = $db->escapeString($kelas_diminta);
    $kelas_ditempatkan = $db->escapeString($kelas_ditempatkan);
    $status_penempatan = $db->escapeString($status_penempatan);
    
    $keterangan_status = "Pasien masuk rawat inap (Request: $kelas_diminta, Ditempatkan: $kelas_ditempatkan)";

    $sql_cek_bed = "SELECT 
                        tbs.f_idbedsts, tbs.f_sts 
                    FROM t_bedstatus tbs
                    WHERE tbs.f_idbed = $id_bed_terpilih 
                    AND tbs.f_waktuselesai IS NULL
                    ORDER BY tbs.f_waktumulai DESC LIMIT 1";
    $bed_status_data = $db->getALL($sql_cek_bed);
    
    $is_available = false;
    $id_bedsts_lama = null;

    if ($bed_status_data) {
        $status_lama = $bed_status_data[0]['f_sts'];
        $id_bedsts_lama = $bed_status_data[0]['f_idbedsts'];
        if ($status_lama == 'Kosong' || $status_lama == 'Siap') {
            $is_available = true;
        }
    } else {
        $is_available = true; 
    }

    if (!$is_available) {
        $_SESSION['flash'] = 'bed_unavailable';
        header("Location: select.php"); 
        exit;
    }

    $sql_insert_ri = "INSERT INTO t_rawatinap 
                      (f_idpasien, f_idbed, f_idpetugas, 
                       f_kelas_diminta, f_kelas_ditempatkan, f_status_penempatan,
                       f_waktumasuk, f_stsbersih, f_created, f_updated)
                      VALUES ('$id_pasien', '$id_bed_terpilih', '$id_petugas', 
                              '$kelas_diminta', '$kelas_ditempatkan', '$status_penempatan',
                              '$waktu_masuk', 'Kotor', '$waktu_masuk', '$waktu_masuk')";
    $result_ri = $db->runSQL($sql_insert_ri);

    if ($result_ri) {
        if ($id_bedsts_lama) {
            $sql_tutup_bed_lama = "UPDATE t_bedstatus SET 
                                   f_waktuselesai = '$waktu_masuk'
                                   WHERE f_idbedsts = $id_bedsts_lama";
            $db->runSQL($sql_tutup_bed_lama);
        }

        $sql_insert_status_baru = "INSERT INTO t_bedstatus 
                                   (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created) 
                                   VALUES ('$id_petugas', '$id_bed_terpilih', 'Terisi', '$waktu_masuk', '$keterangan_status', '$waktu_masuk')";
        $db->runSQL($sql_insert_status_baru);

        $_SESSION['flash'] = 'ri_success';
        header("Location: select.php");
        exit;
    } else {
        $_SESSION['flash'] = 'error';
        header("Location: select.php");
        exit;
    }
}

$sql_pasien = "SELECT * FROM t_pasien WHERE f_idpasien = $id_pasien";
$pasien_data = $db->getALL($sql_pasien);

if (!$pasien_data) {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}
$pasien = $pasien_data[0];

$sql_cek_aktif = "SELECT f_idrawatinap FROM t_rawatinap WHERE f_idpasien = $id_pasien AND f_waktukeluar IS NULL";
$ri_aktif = $db->rowCOUNT($sql_cek_aktif);

if ($ri_aktif > 0) {
    $_SESSION['flash'] = '<div class="alert alert-warning">Pasien <strong>' . htmlspecialchars($pasien['f_nama']) . '</strong> sudah terdaftar Rawat Inap yang aktif. Silakan lakukan pelepasan terlebih dahulu.</div>';
    header("Location: select.php");
    exit;
}

$kelas_hierarchy = ['VVIP' => 5, 'VIP' => 4, 'Kelas 1' => 3, 'Kelas 2' => 2, 'Kelas 3' => 1];

$sql_available_beds = "SELECT 
                        tt.f_idbed, tt.f_nomorbed, 
                        tr.f_idruangan, tr.f_nama AS nama_ruangan, tr.f_kelas, tr.f_lantai,
                        tbs.f_sts AS status_op
                      FROM t_tempattidur tt
                      JOIN t_ruangan tr ON tt.f_idruangan = tr.f_idruangan
                      LEFT JOIN t_bedstatus tbs 
                        ON tt.f_idbed = tbs.f_idbed 
                        AND tbs.f_waktuselesai IS NULL
                      WHERE tt.f_stsfisik = 'Aktif' 
                      AND (tbs.f_sts = 'Siap' OR tbs.f_sts = 'Kosong' OR tbs.f_sts IS NULL)
                      ORDER BY tr.f_kelas DESC, tr.f_lantai, tr.f_nama, tt.f_nomorbed";
$available_beds = $db->getALL($sql_available_beds);

$beds_by_kelas = [];
foreach ($available_beds as $bed) {
    $kelas = $bed['f_kelas'];
    if (!isset($beds_by_kelas[$kelas])) {
        $beds_by_kelas[$kelas] = [];
    }
    $beds_by_kelas[$kelas][] = $bed;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Pendaftaran Rawat Inap - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <style>
        .recommendation-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .recommendation-card:hover {
            transform: translateX(5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
        .recommendation-card.perfect { border-left-color: #1cc88a; background: #f0fff9; }
        .recommendation-card.upgrade { border-left-color: #4e73df; background: #f0f6ff; }
        .recommendation-card.downgrade { border-left-color: #f6c23e; background: #fffbf0; }
        .recommendation-card.unavailable { border-left-color: #e74a3b; background: #fff0f0; }
        
        .bed-option {
            padding: 10px;
            margin: 5px 0;
            border: 2px solid #e3e6f0;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .bed-option:hover {
            border-color: #4e73df;
            background: #f8f9fc;
        }
        .bed-option.selected {
            border-color: #1cc88a;
            background: #d1fae5;
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
                            <i class="fas fa-hospital-user"></i> Pendaftaran Rawat Inap Baru
                        </h1>
                        <a href="select.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-user-injured"></i> Detail Pasien
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr><td width="120"><strong>ID Pasien:</strong></td><td><?= $pasien['f_idpasien'] ?></td></tr>
                                        <tr><td><strong>No. RM:</strong></td><td><?= htmlspecialchars($pasien['f_norekmed']) ?></td></tr>
                                        <tr><td><strong>Nama:</strong></td><td><strong><?= htmlspecialchars($pasien['f_nama']) ?></strong></td></tr>
                                        <tr><td><strong>Tgl Lahir:</strong></td><td><?= date('d-m-Y', strtotime($pasien['f_tgllahir'])) ?></td></tr>
                                        <tr><td><strong>Jenis Kelamin:</strong></td><td><?= $pasien['f_jnskelamin'] ?></td></tr>
                                        <tr><td><strong>Alamat:</strong></td><td><?= htmlspecialchars($pasien['f_alamat']) ?></td></tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card shadow mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-bed"></i> Ketersediaan Bed Real-time
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach (['VVIP', 'VIP', 'Kelas 1', 'Kelas 2', 'Kelas 3'] as $kelas): ?>
                                        <?php 
                                        $jumlah = isset($beds_by_kelas[$kelas]) ? count($beds_by_kelas[$kelas]) : 0;
                                        $badge_class = $jumlah > 0 ? 'badge-success' : 'badge-danger';
                                        $icon = $jumlah > 0 ? 'fa-check-circle' : 'fa-times-circle';
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span><strong><?= $kelas ?></strong></span>
                                            <span class="badge <?= $badge_class ?>">
                                                <i class="fas <?= $icon ?>"></i> <?= $jumlah ?> bed
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <form action="" method="POST" id="formRI">
                                <div class="card shadow mb-4">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-clipboard-check"></i> Step 1: Kelas Ruangan yang Diminta Pasien
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> 
                                            <strong>Instruksi:</strong> Tanyakan ke pasien/keluarga kelas ruangan yang diinginkan. 
                                            Sistem akan memberikan rekomendasi penempatan terbaik.
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="kelas_diminta">
                                                <i class="fas fa-star"></i> Kelas yang Diminta <span class="text-danger">*</span>
                                            </label>
                                            <select name="kelas_diminta" id="kelas_diminta" class="form-control form-control-md" required>
                                                <option value="">-- Pilih Kelas yang Diminta Pasien --</option>
                                                <option value="VVIP">VVIP (Suite Premium)</option>
                                                <option value="VIP">VIP (Private Room)</option>
                                                <option value="Kelas 1">Kelas 1 (Semi Private)</option>
                                                <option value="Kelas 2">Kelas 2 (Semi Private)</option>
                                                <option value="Kelas 3">Kelas 3 (Multi Bed)</option>
                                            </select>
                                        </div>
                                        
                                        <button type="button" id="btnCariRekomendasi" class="btn btn-primary btn-md btn-block" disabled>
                                            <i class="fas fa-search"></i> Cari Rekomendasi Bed
                                        </button>
                                    </div>
                                </div>

                                <div class="card shadow mb-4" id="cardRekomendasi" style="display: none;">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-lightbulb"></i> Step 2: Rekomendasi Sistem Berdasarkan Ketersediaan
                                        </h6>
                                    </div>
                                    <div class="card-body" id="rekomendasiContainer">

                                    </div>
                                </div>

                                <div class="card shadow mb-4" id="cardPilihBed" style="display: none;">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-bed"></i> Step 3: Pilih Bed Spesifik
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="bedOptionsContainer">
                                            <!-- Filled by JavaScript -->
                                        </div>
                                        
                                        <input type="hidden" name="id_bed" id="id_bed" required>
                                        <input type="hidden" name="kelas_ditempatkan" id="kelas_ditempatkan" required>
                                        <input type="hidden" name="status_penempatan" id="status_penempatan" required>
                                        
                                        <hr>
                                        
                                        <button type="submit" name="simpan_ri" class="btn btn-success btn-md btn-block" id="btnSubmit" disabled>
                                            <i class="fas fa-hospital-user"></i> <strong>Konfirmasi & Proses Rawat Inap</strong>
                                        </button>
                                        
                                        <a href="select.php" class="btn btn-secondary btn-md btn-block">
                                            <i class="fas fa-times"></i> Batal
                                        </a>
                                    </div>
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

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
    const bedsByKelas = <?= json_encode($beds_by_kelas) ?>;
    const kelasHierarchy = <?= json_encode($kelas_hierarchy) ?>;
    
    let selectedKelas = null;
    let selectedBedId = null;
    
    $('#kelas_diminta').on('change', function() {
        const value = $(this).val();
        $('#btnCariRekomendasi').prop('disabled', !value);
        
        $('#cardRekomendasi, #cardPilihBed').hide();
        selectedBedId = null;
    });
    
    $('#btnCariRekomendasi').on('click', function() {
        selectedKelas = $('#kelas_diminta').val();
        
        if (!selectedKelas) {
            alert('Pilih kelas terlebih dahulu!');
            return;
        }
        
        generateRekomendasi(selectedKelas);
        
        $('#cardRekomendasi').slideDown();
        
        $('html, body').animate({
            scrollTop: $('#cardRekomendasi').offset().top - 100
        }, 500);
    });
    
    function generateRekomendasi(kelasDiminta) {
        let html = '';
        const rankDiminta = kelasHierarchy[kelasDiminta];
        
        const bedsDiminta = bedsByKelas[kelasDiminta] || [];
        
        if (bedsDiminta.length > 0) {
            html += `
                <div class="recommendation-card perfect card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="text-success mb-3">
                            <i class="fas fa-check-circle"></i> Kelas ${kelasDiminta} TERSEDIA
                        </h5>
                        <p class="mb-2">
                            <i class="fas fa-bed"></i> <strong>${bedsDiminta.length} bed</strong> tersedia di kelas yang diminta.
                        </p>
                        <button type="button" class="btn btn-success btn-sm" onclick="showBedOptions('${kelasDiminta}', 'langsung')">
                            <i class="fas fa-hand-pointer"></i> Pilih Bed ${kelasDiminta}
                        </button>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="recommendation-card unavailable card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="text-danger mb-3">
                            <i class="fas fa-times-circle"></i> Kelas ${kelasDiminta} PENUH
                        </h5>
                        <p class="mb-0">Saat ini tidak ada bed tersedia di kelas ${kelasDiminta}. Lihat opsi alternatif di bawah:</p>
                    </div>
                </div>
            `;
        }
        
        let upgradeOptions = [];
        for (let kelas in kelasHierarchy) {
            if (kelasHierarchy[kelas] > rankDiminta && bedsByKelas[kelas] && bedsByKelas[kelas].length > 0) {
                upgradeOptions.push(kelas);
            }
        }
        
        if (upgradeOptions.length > 0) {
            html += `
                <div class="recommendation-card upgrade card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-arrow-up"></i> OPSI UPGRADE (Kelas Lebih Tinggi)
                        </h5>
                        <p class="mb-3">Tersedia kelas yang lebih baik dari yang diminta:</p>
                        <div class="d-flex flex-wrap gap-2">
            `;
            
            upgradeOptions.forEach(kelas => {
                const jumlah = bedsByKelas[kelas].length;
                html += `
                    <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2" onclick="showBedOptions('${kelas}', 'upgrade')">
                        <i class="fas fa-star"></i> ${kelas} (${jumlah} bed)
                    </button>
                `;
            });
            
            html += `
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle"></i> Upgrade mungkin memerlukan persetujuan biaya tambahan
                        </small>
                    </div>
                </div>
            `;
        }
        
        let downgradeOptions = [];
        for (let kelas in kelasHierarchy) {
            if (kelasHierarchy[kelas] < rankDiminta && bedsByKelas[kelas] && bedsByKelas[kelas].length > 0) {
                downgradeOptions.push(kelas);
            }
        }
        
        if (downgradeOptions.length > 0) {
            html += `
                <div class="recommendation-card downgrade card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="text-warning mb-3">
                            <i class="fas fa-arrow-down"></i> OPSI DOWNGRADE (Alternatif)
                        </h5>
                        <p class="mb-3">Jika pasien setuju dengan kelas yang lebih rendah:</p>
                        <div class="d-flex flex-wrap gap-2">
            `;
            
            downgradeOptions.forEach(kelas => {
                const jumlah = bedsByKelas[kelas].length;
                html += `
                    <button type="button" class="btn btn-outline-warning btn-sm mr-2 mb-2" onclick="showBedOptions('${kelas}', 'downgrade')">
                        <i class="fas fa-bed"></i> ${kelas} (${jumlah} bed)
                    </button>
                `;
            });
            
            html += `
                        </div>
                        <small class="text-danger mt-2 d-block">
                            <i class="fas fa-exclamation-triangle"></i> Konfirmasi persetujuan pasien sebelum memilih opsi ini
                        </small>
                    </div>
                </div>
            `;
        }
        
        if (bedsDiminta.length === 0 && upgradeOptions.length === 0 && downgradeOptions.length === 0) {
            html += `
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-circle"></i> Tidak Ada Bed Tersedia</h5>
                    <p class="mb-0">Saat ini semua bed di RS sedang terisi. Pasien perlu masuk daftar tunggu atau dirujuk ke RS lain.</p>
                </div>
            `;
        }
        
        $('#rekomendasiContainer').html(html);
    }
    
    function showBedOptions(kelas, statusPenempatan) {
        const beds = bedsByKelas[kelas] || [];
        
        if (beds.length === 0) {
            alert('Tidak ada bed tersedia di kelas ' + kelas);
            return;
        }
        
        let html = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Pilih bed spesifik di <strong>${kelas}</strong> (${statusPenempatan}). 
                Klik pada bed untuk memilih.
            </div>
        `;
        
        beds.forEach(bed => {
            html += `
                <div class="bed-option" data-bed-id="${bed.f_idbed}" data-kelas="${kelas}" data-status="${statusPenempatan}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="fas fa-bed"></i> Bed ${bed.f_nomorbed}</strong><br>
                            <small class="text-muted">
                                <i class="fas fa-door-open"></i> ${bed.nama_ruangan} | 
                                <i class="fas fa-layer-group"></i> Lantai ${bed.f_lantai} |
                                <span class="badge badge-info">${bed.f_kelas}</span>
                            </small>
                        </div>
                        <div>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; display: none;"></i>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#bedOptionsContainer').html(html);
        $('#cardPilihBed').slideDown();
        
        $('html, body').animate({
            scrollTop: $('#cardPilihBed').offset().top - 100
        }, 500);
        
        $('.bed-option').on('click', function() {
            $('.bed-option').removeClass('selected');
            $('.bed-option .fa-check-circle').hide();
            
            $(this).addClass('selected');
            $(this).find('.fa-check-circle').show();
            
           selectedBedId = $(this).data('bed-id');
            const klsDitempatkan = $(this).data('kelas');
            const stsPenempatan = $(this).data('status');

            $('#id_bed').val(selectedBedId);
            $('#kelas_ditempatkan').val(klsDitempatkan);
            $('#status_penempatan').val(stsPenempatan);
            $('#btnSubmit').prop('disabled', false);

            let btnText = '<i class="fas fa-hospital-user"></i> <strong>Konfirmasi & Proses Rawat Inap';
            if (stsPenempatan === 'upgrade') {
                btnText += ' (UPGRADE)';
            } else if (stsPenempatan === 'downgrade') {
                btnText += ' (DOWNGRADE)';
            }
            btnText += '</strong>';
            $('#btnSubmit').html(btnText);
        });
    }

    $('#formRI').on('submit', function(e) {
        const kDiminta = $('#kelas_diminta').val();
        const kDitempatkan = $('#kelas_ditempatkan').val();
        const status = $('#status_penempatan').val();

        if (status === 'upgrade') {
            return confirm(`Pasien meminta kelas ${kDiminta} tetapi akan ditempatkan di ${kDitempatkan} (UPGRADE). Lanjutkan proses?`);
        } else if (status === 'downgrade') {
            return confirm(`Pasien meminta kelas ${kDiminta} tetapi akan ditempatkan di ${kDitempatkan} (DOWNGRADE). Pastikan pasien sudah setuju! Lanjutkan?`);
        }
    });
    </script>
</body>
</html>