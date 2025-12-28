<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

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
    
    $created = date("Y-m-d H:i:s");
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
        
        $toleransi = $kapasitas_maks * 1.2;
        if ($kapasitas > $toleransi) {
            $_SESSION['flash'] = 'error';
            $_SESSION['flash_message'] = "Kapasitas manual ($kapasitas bed) terlalu besar! Maksimal berdasarkan luas adalah $kapasitas_maks bed (toleransi: " . floor($toleransi) . " bed).";
            header("Location: insert.php");
            exit;
        }
    }

    $sql = "INSERT INTO t_ruangan (
                f_nama, f_kelas, f_lantai, f_kapasitas, 
                f_luasruangan, f_luasperbed, f_faktorefisiensi, f_kapasitasmaks,
                f_created, f_updated
            ) VALUES (
                '$nama', '$kelas', '$lantai', '$kapasitas',
                " . ($luas_ruangan ? "'$luas_ruangan'" : "NULL") . ",
                " . ($luas_perbed ? "'$luas_perbed'" : "NULL") . ",
                '$faktor_efisiensi', '$kapasitas_maks',
                '$created', '$updated'
            )";

    $result = $db->runSQL($sql);

    if ($result) {
        $_SESSION['flash'] = 'success';
    } else {
        $_SESSION['flash'] = 'error';
    }

    header("Location: select.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tambah Data Ruangan - RS InsanMedika</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
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
        .badge-large {
            font-size: 1.2rem;
            padding: 10px 15px;
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
                     Tambah Data Ruangan
                </h1>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Info:</strong> 
                    Sistem akan menghitung kapasitas maksimal bed berdasarkan luas ruangan dan standar Kemenkes RI. 
                    Anda juga dapat mengisi kapasitas manual untuk ruangan existing.
                </div>

                <div class="card shadow">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-door-open"></i> Form Insert Ruangan
                        </h6>
                    </div>

                    <div class="card-body">

                        <form action="insert.php" method="POST" id="formRuangan">

                            <h5 class="text-primary mb-3"><i class="fas fa-clipboard-list"></i> Informasi Dasar</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-door-open"></i> Nama Ruangan <span class="text-danger">*</span></label>
                                        <input type="text" name="nama" id="nama" class="form-control" 
                                               placeholder="Contoh: Melati, Anggrek" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-tags"></i> Kelas Ruangan <span class="text-danger">*</span></label>
                                        <select name="kelas" id="kelas" class="form-control" required>
                                            <option value="">-- Pilih Kelas --</option>
                                            <option value="VVIP">VVIP</option>
                                            <option value="VIP">VIP</option>
                                            <option value="Kelas 1">Kelas 1</option>
                                            <option value="Kelas 2">Kelas 2</option>
                                            <option value="Kelas 3">Kelas 3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Lantai <span class="text-danger">*</span></label>
                                <input type="number" name="lantai" id="lantai" class="form-control" 
                                       placeholder="Contoh: 1, 2, 3" min="1" required>
                            </div>

                            <hr>

                            <h5 class="text-success mb-3"><i class="fas fa-calculator"></i> Kalkulasi Kapasitas Bed</h5>

                            <div class="info-box">
                                <strong>📏 Cara Kerja Sistem:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Masukkan <strong>luas ruangan</strong> dalam m²</li>
                                    <li>Sistem otomatis menghitung <strong>kapasitas maksimal</strong> berdasarkan standar Kemenkes</li>
                                    <li>Anda dapat override dengan kapasitas manual jika diperlukan</li>
                                </ol>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-ruler-combined"></i> Luas Ruangan (m²)</label>
                                        <input type="number" step="0.01" name="luas_ruangan" id="luas_ruangan" 
                                               class="form-control" placeholder="Contoh: 50.00" min="0">
                                        <small class="form-text text-muted">Luas total ruangan dalam meter persegi</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-bed"></i> Luas per Bed (m²) - Optional</label>
                                        <input type="number" step="0.01" name="luas_perbed" id="luas_perbed" 
                                               class="form-control" placeholder="Auto dari standar">
                                        <small class="form-text text-muted">Kosongkan untuk pakai standar</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-percentage"></i> Faktor Efisiensi</label>
                                        <input type="number" step="0.01" name="faktor_efisiensi" id="faktor_efisiensi" 
                                               class="form-control" value="0.65" min="0" max="1">
                                        <small class="form-text text-muted">Default: 65% (0.60 - 0.70)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="calculation-box" id="calculationBox" style="display:none;">
                                <h6 class="font-weight-bold text-primary mb-3">
                                    <i class="fas fa-calculator"></i> Hasil Kalkulasi Otomatis
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
                                            <small class="text-muted">Faktor Efisiensi</small>
                                            <h4 class="text-info" id="display_faktor">-</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <small class="text-muted"><strong>KAPASITAS MAKS</strong></small>
                                            <h2 class="text-success font-weight-bold" id="display_kapasitas">-</h2>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Kapasitas maksimal <strong id="kapasitas_text">-</strong> bed berdasarkan regulasi Kemenkes.
                                </div>
                            </div>

                            <hr>

                            <h5 class="text-warning mb-3"><i class="fas fa-users"></i> Kapasitas Manual / Existing</h5>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Catatan:</strong> Isi field ini jika ruangan sudah ada dan belum punya data luas, 
                                atau untuk override hasil kalkulasi otomatis.
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Kapasitas Tempat Tidur <span class="text-danger">*</span></label>
                                <input type="number" name="kapasitas" id="kapasitas" class="form-control" 
                                       placeholder="Masukkan jumlah bed" min="1" required>
                                <small class="form-text text-muted">
                                    Jika luas ruangan diisi, sistem akan validasi apakah kapasitas ini sesuai standar
                                </small>
                            </div>

                            <hr>
                            
                            <div class="form-group mb-0">
                                <button type="submit" name="simpan" class="btn btn-primary btn-md">
                                    <i class="fas fa-save"></i> Simpan Ruangan
                                </button>

                                <a href="select.php" class="btn btn-secondary btn-md ml-2">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>

                        </form>

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
        $('#display_faktor').text((faktorEfisiensi * 100).toFixed(0) + '%');
        $('#display_kapasitas').text(kapasitasMaks + ' bed');
        $('#kapasitas_text').text(kapasitasMaks);
        
        if (!$('#kapasitas').val()) {
            $('#kapasitas').val(kapasitasMaks);
        }
        
        const kapasitasManual = parseInt($('#kapasitas').val());
        $('#warning-kapasitas').remove();
        
        if (kapasitasManual > kapasitasMaks) {
            $('#calculationBox').append(`
                <div class="alert alert-danger mt-2" id="warning-kapasitas">
                    <i class="fas fa-exclamation-circle"></i> 
                    <strong>Peringatan:</strong> Kapasitas manual (${kapasitasManual} bed) 
                    melebihi kapasitas maksimal standar (${kapasitasMaks} bed). 
                    Pastikan ini sesuai dengan kondisi ruangan.
                </div>
            `);
        }
        
        $('#calculationBox').show();
    }
    
    // Event listeners
    $('#kelas, #luas_ruangan, #luas_perbed, #faktor_efisiensi').on('change keyup', hitungKapasitas);
    $('#kapasitas').on('change keyup', function() {
        if ($('#luas_ruangan').val()) {
            hitungKapasitas(); // Re-validate
        }
    });
    
    // Form validation sebelum submit
    $('#formRuangan').on('submit', function(e) {
        const luasRuangan = parseFloat($('#luas_ruangan').val());
        const kapasitas = parseInt($('#kapasitas').val());
        
        if (!kapasitas || kapasitas < 1) {
            e.preventDefault();
            alert('Kapasitas tempat tidur harus diisi minimal 1 bed!');
            return false;
        }
        
        if (luasRuangan > 0) {
            const kapasitasMaksText = $('#display_kapasitas').text();
            const kapasitasMaks = parseInt(kapasitasMaksText);
            
            if (kapasitas > kapasitasMaks * 1.5) {
                if (!confirm(`PERINGATAN: Kapasitas (${kapasitas} bed) jauh melebihi standar (${kapasitasMaks} bed).\n\nLanjutkan?`)) {
                    e.preventDefault();
                    return false;
                }
            }
        }
    });
    </script>

</body>

</html>