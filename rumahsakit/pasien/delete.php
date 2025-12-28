<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

if (!isset($_POST['delete'])) {
    header("Location: select.php");
    exit;
}

$id_pasien = intval($_GET['id'] ?? 0);

if ($id_pasien == 0) {
    $_SESSION['flash'] = 'error';
    $_SESSION['flash_message'] = 'ID Pasien tidak valid.';
    header("Location: select.php");
    exit;
}

$sql_check_history = "SELECT COUNT(f_idrawatinap) AS total_ri FROM t_rawatinap WHERE f_idpasien = ?";
$params = [$id_pasien];
$history_count_result = $db->execute($sql_check_history, 'i', $params); 

if (!$history_count_result) {
    $_SESSION['flash'] = 'error';
    $_SESSION['flash_message'] = 'Terjadi kesalahan saat memeriksa riwayat pasien.';
    header("Location: detail.php?id=$id_pasien");
    exit;
}

$history_count = $history_count_result[0];

if ($history_count['total_ri'] > 0) {
    $_SESSION['flash'] = 'error';
    $_SESSION['flash_message'] = 'Pasien tidak dapat dihapus karena sudah memiliki riwayat Rawat Inap (rekam medis).';
    header("Location: detail.php?id=$id_pasien"); 
    exit;
} else {
    $sql_delete = "DELETE FROM t_pasien WHERE f_idpasien = ?";
    $deleted_rows = $db->execute($sql_delete, 'i', $params);

    if ($deleted_rows !== false) {
        $_SESSION['flash'] = 'del_success';
        $_SESSION['flash_message'] = 'Data pasien berhasil dihapus.';
        header("Location: select.php");
        exit;
    } else {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Gagal menghapus data pasien. (Cek koneksi/database)';
        header("Location: detail.php?id=$id_pasien");
        exit;
    }
}
?>