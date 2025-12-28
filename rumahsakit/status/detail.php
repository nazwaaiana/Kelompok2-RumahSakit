<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: select.php");
    exit;
}

$id_bed = (int)$_GET['id'];
$pesan_sukses = "";
$pesan_error = "";

if (isset($_POST['update_fisik'])) {
    $new_status = $_POST['f_stsfisik'];
    $current_time = date('Y-m-d H:i:s');
    
    $valid_statuses = ['Aktif', 'Nonaktif', 'Maintenance'];
    if (in_array($new_status, $valid_statuses)) {
        $sql_update = "UPDATE t_tempattidur 
                       SET `f_stsfisik` = '$new_status', 
                           f_updated = '$current_time' 
                       WHERE f_idbed = $id_bed";

        if ($db->runSQL($sql_update)) {
            $pesan_sukses = "Status Fisik Tempat Tidur berhasil diperbarui menjadi " . htmlspecialchars($new_status) . ".";
        } else {
            $pesan_error = "Gagal memperbarui Status Fisik. Silakan coba lagi.";
        }
    } else {
        $pesan_error = "Status yang dipilih tidak valid.";
    }
}

$sql_detail = "SELECT 
                tt.f_idbed,
                tt.f_nomorbed,
                tt.`f_ stsfisik`, 
                tt.f_created,
                tt.f_updated,
                r.f_nama AS nama_ruangan,
                r.f_kelas AS kelas_ruangan,
                r.f_lantai
            FROM 
                t_tempattidur tt
            JOIN 
                t_ruangan r ON tt.f_idruangan = r.f_idruangan
            WHERE 
                tt.f_idbed = $id_bed";

$bed_detail = $db->getITEM($sql_detail);

if (!$bed_detail) {
    header("Location: select.php?msg=Bed tidak ditemukan");
    exit;
}

$bed_fisik_status = $bed_detail['f_stsfisik']; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Tempat Tidur (Bed)</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include '../topbar.php';?>

                <div class="container-fluid">

                    <?php if ($pesan_sukses): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?= $pesan_sukses ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($pesan_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?= $pesan_error ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-search"></i> Detail Tempat Tidur (Bed)</h1>
                        <a href="select.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali ke Daftar Bed
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Master Bed: <?= htmlspecialchars($bed_detail['f_nomorbed']) ?> (ID: <?= $id_bed ?>)</h6>
                        </div>

                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-info mb-3">Informasi Tempat Tidur</h5>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th>Nomor Bed</th>
                                            <td>: <?= htmlspecialchars($bed_detail['f_nomorbed']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Ruangan (Kelas)</th>
                                            <td>: <?= htmlspecialchars($bed_detail['nama_ruangan']) ?> (<?= htmlspecialchars($bed_detail['kelas_ruangan']) ?>)</td>
                                        </tr>
                                        <tr>
                                            <th>Lantai</th>
                                            <td>: <?= htmlspecialchars($bed_detail['f_lantai']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Dibuat</th>
                                            <td>: <?= date('d M Y H:i:s', strtotime($bed_detail['f_created'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Terakhir Diperbarui</th>
                                            <td>: <?= date('d M Y H:i:s', strtotime($bed_detail['f_updated'])) ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-danger mb-3">Status Fisik Saat Ini</h5>
                                    <h1 class="font-weight-bold">
                                        <span class="badge 
                                            <?php 
                                                if ($bed_fisik_status == 'Aktif') echo 'badge-success';
                                                elseif ($bed_fisik_status == 'Nonaktif') echo 'badge-danger';
                                                elseif ($bed_fisik_status == 'Maintenance') echo 'badge-dark';
                                                else echo 'badge-secondary';
                                            ?>
                                        p-2"><?= htmlspecialchars($bed_fisik_status) ?></span>
                                    </h1>
                                </div>
                            </div>
                            
                            <hr>

                            <h5 class="mt-4 mb-3 text-warning"><i class="fas fa-cog"></i> Ubah Status Fisik Master Bed</h5>
                            <div class="alert alert-warning">
                               <strong> Perhatian: </strong> Mengubah Status Fisik menjadi Nonaktif atau Maintenance akan membuat bed Tidak tersedia untuk Rawat Inap, terlepas dari status operasionalnya.
                            </div>
                            
                            <form method="POST">
                                <div class="form-group row">
                                    <label for="f_stsfisik" class="col-sm-3 col-form-label">Pilih Status Fisik Baru</label>
                                    <div class="col-sm-5">
                                        <select name="f_stsfisik" id="f_stsfisik" class="form-control" required>
                                            <option value="Aktif" <?= ($bed_fisik_status == 'Aktif') ? 'selected' : '' ?>>Aktif (Tersedia)</option>
                                            <option value="Nonaktif" <?= ($bed_fisik_status == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif (Dikeluarkan/Rusak Permanen)</option>
                                            <option value="Maintenance" <?= ($bed_fisik_status == 'Maintenance') ? 'selected' : '' ?>>Maintenance (Perbaikan Fisik Berat)</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <button type="submit" name="update_fisik" class="btn btn-warning btn-block">
                                            <i class="fas fa-sync-alt"></i> Update Status Fisik
                                        </button>
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

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

</body>

</html>