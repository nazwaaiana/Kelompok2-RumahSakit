<?php 
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

$id_ruangan = null;
if (isset($_GET['id'])) {
    $id_ruangan = (int)$_GET['id'];
} else {
    header("Location: select.php");
    exit;
}

$sql_standar = "SELECT * FROM t_standar_luasbed ORDER BY f_kelas";
$standar_list = $db->getALL($sql_standar);
$standar_json = json_encode($standar_list);

if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama']);
    $kelas = $_POST['kelas'];
    $lantai = (int)$_POST['lantai'];
    $kapasitas = (int)$_POST['kapasitas'];
    $luas_ruangan = !empty($_POST['luas_ruangan']) ? (float)$_POST['luas_ruangan'] : null;
    $luas_perbed = !empty($_POST['luas_perbed']) ? (float)$_POST['luas_perbed'] : null;
    $faktor_efisiensi = !empty($_POST['faktor_efisiensi']) ? (float)$_POST['faktor_efisiensi'] : 0.65;
    
    $updated = date("Y-m-d H:i:s");
    $nama = $db->escapeString($nama);
    $kelas = $db->escapeString($kelas);
    
    $kapasitas_maks = 0;
    
    if ($luas_ruangan) {
        if (!$luas_perbed) {
            $sql_get_standar = "SELECT f_luasmin FROM t_standar_luasbed WHERE f_kelas = '$kelas'";
            $standar_data = $db->getITEM($sql_get_standar);
            $luas_perbed = $standar_data['f_luasmin'] ?? 6; 
        }
        
        $area_efektif = $luas_ruangan * $faktor_efisiensi;
        $kapasitas_maks = floor($area_efektif / $luas_perbed);
        
        $sql_count_bed = "SELECT COUNT(*) as jumlah FROM t_tempattidur 
                          WHERE f_idruangan = $id_ruangan AND f_stsfisik = 'Aktif'";
        $bed_aktif = $db->getITEM($sql_count_bed);
        $jumlah_bed_aktif = (int)($bed_aktif['jumlah'] ?? 0);
        
        if ($kapasitas_maks < $jumlah_bed_aktif) {
            $_SESSION['flash'] = 'error';
            $_SESSION['flash_message'] = "Tidak dapat memperkecil ruangan! Kapasitas baru ($kapasitas_maks bed) lebih kecil dari jumlah bed aktif saat ini ($jumlah_bed_aktif bed). Nonaktifkan bed terlebih dahulu.";
            header("Location: update.php?id=$id_ruangan");
            exit;
        }
        
        $toleransi = $kapasitas_maks * 1.2;
        if ($kapasitas > $toleransi) {
            $_SESSION['flash'] = 'error';
            $_SESSION['flash_message'] = "Kapasitas manual ($kapasitas bed) terlalu besar! Maksimal berdasarkan luas adalah $kapasitas_maks bed (toleransi: " . floor($toleransi) . " bed).";
            header("Location: update.php?id=$id_ruangan");
            exit;
        }
    }

    $sql_update = "UPDATE t_ruangan SET 
        f_nama = '$nama',
        f_kelas = '$kelas',
        f_lantai = '$lantai',
        f_kapasitas = '$kapasitas',
        f_luasruangan = " . ($luas_ruangan ? "'$luas_ruangan'" : "NULL") . ",
        f_luasperbed = " . ($luas_perbed ? "'$luas_perbed'" : "NULL") . ",
        f_faktorefisiensi = '$faktor_efisiensi',
        f_kapasitasmaks = '$kapasitas_maks',
        f_updated = '$updated'
        WHERE f_idruangan = $id_ruangan";

    $result = $db->runSQL($sql_update);

    if ($result) {
        $_SESSION['flash'] = 'success_update';
    } else {
        $_SESSION['flash'] = 'error_update';
    }

    header("Location: select.php");
    exit;
}

$sql_select = "SELECT * FROM t_ruangan WHERE f_idruangan = $id_ruangan";
$ruangan_data = $db->getALL($sql_select);

if (!$ruangan_data) {
    header("Location: select.php");
    exit;
}

$ruangan = $ruangan_data[0];

$sql_bed_aktif = "SELECT COUNT(*) as jumlah FROM t_tempattidur 
                  WHERE f_idruangan = $id_ruangan AND f_stsfisik = 'Aktif'";
$bed_aktif_data = $db->getITEM($sql_bed_aktif);
$jumlah_bed_aktif = (int)($bed_aktif_data['jumlah'] ?? 0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ubah Data Ruangan - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .calculation-box {
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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

                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['flash_message'] ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <h1 class="h3 mb-4 text-gray-800">
                    <i class="fas fa-edit"></i> Ubah Data Ruangan
                </h1>
                
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> <strong>Status Bed Aktif:</strong> 
                    Ruangan ini memiliki <strong><?= $jumlah_bed_aktif ?> bed aktif</strong>. 
                    Pastikan perubahan luas ruangan tidak mengurangi kapasitas di bawah jumlah bed aktif.
                </div>

                <div class="card shadow">
                    <div class="card-header py-3 bg-warning text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-door-open"></i> Form Update Ruangan: <?= htmlspecialchars($ruangan['f_nama']) ?>
                        </h6>
                    </div>

                    <div class="card-body">

                        <form action="" method="POST" id="formRuangan">

                            <h5 class="text-primary mb-3"><i class="fas fa-clipboard-list"></i> Informasi Dasar</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-door-open"></i> Nama Ruangan <span class="text-danger">*</span></label>
                                        <input type="text" name="nama" id="nama" class="form-control"
                                               value="<?= htmlspecialchars($ruangan['f_nama']); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-tags"></i> Kelas Ruangan <span class="text-danger">*</span></label>
                                        <select name="kelas" id="kelas" class="form-control" required>
                                            <option value="">-- Pilih Kelas --</option>
                                            <option value="VVIP" <?= ($ruangan['f_kelas']=="VVIP") ? "selected" : "" ?>>VVIP</option>
                                            <option value="VIP" <?= ($ruangan['f_kelas']=="VIP") ? "selected" : "" ?>>VIP</option>
                                            <option value="Kelas 1" <?= ($ruangan['f_kelas']=="Kelas 1") ? "selected" : "" ?>>Kelas 1</option>
                                            <option value="Kelas 2" <?= ($ruangan['f_kelas']=="Kelas 2") ? "selected" : "" ?>>Kelas 2</option>
                                            <option value="Kelas 3" <?= ($ruangan['f_kelas']=="Kelas 3") ? "selected" : "" ?>>Kelas 3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Lantai <span class="text-danger">*</span></label>
                                <input type="number" name="lantai" id="lantai" class="form-control"
                                       value="<?= $ruangan['f_lantai']; ?>" min="1" required>
                            </div>

                            <hr>

                            <h5 class="text-success mb-3"><i class="fas fa-calculator"></i> Kalkulasi Kapasitas Bed</h5>

                            <div class="info-box">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Perhatian:</strong>
                                Jika Anda memperkecil luas ruangan, pastikan kapasitas baru tidak lebih kecil dari 
                                jumlah bed aktif (<?= $jumlah_bed_aktif ?> bed).
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-ruler-combined"></i> Luas Ruangan (m²)</label>
                                        <input type="number" step="0.01" name="luas_ruangan" id="luas_ruangan" 
                                               class="form-control" 
                                               value="<?= $ruangan['f_luasruangan'] ?? '' ?>" 
                                               placeholder="Contoh: 50.00" min="0">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-bed"></i> Luas per Bed (m²)</label>
                                        <input type="number" step="0.01" name="luas_perbed" id="luas_perbed" 
                                               class="form-control" 
                                               value="<?= $ruangan['f_luasperbed'] ?? '' ?>"
                                               placeholder="Auto dari standar">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-percentage"></i> Faktor Efisiensi</label>
                                        <input type="number" step="0.01" name="faktor_efisiensi" id="faktor_efisiensi" 
                                               class="form-control" 
                                               value="<?= $ruangan['f_faktorefisiensi'] ?? 0.65 ?>" 
                                               min="0" max="1">
                                    </div>
                                </div>
                            </div>

                            <div class="calculation-box" id="calculationBox" style="display:none;">
                                <h6 class="font-weight-bold text-primary mb-3">
                                    <i class="fas fa-calculator"></i> Hasil Kalkulasi
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <small class="text-muted">Standar Luas per Bed</small>
                                            <h4 class="text-info" id="display_luasperbed">-</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <small class="text-muted">Area Efektif</small>
                                            <h4 class="text-info" id="display_areaefektif">-</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <small class="text-muted">Bed Aktif Saat Ini</small>
                                            <h4 class="text-warning" id="display_bedaktif"><?= $jumlah_bed_aktif ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <small class="text-muted"><strong>KAPASITAS MAKS</strong></small>
                                            <h2 class="text-success font-weight-bold" id="display_kapasitas">-</h2>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="warning-container"></div>
                            </div>

                            <hr>

                            <h5 class="text-warning mb-3"><i class="fas fa-users"></i> Kapasitas Tempat Tidur</h5>
                            
                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Kapasitas <span class="text-danger">*</span></label>
                                <input type="number" name="kapasitas" id="kapasitas" class="form-control"
                                       value="<?= $ruangan['f_kapasitas']; ?>" min="1" required>
                                <small class="form-text text-muted">
                                    Kapasitas saat ini: <?= $ruangan['f_kapasitas'] ?> bed 
                                    (Maksimal otomatis: <?= $ruangan['f_kapasitasmaks'] ?? 'belum dihitung' ?>)
                                </small>
                            </div>

                            <hr>

                            <div class="form-group mb-0">
                                <button type="submit" name="simpan" class="btn btn-warning btn-lg">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>

                                <a href="select.php" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>

                        </form>

                    </div>
                </div>

            </div>

        </div>
    </div>

</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
const standarData = <?= $standar_json ?>;
const bedAktif = <?= $jumlah_bed_aktif ?>;

function hitungKapasitas() {
    const kelas = $('#kelas').val();
    const luasRuangan = parseFloat($('#luas_ruangan').val());
    let luasPerBed = parseFloat($('#luas_perbed').val());
    const faktorEfisiensi = parseFloat($('#faktor_efisiensi').val()) || 0.65;
    
    if (!kelas || !luasRuangan || luasRuangan <= 0) {
        $('#calculationBox').hide();
        return;
    }
    
    const standar = standarData.find(s => s.f_kelas === kelas);
    
    if (!standar) {
        alert('Standar untuk kelas ' + kelas + ' tidak ditemukan!');
        return;
    }
    
    if (!luasPerBed || luasPerBed <= 0) {
        luasPerBed = parseFloat(standar.f_luasmin);
        $('#luas_perbed').attr('placeholder', 'Standar: ' + luasPerBed + ' m²');
    }
    
    const areaEfektif = luasRuangan * faktorEfisiensi;
    const kapasitasMaks = Math.floor(areaEfektif / luasPerBed);
    
    $('#display_luasperbed').text(luasPerBed.toFixed(2) + ' m²');
    $('#display_areaefektif').text(areaEfektif.toFixed(2) + ' m²');
    $('#display_kapasitas').text(kapasitasMaks + ' bed');
    
    $('#warning-container').empty();
    
    if (kapasitasMaks < bedAktif) {
        $('#warning-container').html(`
            <div class="alert alert-danger mt-3 mb-0">
                <i class="fas fa-times-circle"></i> 
                <strong>ERROR:</strong> Kapasitas baru (${kapasitasMaks} bed) lebih kecil dari 
                bed aktif saat ini (${bedAktif} bed). Perubahan akan ditolak!
            </div>
        `);
    } else if (kapasitasMaks === bedAktif) {
        $('#warning-container').html(`
            <div class="alert alert-warning mt-3 mb-0">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Perhatian:</strong> Kapasitas baru (${kapasitasMaks} bed) sama dengan 
                bed aktif (${bedAktif} bed). Tidak ada ruang untuk penambahan bed baru.
            </div>
        `);
    } else {
        $('#warning-container').html(`
            <div class="alert alert-success mt-3 mb-0">
                <i class="fas fa-check-circle"></i> 
                <strong>Valid:</strong> Kapasitas baru ${kapasitasMaks} bed. 
                Tersisa ${kapasitasMaks - bedAktif} slot untuk bed baru.
            </div>
        `);
    }
    
    const kapasitasManual = parseInt($('#kapasitas').val());
    if (!kapasitasManual || kapasitasManual < kapasitasMaks) {
        $('#kapasitas').val(kapasitasMaks);
    }
    
    $('#calculationBox').show();
}

$('#kelas, #luas_ruangan, #luas_perbed, #faktor_efisiensi').on('change keyup', hitungKapasitas);

if ($('#luas_ruangan').val()) {
    hitungKapasitas();
}

$('#formRuangan').on('submit', function(e) {
    const luasRuangan = parseFloat($('#luas_ruangan').val());
    
    if (luasRuangan > 0) {
        const kapasitasMaksText = $('#display_kapasitas').text();
        const kapasitasMaks = parseInt(kapasitasMaksText);
        
        if (kapasitasMaks < bedAktif) {
            e.preventDefault();
            alert(`TIDAK DAPAT MENYIMPAN!\n\nKapasitas baru (${kapasitasMaks} bed) lebih kecil dari bed aktif (${bedAktif} bed).\n\nNonaktifkan bed terlebih dahulu atau perbesar luas ruangan.`);
            return false;
        }
    }
});
</script>

</body>
</html>