<?php
ob_start(); 
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

if (!isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php");
    exit;
}
$id_petugas = $_SESSION['idpetugas'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = '<div class="alert alert-danger">ID Status tidak valid!</div>';
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
                    tt.f_nomorbed,
                    tt.f_stsfisik,
                    r.f_nama AS nama_ruangan
                FROM
                    t_bedstatus bs
                JOIN
                    t_tempattidur tt ON bs.f_idbed = tt.f_idbed
                JOIN
                    t_ruangan r ON tt.f_idruangan = r.f_idruangan
                WHERE
                    bs.f_idbedsts = $id_bedsts
                    AND bs.f_waktuselesai IS NULL";

$status_data = $db->getALL($sql_status);

if (empty($status_data)) {
    $_SESSION['flash_message'] = '<div class="alert alert-danger">Status tidak ditemukan atau sudah diselesaikan!</div>';
    header("Location: select.php");
    exit;
}

$status = $status_data[0];
$id_bed = $status['f_idbed'];
$status_saat_ini = $status['f_sts'];
$status_fisik_master = $status['f_stsfisik'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->runSQL("START TRANSACTION");
        
        $id_bed_final = $status['f_idbed']; 

        $sql_finish = "UPDATE t_bedstatus
                       SET f_waktuselesai = '$waktu_selesai'
                       WHERE f_idbedsts = $id_bedsts";
        $db->runSQL($sql_finish);

        $status_check = trim($status_saat_ini);
        $msg = '';

        if (strcasecmp($status_check, 'Maintenance') == 0) {
            $sql_master = "UPDATE t_tempattidur 
                           SET f_stsfisik = 'Aktif',
                               f_updated = '$waktu_selesai'
                           WHERE f_idbed = '$id_bed_final'";
            $db->runSQL($sql_master);
            
            $sql_insert_siap = "INSERT INTO t_bedstatus
                                (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                                VALUES ('$id_petugas', '$id_bed_final', 'Siap', '$waktu_selesai', 
                                'Otomatis: Selesai maintenance dan siap digunakan.', '$waktu_selesai')";
            $db->runSQL($sql_insert_siap);
            
            $msg = 'Maintenance selesai. Status fisik bed kini <strong>AKTIF</strong> dan bed dalam kondisi <strong>SIAP</strong>.';
        } 
        elseif (strcasecmp($status_check, 'Pembersihan') == 0) {
            $sql_insert_baru = "INSERT INTO t_bedstatus
                                (f_idpetugas, f_idbed, f_sts, f_waktumulai, f_keterangan, f_created)
                                VALUES ('$id_petugas', '$id_bed_final', 'Siap', '$waktu_selesai', 
                                'Otomatis: Selesai pembersihan dan siap digunakan.', '$waktu_selesai')";
            $db->runSQL($sql_insert_baru);

            $db->runSQL("UPDATE t_tempattidur 
                        SET f_stsfisik = 'Aktif', f_updated = '$waktu_selesai' 
                        WHERE f_idbed = '$id_bed_final'");

            $sql_update_stsbersih = "UPDATE t_rawatinap 
                                     SET f_stsbersih = 'Siap', 
                                         f_updated = '$waktu_selesai'
                                     WHERE f_idbed = '$id_bed_final' 
                                     AND f_waktukeluar IS NOT NULL 
                                     AND f_stsbersih = 'Kotor'
                                     ORDER BY f_waktukeluar DESC 
                                     LIMIT 1";
            $db->runSQL($sql_update_stsbersih);

            $msg = 'Pembersihan selesai. Bed kini <strong>SIAP</strong> digunakan dan status kebersihan di Rawat Inap diperbarui.';
        }
        elseif (strcasecmp($status_check, 'Terisi') == 0) {
            $msg = 'Status Terisi selesai. Bed perlu dibersihkan sebelum digunakan kembali.';
        }
        elseif (strcasecmp($status_check, 'Siap') == 0) {
            $db->runSQL("UPDATE t_tempattidur 
                        SET f_stsfisik = 'Aktif', f_updated = '$waktu_selesai' 
                        WHERE f_idbed = '$id_bed_final'");
            $msg = 'Status Siap ditutup.';
        }
        else {
            $db->runSQL("UPDATE t_tempattidur 
                        SET f_stsfisik = 'Aktif', f_updated = '$waktu_selesai' 
                        WHERE f_idbed = '$id_bed_final'");
            $msg = 'Status berhasil diperbarui.';
        }

        $db->runSQL("COMMIT");
        
        $_SESSION['flash_message'] = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . $msg . '</div>';
        header("Location: select.php");
        exit;

    } catch (Exception $e) {
        $db->runSQL("ROLLBACK");
        $_SESSION['flash_message'] = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Terjadi kesalahan: ' . $e->getMessage() . '</div>';
        header("Location: select.php");
        exit;
    }
}

$waktu_mulai = strtotime($status['f_waktumulai']);
$durasi_detik = time() - $waktu_mulai;
$durasi_jam = floor($durasi_detik / 3600);
$durasi_menit = floor(($durasi_detik % 3600) / 60);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Selesaikan Status Bed - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../topbar.php'; ?>

                <div class="container-fluid mt-4">

                    <h1 class="h3 mb-4 text-gray-800">
                        <i class="fas fa-check-circle"></i> Selesaikan Status: <?= htmlspecialchars($status_saat_ini) ?>
                    </h1>

                    <?php
                        if (isset($_SESSION['flash_message'])) {
                            echo $_SESSION['flash_message'];
                            unset($_SESSION['flash_message']);
                        }
                    ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-danger text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-exclamation-triangle"></i> Konfirmasi Penyelesaian Status Bed #<?= $id_bedsts ?>
                            </h6>
                        </div>
                        <div class="card-body">

                            <div class="alert alert-warning">
                                <strong><i class="fas fa-info-circle"></i> PERHATIAN!</strong> Tindakan ini akan menutup status <strong><?= htmlspecialchars($status_saat_ini) ?></strong> dan
                                <?php if ($status_saat_ini === 'Pembersihan'): ?>
                                secara otomatis memulai status baru <strong>SIAP</strong> untuk bed ini serta mengupdate status kebersihan di Rawat Inap.
                                <?php elseif ($status_saat_ini === 'Maintenance'): ?>
                                mengembalikan status fisik bed ke <strong>AKTIF</strong> dan bed akan berstatus <strong>SIAP</strong>.
                                <?php else: ?>
                                menutup log aktivitas ini.
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td width="140"><strong>ID Bed:</strong></td>
                                            <td><?= $status['f_idbed'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Nomor Bed:</strong></td>
                                            <td><span class="badge badge-primary p-2"><?= htmlspecialchars($status['f_nomorbed']) ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Ruangan:</strong></td>
                                            <td><?= htmlspecialchars($status['nama_ruangan']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status Fisik Master:</strong></td>
                                            <td><span class="badge badge-secondary p-2"><?= htmlspecialchars($status_fisik_master) ?></span></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td width="140"><strong>Status Saat Ini:</strong></td>
                                            <td><span class="badge badge-info p-2"><?= htmlspecialchars($status['f_sts']) ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Waktu Mulai:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($status['f_waktumulai'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Durasi Berjalan:</strong></td>
                                            <td>
                                                <strong class="text-primary"><?= $durasi_jam ?></strong> jam 
                                                <strong class="text-primary"><?= $durasi_menit ?></strong> menit
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Waktu Selesai:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($waktu_selesai)) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            <form action="finish.php?id=<?= $id_bedsts ?>" method="POST">
                                <p class="text-center font-weight-bold text-danger mb-4">
                                    <i class="fas fa-question-circle"></i> Apakah Anda yakin ingin menyelesaikan proses ini?
                                </p>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-lg btn-icon-split">
                                        <span class="icon text-white-50"><i class="fas fa-check-circle"></i></span>
                                        <span class="text">Ya, Selesaikan Status</span>
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
                        <span>Copyright &copy; RS InsanMedika <?= date('Y') ?></span>
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