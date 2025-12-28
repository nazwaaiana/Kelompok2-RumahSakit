<?php 
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

$id_bed = null;
if (isset($_GET['id'])) {
    $id_bed = (int)$_GET['id'];
} else {
    header("Location: select.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $id_ruangan = $_POST['id_ruangan'];
    $nomor_bed  = strtoupper(trim($_POST['nomor_bed']));
    $updated    = date("Y-m-d H:i:s");

    $sql_bed_lama = "SELECT f_idruangan FROM t_tempattidur WHERE f_idbed = $id_bed";
    $bed_lama = $db->getITEM($sql_bed_lama);
    $ruangan_lama = $bed_lama['f_idruangan'];

    if ($id_ruangan != $ruangan_lama) {
        $sql_check_kapasitas = "SELECT 
                                    r.f_nama,
                                    r.f_kelas,
                                    r.f_kapasitas,
                                    COUNT(t.f_idbed) as jumlah_bed_sekarang
                                FROM t_ruangan r
                                LEFT JOIN t_tempattidur t ON r.f_idruangan = t.f_idruangan
                                WHERE r.f_idruangan = '$id_ruangan'
                                GROUP BY r.f_idruangan";
        $data_ruangan_baru = $db->getITEM($sql_check_kapasitas);
        
        $kapasitas_maks = (int)$data_ruangan_baru['f_kapasitas'];
        $jumlah_bed_sekarang = (int)$data_ruangan_baru['jumlah_bed_sekarang'];
        $nama_ruangan_baru = $data_ruangan_baru['f_nama'];
        
        if ($jumlah_bed_sekarang >= $kapasitas_maks) {
            $_SESSION['flash'] = 'error_capacity';
            $_SESSION['msg_error'] = "Gagal pindah! Ruangan <strong>$nama_ruangan_baru</strong> sudah penuh ($jumlah_bed_sekarang/$kapasitas_maks bed).";
            header("Location: update.php?id=$id_bed");
            exit;
        }
    }

    $sql_kelas = "SELECT f_kelas, f_nama FROM t_ruangan WHERE f_idruangan = '$id_ruangan'";
    $data_ruangan = $db->getITEM($sql_kelas);
    $kelas = $data_ruangan['f_kelas'] ?? '';
    $nama_ruangan = $data_ruangan['f_nama'] ?? '';

    $prefix_benar = "";
    if (strpos($kelas, 'VVIP') !== false) $prefix_benar = 'A';
    elseif (strpos($kelas, 'VIP') !== false) $prefix_benar = 'B';
    elseif (strpos($kelas, '1') !== false) $prefix_benar = 'C';
    elseif (strpos($kelas, '2') !== false) $prefix_benar = 'D';
    elseif (strpos($kelas, '3') !== false) $prefix_benar = 'E';

    if (substr($nomor_bed, 0, 1) !== $prefix_benar) {
        $_SESSION['flash'] = 'error_prefix';
        $_SESSION['msg_error'] = "Untuk kelas <strong>$kelas</strong>, nomor bed harus diawali huruf '<strong>$prefix_benar</strong>'";
        header("Location: update.php?id=$id_bed");
        exit;
    }

    $sql_cek = "SELECT * FROM t_tempattidur WHERE f_nomorbed = '$nomor_bed' AND f_idbed != $id_bed";
    $cek_ada = $db->getALL($sql_cek);

    if ($cek_ada) {
        $_SESSION['flash'] = 'error_duplicate';
        $_SESSION['msg_error'] = "Nomor Bed '<strong>$nomor_bed</strong>' sudah digunakan bed lain!";
        header("Location: update.php?id=$id_bed");
        exit;
    }

    $sql_update = "UPDATE t_tempattidur SET 
        f_idruangan = '$id_ruangan',
        f_nomorbed = '$nomor_bed',
        f_updated = '$updated'
        WHERE f_idbed = $id_bed";

    $result = $db->runSQL($sql_update);

    if ($result) {
        if ($id_ruangan != $ruangan_lama) {
            $_SESSION['flash'] = 'success_update';
            $_SESSION['flash_message'] = "Bed <strong>$nomor_bed</strong> berhasil dipindah ke ruangan <strong>$nama_ruangan</strong>!";
        } else {
            $_SESSION['flash'] = 'success_update';
            $_SESSION['flash_message'] = "Data bed <strong>$nomor_bed</strong> berhasil diperbarui!";
        }
        header("Location: detail.php?id=$id_bed"); 
    } else {
        $_SESSION['flash'] = 'error_update';
        header("Location: update.php?id=$id_bed");
    }
    exit;
}

$sql_select = "SELECT t.*, r.f_nama as nama_ruangan, r.f_kelas 
               FROM t_tempattidur t
               JOIN t_ruangan r ON t.f_idruangan = r.f_idruangan
               WHERE t.f_idbed = $id_bed";
$tempat_tidur = $db->getALL($sql_select);

if (!$tempat_tidur) {
    header("Location: select.php?msg=data_not_found");
    exit;
}
$tempat_tidur = $tempat_tidur[0];

$sql_ruangan = "SELECT 
                    r.f_idruangan,
                    r.f_nama,
                    r.f_kelas,
                    r.f_lantai,
                    r.f_kapasitas,
                    COUNT(t.f_idbed) as jumlah_bed_terdaftar
                FROM t_ruangan r
                LEFT JOIN t_tempattidur t ON r.f_idruangan = t.f_idruangan
                GROUP BY r.f_idruangan
                ORDER BY r.f_nama ASC";
$list_ruangan = $db->getALL($sql_ruangan);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Data Tempat Tidur - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
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
                    <i class="fas fa-edit"></i> Ubah Data Tempat Tidur
                </h1>

                <?php if (isset($_SESSION['flash'])): ?>
                    <?php if ($_SESSION['flash'] == 'success_update'): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm">
                            <i class="fas fa-check-circle"></i> <strong>Berhasil!</strong> 
                            <?= isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : 'Data berhasil diupdate!' ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php elseif ($_SESSION['flash'] == 'error_capacity'): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                            <i class="fas fa-exclamation-circle"></i> <strong>Kapasitas Penuh!</strong> 
                            <?= $_SESSION['msg_error'] ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                        <?php unset($_SESSION['msg_error']); ?>
                    <?php elseif ($_SESSION['flash'] == 'error_prefix'): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Format Salah!</strong> <?= $_SESSION['msg_error'] ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                        <?php unset($_SESSION['msg_error']); ?>
                    <?php elseif ($_SESSION['flash'] == 'error_duplicate'): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                            <i class="fas fa-times-circle"></i> <strong>Duplikat!</strong> <?= $_SESSION['msg_error'] ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                        <?php unset($_SESSION['msg_error']); ?>
                    <?php elseif ($_SESSION['flash'] == 'error_update'): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                            <i class="fas fa-times-circle"></i> <strong>Gagal!</strong> Terjadi kesalahan saat mengupdate data!
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php unset($_SESSION['flash']); unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-info-circle"></i> <strong>Ketentuan Awalan Nomor Bed:</strong> 
                    <ul class="mb-0 mt-2">
                        <li>VVIP: <b>A</b> | VIP: <b>B</b> | Kelas 1: <b>C</b> | Kelas 2: <b>D</b> | Kelas 3: <b>E</b></li>
                    </ul>
                </div>
                
                <div class="alert alert-warning shadow-sm">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Perhatian:</strong> 
                    Jika Anda memindahkan bed ke ruangan lain, sistem akan memvalidasi kapasitas ruangan tujuan. 
                    Perpindahan hanya diizinkan jika ruangan tujuan masih memiliki slot tersedia.
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-warning text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-bed"></i> Form Update Bed: <?= htmlspecialchars($tempat_tidur['f_nomorbed']) ?>
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-secondary">
                            <strong>Data Saat Ini:</strong><br>
                            <i class="fas fa-bed"></i> Nomor Bed: <strong><?= htmlspecialchars($tempat_tidur['f_nomorbed']) ?></strong><br>
                            <i class="fas fa-door-open"></i> Ruangan: <strong><?= htmlspecialchars($tempat_tidur['nama_ruangan']) ?></strong> (<?= htmlspecialchars($tempat_tidur['f_kelas']) ?>)
                        </div>

                        <form action="" method="POST">

                            <h5 class="text-primary mb-3"><i class="fas fa-clipboard-list"></i> Informasi Tempat Tidur</h5>

                            <div class="form-group">
                                <label for="id_ruangan">
                                    <i class="fas fa-door-open"></i> Ruangan <span class="text-danger">*</span>
                                </label>
                                <select name="id_ruangan" id="id_ruangan" class="form-control" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    <?php foreach($list_ruangan as $ruangan): 
                                        $kapasitas = (int)$ruangan['f_kapasitas'];
                                        $jumlah_bed = (int)$ruangan['jumlah_bed_terdaftar'];
                                        
                                        if ($ruangan['f_idruangan'] == $tempat_tidur['f_idruangan']) {
                                            $jumlah_bed = max(0, $jumlah_bed - 1);
                                        }
                                        
                                        $sisa = $kapasitas - $jumlah_bed;
                                        
                                        $disabled = '';
                                        $status_text = "Sisa: $sisa bed";
                                        
                                        if ($ruangan['f_idruangan'] != $tempat_tidur['f_idruangan'] && $sisa <= 0) {
                                            $disabled = 'disabled';
                                            $status_text = "PENUH ($jumlah_bed/$kapasitas)";
                                        }
                                        
                                        $selected = ($tempat_tidur['f_idruangan'] == $ruangan['f_idruangan']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $ruangan['f_idruangan']; ?>" 
                                                <?php echo $selected; ?> 
                                                <?php echo $disabled; ?>
                                                data-kelas="<?php echo htmlspecialchars($ruangan['f_kelas']); ?>"
                                                data-kapasitas="<?php echo $kapasitas; ?>"
                                                data-terpakai="<?php echo $jumlah_bed; ?>">
                                            <?php echo htmlspecialchars($ruangan['f_nama']) . " - " . $ruangan['f_kelas'] . " (Lantai " . $ruangan['f_lantai'] . ") - " . $status_text; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="capacityInfo" class="mt-2" style="display:none;">
                                    <div class="alert alert-info mb-0">
                                        <strong>Informasi Kapasitas Ruangan:</strong>
                                        <div id="capacityDetail"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="nomor_bed">
                                    <i class="fas fa-hashtag"></i> Nomor Bed <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="nomor_bed" 
                                       id="nomor_bed"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($tempat_tidur['f_nomorbed']); ?>" 
                                       placeholder="Contoh: A01, B01, C01" 
                                       required>
                                <small class="form-text text-muted" id="prefixHint">
                                    Nomor unik sesuai kelas ruangan
                                </small>
                            </div>

                            <hr>

                            <div class="form-group mb-0">
                                <button type="submit" name="simpan" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>

                                <a href="detail.php?id=<?= $id_bed ?>" class="btn btn-secondary ml-2">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Detail
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

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
$('#id_ruangan').on('change', function() {
    const selectedOption = $(this).find(':selected');
    const kelas = selectedOption.data('kelas');
    const kapasitas = selectedOption.data('kapasitas');
    const terpakai = selectedOption.data('terpakai');
    
    if (kelas) {
        const sisa = kapasitas - terpakai;
        
        let infoHtml = `
            <ul class="mb-0 mt-2">
                <li>Kapasitas Maksimal: <strong>${kapasitas} bed</strong></li>
                <li>Bed Terdaftar: <strong>${terpakai} bed</strong></li>
                <li>Slot Tersedia: <strong class="${sisa > 2 ? 'text-success' : 'text-warning'}">${sisa} bed</strong></li>
            </ul>
        `;
        
        if (sisa <= 2 && sisa > 0) {
            infoHtml += `<div class="alert alert-warning mb-0 mt-2"><i class="fas fa-exclamation-triangle"></i> Ruangan hampir penuh!</div>`;
        }
        
        $('#capacityDetail').html(infoHtml);
        $('#capacityInfo').show();
        
        let prefix = '';
        if (kelas.includes('VVIP')) prefix = 'A';
        else if (kelas.includes('VIP')) prefix = 'B';
        else if (kelas.includes('1')) prefix = 'C';
        else if (kelas.includes('2')) prefix = 'D';
        else if (kelas.includes('3')) prefix = 'E';
        
        $('#prefixHint').html(`<i class="fas fa-info-circle"></i> <strong>Penting:</strong> Untuk kelas <strong>${kelas}</strong>, gunakan awalan huruf <strong>${prefix}</strong>`);
    } else {
        $('#capacityInfo').hide();
    }
});

$('#id_ruangan').trigger('change');
</script>

</body>
</html>