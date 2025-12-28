<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM t_ruangan WHERE f_idruangan = $id LIMIT 1";

    $result = $db->runSQL($sql);

    if ($result) {
        $_SESSION['flash'] = 'deleted';
    } else {
        $_SESSION['flash'] = 'delete_error';
    }

    header("Location: select.php?p=" . ($_GET['p'] ?? 1));
    exit;
}
