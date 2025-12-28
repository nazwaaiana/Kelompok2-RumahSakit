<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

$sql_ruangan = "SELECT 
                    r.f_idruangan, 
                    r.f_nama, 
                    r.f_kelas, 
                    r.f_lantai, 
                    r.f_kapasitas,
                    r.f_kapasitasmaks,
                    COUNT(t.f_idbed) as jumlah_bed_terdaftar
                FROM t_ruangan r
                LEFT JOIN t_tempattidur t ON r.f_idruangan = t.f_idruangan
                GROUP BY r.f_idruangan
                ORDER BY r.f_nama ASC";
$ruangan_list = $db->getALL($sql_ruangan);

if (isset($_POST['simpan'])) {
    $idruangan = $db->escapeString($_POST['idruangan']); 
    $nomorbed  = $db->escapeString(strtoupper(trim($_POST['nomorbed']))); 
    $stsfisik  = $db->escapeString($_POST['stsfisik']);
    $created   = date("Y-m-d H:i:s");
    $updated   = date("Y-m-d H:i:s");
    $id_petugas = $_SESSION['idpetugas'];

    $valid_statuses = ['Aktif', 'Nonaktif', 'Maintenance'];
    if (!in_array($stsfisik, $valid_statuses)) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Status fisik tidak valid!';
        header("Location: insert.php");
        exit;
    }

    $sql_kapasitas = "SELECT 
                        r.f_nama, 
                        r.f_kelas, 
                        r.f_kapasitas,
                        r.f_kapasitasmaks,
                        COUNT(t.f_idbed) as jumlah_bed_sekarang
                      FROM t_ruangan r
                      LEFT JOIN t_tempattidur t ON r.f_idruangan = t.f_idruangan
                      WHERE r.f_idruangan = '$idruangan'
                      GROUP BY r.f_idruangan";
    $data_kapasitas = $db->getITEM($sql_kapasitas);
    
    $kapasitas_maks = (int)$data_kapasitas['f_kapasitas'];
    $kapasitas_standar = (int)($data_kapasitas['f_kapasitasmaks'] ?? 0);
    $nama_ruangan   = $data_kapasitas['f_nama'];
    $kelas          = $data_kapasitas['f_kelas'];
    $jumlah_bed_sekarang = (int)$data_kapasitas['jumlah_bed_sekarang'];

    if ($jumlah_bed_sekarang >= $kapasitas_maks) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = "Gagal! Ruangan <strong>$nama_ruangan</strong> sudah mencapai batas maksimum kapasitas (<strong>$kapasitas_maks bed</strong>). Saat ini sudah terdaftar <strong>$jumlah_bed_sekarang bed</strong>.";
        $_SESSION['form_data'] = $_POST;
        header("Location: insert.php");
        exit;
    }

    if ($kapasitas_standar > 0 && $jumlah_bed_sekarang >= $kapasitas_standar) {
        $_SESSION['warning_message'] = "Perhatian: Ruangan ini sudah melebihi kapasitas standar ($kapasitas_standar bed) berdasarkan luas ruangan.";
    }

    $prefix_benar = "";
    if (strpos($kelas, 'VVIP') !== false) $prefix_benar = 'A';
    elseif (strpos($kelas, 'VIP') !== false) $prefix_benar = 'B';
    elseif (strpos($kelas, '1') !== false) $prefix_benar = 'C';
    elseif (strpos($kelas, '2') !== false) $prefix_benar = 'D';
    elseif (strpos($kelas, '3') !== false) $prefix_benar = 'E';

    if (substr($nomorbed, 0, 1) !== $prefix_benar) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = "Format Salah! Untuk <strong>$kelas</strong>, nomor bed harus diawali huruf '<strong>$prefix_benar</strong>'";
        $_SESSION['form_data'] = $_POST;
        header("Location: insert.php");
        exit;
    }

    $sql_check = "SELECT f_nomorbed FROM t_tempattidur WHERE f_nomorbed = '$nomorbed' LIMIT 1";
    $check_result = $db->getALL($sql_check);
    
    if (!empty($check_result)) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Nomor Bed "<strong>' . htmlspecialchars($nomorbed) . '</strong>" sudah terdaftar di sistem!';
        $_SESSION['form_data'] = $_POST;
        header("Location: insert.php");
        exit;
    }

    try {
        $db->runSQL("START TRANSACTION");
        
        $sql = "INSERT INTO t_tempattidur (f_idruangan, f_nomorbed, f_stsfisik, f_created, f_updated)
                VALUES ('$idruangan', '$nomorbed', '$stsfisik', '$created', '$updated')";
        $db->runSQL($sql);
        
        $res_id = $db->getITEM("SELECT LAST_INSERT_ID() as last_id");
        $new_bed_id = $res_id['last_id'];
        
        if (!$new_bed_id) {
            throw new Exception("Gagal mendapatkan ID Tempat Tidur");
        }

        $keterangan = "";
        $status_log = "";

        if ($stsfisik === 'Aktif') {
            $status_log = 'Siap';
            $keterangan = "Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.";
        } elseif ($stsfisik === 'Maintenance') {
            $status_log = 'Maintenance';
            $keterangan = "Bed baru ditambahkan langsung dalam kondisi Maintenance.";
        }

        if ($status_log !== "") {
            $sql_insert_log = "INSERT INTO t_bedstatus 
                              (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                              VALUES ('$id_petugas', '$new_bed_id', '$status_log', '$created', 
                              '$keterangan', '$created')";
            $db->runSQL($sql_insert_log);
        }
        
        $db->runSQL("COMMIT");
        
        $sisa_slot = $kapasitas_maks - ($jumlah_bed_sekarang + 1);
        $_SESSION['flash'] = 'success';
        $_SESSION['flash_message'] = "Data tempat tidur <strong>$nomorbed</strong> berhasil ditambahkan ke ruangan <strong>$nama_ruangan</strong>! Sisa slot: <strong>$sisa_slot bed</strong>.";
        
    } catch (Exception $e) {
        $db->runSQL("ROLLBACK");
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Gagal menambahkan data: ' . $e->getMessage();
    }

    header("Location: select.php");
    exit;
}

$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tambah Data Tempat Tidur - RS InsanMedika</title>
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
        
        .capacity-info {
            font-size: 0.85rem;
            color: #5a5c69;
        }
        
        .capacity-full {
            color: #e74a3b;
            font-weight: bold;
        }
        
        .capacity-warning {
            color: #f6c23e;
            font-weight: bold;
        }
        
        .capacity-available {
            color: #1cc88a;
            font-weight: bold;
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
                        Tambah Data Tempat Tidur
                    </h1>
                    
                    <?php
                    if (isset($_SESSION['flash']) && isset($_SESSION['flash_message'])) {
                        $alert_type = $_SESSION['flash'] == 'success' ? 'alert-success' : 'alert-danger';
                        echo '<div class="alert ' . $alert_type . ' alert-dismissible fade show" role="alert">';
                        echo '<strong>' . ($_SESSION['flash'] == 'success' ? '<i class="fas fa-check-circle"></i> Berhasil!' : '<i class="fas fa-exclamation-triangle"></i> Kesalahan!') . '</strong> ';
                        echo $_SESSION['flash_message'];
                        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                        echo '<span aria-hidden="true">&times;</span></button></div>';
                        
                        unset($_SESSION['flash']);
                        unset($_SESSION['flash_message']);
                    }
                    
                    if (isset($_SESSION['warning_message'])) {
                        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
                        echo '<i class="fas fa-exclamation-triangle"></i> ';
                        echo $_SESSION['warning_message'];
                        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                        echo '<span aria-hidden="true">&times;</span></button></div>';
                        
                        unset($_SESSION['warning_message']);
                    }
                    ?>
                    
                    <div class="alert alert-info shadow-sm">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Ketentuan Penomoran Bed:</strong>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li>VVIP: Awalan <b>A</b> (Contoh: A01)</li>
                                    <li>VIP: Awalan <b>B</b> (Contoh: B01)</li>
                                    <li>Kelas 1: Awalan <b>C</b> (Contoh: C01)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li>Kelas 2: Awalan <b>D</b> (Contoh: D01)</li>
                                    <li>Kelas 3: Awalan <b>E</b> (Contoh: E01)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-bed"></i> Form Insert Tempat Tidur
                            </h6>
                        </div>

                        <div class="card-body">

                            <form action="insert.php" method="POST" id="formTempattidur">

                                <h5 class="text-primary mb-3"><i class="fas fa-clipboard-list"></i> Informasi Tempat Tidur</h5>

                                <div class="form-group">
                                    <label for="idruangan">
                                        <i class="fas fa-door-open"></i> Ruangan <span class="text-danger">*</span>
                                    </label>
                                    <select name="idruangan" id="idruangan" class="form-control" required>
                                        <option value="">-- Pilih Ruangan --</option>
                                        <?php 
                                        if (!empty($ruangan_list)) {
                                            foreach ($ruangan_list as $ruangan) {
                                                $kapasitas = (int)$ruangan['f_kapasitas'];
                                                $jumlah_bed = (int)$ruangan['jumlah_bed_terdaftar'];
                                                $sisa = $kapasitas - $jumlah_bed;
                                                
                                                $disabled = '';
                                                $capacity_class = 'capacity-available';
                                                $status_text = "Sisa: $sisa bed";
                                                
                                                if ($sisa <= 0) {
                                                    $disabled = 'disabled';
                                                    $capacity_class = 'capacity-full';
                                                    $status_text = "PENUH ($jumlah_bed/$kapasitas)";
                                                } elseif ($sisa <= 2) {
                                                    $capacity_class = 'capacity-warning';
                                                    $status_text = "Hampir Penuh - Sisa: $sisa bed";
                                                }
                                                
                                                $selected = (isset($form_data['idruangan']) && $form_data['idruangan'] == $ruangan['f_idruangan']) ? 'selected' : '';
                                                
                                                echo '<option value="' . htmlspecialchars($ruangan['f_idruangan']) . '" ' . $selected . ' ' . $disabled . 
                                                     ' data-kelas="' . htmlspecialchars($ruangan['f_kelas']) . '">' 
                                                     . htmlspecialchars($ruangan['f_nama']) . ' - ' . $ruangan['f_kelas'] 
                                                     . ' (Lantai ' . $ruangan['f_lantai'] . ') - ' 
                                                     . $status_text . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Pilih ruangan yang masih memiliki slot tersedia
                                    </small>
                                    <div id="capacityInfo" class="mt-2" style="display:none;">
                                        <div class="alert alert-info mb-0">
                                            <strong>Informasi Kapasitas:</strong>
                                            <div id="capacityDetail"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="nomorbed">
                                        <i class="fas fa-hashtag"></i> Nomor Bed <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="nomorbed" id="nomorbed" class="form-control" required 
                                           placeholder="A01, B01, C01, dst..."
                                           value="<?php echo isset($form_data['nomorbed']) ? htmlspecialchars($form_data['nomorbed']) : ''; ?>">
                                    <small class="form-text text-muted" id="prefixHint">
                                        Pastikan awalan sesuai dengan kelas ruangan yang dipilih.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="stsfisik">
                                        <i class="fas fa-cog"></i> Status Fisik <span class="text-danger">*</span>
                                    </label>
                                    <select name="stsfisik" id="stsfisik" class="form-control" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Aktif" <?php echo (isset($form_data['stsfisik']) && $form_data['stsfisik'] == 'Aktif') || !isset($form_data['stsfisik']) ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="Nonaktif" <?php echo (isset($form_data['stsfisik']) && $form_data['stsfisik'] == 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                                        <option value="Maintenance" <?php echo (isset($form_data['stsfisik']) && $form_data['stsfisik'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <strong>Aktif:</strong> Bed siap digunakan | <strong>Nonaktif:</strong> Tidak operasional | <strong>Maintenance:</strong> Sedang perbaikan
                                    </small>
                                </div>
                                
                                <hr>
                                
                                <div class="form-group mb-0">
                                    <button type="submit" name="simpan" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Simpan Data
                                    </button>

                                    <a href="select.php" class="btn btn-secondary ml-2">
                                        <i class="fas fa-arrow-left"></i> Kembali
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
                        <span>Copyright &copy; <?= date('Y') ?> RS InsanMedika | Kelompok Dua</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    
    <script>
    const ruanganData = <?php echo json_encode($ruangan_list); ?>;
    
    $('#idruangan').on('change', function() {
        const selectedId = $(this).val();
        const selectedKelas = $(this).find(':selected').data('kelas');
        
        if (selectedId) {
            const ruangan = ruanganData.find(r => r.f_idruangan == selectedId);
            
            if (ruangan) {
                const kapasitas = parseInt(ruangan.f_kapasitas);
                const jumlahBed = parseInt(ruangan.jumlah_bed_terdaftar);
                const sisa = kapasitas - jumlahBed;
                
                let infoHtml = `
                    <ul class="mb-0 mt-2">
                        <li>Kapasitas Maksimal: <strong>${kapasitas} bed</strong></li>
                        <li>Bed Terdaftar: <strong>${jumlahBed} bed</strong></li>
                        <li>Slot Tersedia: <strong class="${sisa > 2 ? 'text-success' : 'text-warning'}">${sisa} bed</strong></li>
                    </ul>
                `;
                
                $('#capacityDetail').html(infoHtml);
                $('#capacityInfo').show();
                
                // Update hint prefix
                let prefix = '';
                if (selectedKelas.includes('VVIP')) prefix = 'A';
                else if (selectedKelas.includes('VIP')) prefix = 'B';
                else if (selectedKelas.includes('1')) prefix = 'C';
                else if (selectedKelas.includes('2')) prefix = 'D';
                else if (selectedKelas.includes('3')) prefix = 'E';
                
                $('#prefixHint').html(`<i class="fas fa-info-circle"></i> <strong>Penting:</strong> Untuk kelas <strong>${selectedKelas}</strong>, gunakan awalan huruf <strong>${prefix}</strong> (Contoh: ${prefix}01, ${prefix}02)`);
            }
        } else {
            $('#capacityInfo').hide();
            $('#prefixHint').html('Pastikan awalan sesuai dengan kelas ruangan yang dipilih.');
        }
    });
    
    if ($('#idruangan').val()) {
        $('#idruangan').trigger('change');
    }
    </script>

</body>
</html>