<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

$id_pasien = null;
if (isset($_GET['id'])) {
    $id_pasien = intval($_GET['id']);
} else {
    header("Location: select.php");
    exit;
}

$sql_pasien = "SELECT * FROM t_pasien WHERE f_idpasien = $id_pasien";
$pasien_data = $db->getALL($sql_pasien);

if (empty($pasien_data)) {
    $_SESSION['flash'] = 'error';
    header("Location: select.php");
    exit;
}

$pasien = $pasien_data[0];

$sql_check_ri = "SELECT * FROM t_rawatinap 
                 WHERE f_idpasien = $id_pasien AND f_waktukeluar IS NULL";
$check_ri = $db->getALL($sql_check_ri);

if (!empty($check_ri)) {
    $_SESSION['flash'] = 'already_ri'; 
    header("Location: select.php");
    exit;
}

$sql_ruangan = "SELECT * FROM t_ruangan ORDER BY f_nama ASC";
$list_ruangan = $db->getALL($sql_ruangan);

$sql_petugas = "SELECT * FROM t_petugas ORDER BY f_nama ASC";
$list_petugas = $db->getALL($sql_petugas);

if (isset($_POST['daftar'])) {
    $id_bed = intval($_POST['id_bed']);
    $id_petugas = intval($_POST['id_petugas']);
    
    if (isset($_POST['waktu_type']) && $_POST['waktu_type'] == 'manual' && !empty($_POST['waktu_masuk_manual'])) {
        $waktu_masuk = date("Y-m-d H:i:s", strtotime($_POST['waktu_masuk_manual']));
    } else {
        $waktu_masuk = date("Y-m-d H:i:s"); 
    }
    
    $created = date("Y-m-d H:i:s");
    $updated = date("Y-m-d H:i:s");

    $sql_check_bed = "SELECT * FROM t_rawatinap 
                      WHERE f_idbed = $id_bed AND f_waktukeluar IS NULL";
    $check_bed = $db->getALL($sql_check_bed);

    if (!empty($check_bed)) {
        $_SESSION['flash'] = 'bed_occupied';
        header("Location: pendaftaran_ri.php?id=$id_pasien");
        exit;
    }

    $sql_insert = "INSERT INTO t_rawatinap 
                    (f_idpasien, f_idbed, f_idpetugas, f_waktumasuk, f_waktukeluar, f_stsbersih, f_alasan, f_created, f_updated)
                    VALUES 
                    ($id_pasien, $id_bed, $id_petugas, '$waktu_masuk', NULL, 'Kotor', 'Null', '$created', '$updated')";

    $result = $db->runSQL($sql_insert);

    if ($result) {
        $_SESSION['flash'] = 'ri_success';
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
    <title>Pendaftaran Rawat Inap - <?= htmlspecialchars($pasien['f_nama']) ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .bed-item {
            cursor: pointer;
            transition: all 0.3s;
        }
        .bed-item:hover {
            transform: scale(1.05);
        }
        .bed-available {
            border: 2px solid #28a745;
            background-color: #d4edda;
        }
        .bed-occupied {
            border: 2px solid #dc3545;
            background-color: #f8d7da;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .bed-selected {
            border: 3px solid #007bff;
            background-color: #cfe2ff;
            box-shadow: 0 0 10px rgba(0,123,255,0.5);
        }
    </style>
</head>

<body id="page-top">

<div id="wrapper">
     <?php 
        include '../sidebar.php'; 
        ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php 
                include '../topbar.php'; 
            ?>

            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-procedures"></i> Pendaftaran Rawat Inap
                    </h1>
                    <a href="select.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php
                if (isset($_SESSION['flash'])) {
                    if ($_SESSION['flash'] == 'bed_occupied') {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Gagal!</strong> Tempat tidur yang dipilih sudah terisi. Silakan pilih bed lain.
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>';
                        unset($_SESSION['flash']);
                    }
                }
                ?>

                <div class="row">
                    
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-primary">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-user-injured"></i> Data Pasien
                                </h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="font-weight-bold">No. RM</td>
                                        <td>:</td>
                                        <td><?= htmlspecialchars($pasien['f_norekmed']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Nama</td>
                                        <td>:</td>
                                        <td><?= htmlspecialchars($pasien['f_nama']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Tgl Lahir</td>
                                        <td>:</td>
                                        <td><?= date('d-m-Y', strtotime($pasien['f_tgllahir'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Umur</td>
                                        <td>:</td>
                                        <td><?= date_diff(date_create($pasien['f_tgllahir']), date_create('today'))->y ?> tahun</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Jenis Kelamin</td>
                                        <td>:</td>
                                        <td><?= $pasien['f_jnskelamin'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">No. Telp</td>
                                        <td>:</td>
                                        <td><?= $pasien['f_notlp'] ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-success">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-clipboard-list"></i> Form Pendaftaran Rawat Inap
                                </h6>
                            </div>
                            <div class="card-body">

                                <form method="POST" action="" id="formDaftarRI">

                                    <div class="form-group">
                                        <label class="font-weight-bold">1. Pilih Ruangan <span class="text-danger">*</span></label>
                                        <select name="id_ruangan" id="id_ruangan" class="form-control" required>
                                            <option value="">-- Pilih Ruangan --</option>
                                            <?php foreach ($list_ruangan as $r): ?>
                                                <option value="<?= $r['f_idruangan'] ?>">
                                                    <?= htmlspecialchars($r['f_nama']) ?> - 
                                                    <?= $r['f_kelas'] ?> 
                                                    (Lantai <?= $r['f_lantai'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold">2. Pilih Tempat Tidur <span class="text-danger">*</span></label>
                                        <div id="bed-container" class="row">
                                            <div class="col-12 text-center text-muted py-4">
                                                <i class="fas fa-bed fa-3x mb-3"></i>
                                                <p>Silakan pilih ruangan terlebih dahulu</p>
                                            </div>
                                        </div>
                                        <input type="hidden" name="id_bed" id="id_bed" required>
                                    </div>
                                    
                                    <hr>

                                    <div class="form-group">
                                        <label class="font-weight-bold">3. Waktu Masuk Rawat Inap <span class="text-danger">*</span></label>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="waktuAuto" name="waktu_type" class="custom-control-input"
                                                value="auto" checked>
                                            <label class="custom-control-label" for="waktuAuto">
                                                Gunakan waktu saat ini (Otomatis)
                                            </label>
                                        </div>
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="waktuManual" name="waktu_type" class="custom-control-input"
                                                value="manual">
                                            <label class="custom-control-label" for="waktuManual">
                                                Input waktu manual (untuk pendaftaran retroaktif)
                                            </label>
                                        </div>
                                        <input type="datetime-local" name="waktu_masuk_manual" id="waktuMasukManual"
                                            class="form-control mt-2" 
                                            value="<?= date('Y-m-d\TH:i') ?>" 
                                            style="display:none;">
                                        
                                        <div id="waktuSekarang" class="alert alert-info mt-2">
                                            <i class="fas fa-clock"></i> Waktu saat ini (Otomatis): <strong><?= date('d F Y H:i:s') ?> WIB</strong>
                                        </div>
                                    </div>
                                    <hr>

                                    <div class="form-group">
                                        <label class="font-weight-bold">4. Pilih Dokter/Petugas Penanggung Jawab <span class="text-danger">*</span></label>
                                        <select name="id_petugas" class="form-control" required>
                                            <option value="">-- Pilih Dokter/Petugas --</option>
                                            <?php foreach ($list_petugas as $pt): ?>
                                                <option value="<?= $pt['f_idpetugas'] ?>">
                                                    <?= htmlspecialchars($pt['f_nama']) ?> - <?= htmlspecialchars($pt['f_role']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <hr>

                                    <div class="text-right">
                                        <a href="select.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Batal
                                        </a>
                                        <button type="submit" name="daftar" class="btn btn-success" id="btnDaftar" disabled>
                                            <i class="fas fa-check-circle"></i> Daftarkan Rawat Inap
                                        </button>
                                    </div>

                                </form>

                            </div>
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

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    function toggleWaktuInput() {
        if ($('input[name="waktu_type"]:checked').val() === 'manual') {
            $('#waktuMasukManual').show().prop('required', true);
            $('#waktuSekarang').hide();
        } else {
            $('#waktuMasukManual').hide().prop('required', false);
            $('#waktuSekarang').show();
        }
    }
    
    toggleWaktuInput(); 
    
    $('input[name="waktu_type"]').change(function() {
        toggleWaktuInput();
    });

    $('#id_ruangan').change(function() {
        var idRuangan = $(this).val();
        
        if (idRuangan) {
            $.ajax({
                url: 'get_beds.php',
                type: 'POST',
                data: { id_ruangan: idRuangan },
                success: function(response) {
                    $('#bed-container').html(response);
                    $('#id_bed').val('');
                    checkFormValidity();
                },
                error: function() {
                    $('#bed-container').html('<div class="col-12"><div class="alert alert-danger">Gagal memuat data tempat tidur</div></div>');
                }
            });
        } else {
            $('#bed-container').html('<div class="col-12 text-center text-muted py-4"><i class="fas fa-bed fa-3x mb-3"></i><p>Silakan pilih ruangan terlebih dahulu</p></div>');
            $('#id_bed').val('');
            checkFormValidity();
        }
    });

    $(document).on('click', '.bed-item.bed-available', function() {
        $('.bed-item').removeClass('bed-selected');
        $(this).addClass('bed-selected');
        $('#id_bed').val($(this).data('idbed'));
        checkFormValidity();
    });

    function checkFormValidity() {
        var ruangan = $('#id_ruangan').val();
        var bed = $('#id_bed').val();
        var petugas = $('select[name="id_petugas"]').val();
        
        var waktuValid = true;
        if ($('input[name="waktu_type"]:checked').val() === 'manual') {
            if (!$('#waktuMasukManual').val()) {
                waktuValid = false;
            }
        }

        if (ruangan && bed && petugas && waktuValid) {
            $('#btnDaftar').prop('disabled', false);
        } else {
            $('#btnDaftar').prop('disabled', true);
        }
    }
    
    $('select[name="id_petugas"]').change(checkFormValidity);
    $('input[name="waktu_type"]').change(checkFormValidity);
    $('#waktuMasukManual').change(checkFormValidity);

    $('#formDaftarRI').submit(function(e) {
        if (!$('#id_bed').val()) {
            e.preventDefault();
            alert('Silakan pilih tempat tidur terlebih dahulu!');
            return false;
        }
        
        if ($('input[name="waktu_type"]:checked').val() === 'manual' && !$('#waktuMasukManual').val()) {
            e.preventDefault();
            alert('Waktu masuk manual tidak boleh kosong!');
            return false;
        }
    });

});
</script>

</body>
</html>