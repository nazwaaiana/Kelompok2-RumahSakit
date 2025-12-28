<?php
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "DELETE FROM t_tempattidur WHERE f_idbed = $id LIMIT 1";
    
    $result = $db->runSQL($sql);
    
    if ($result) {
        $_SESSION['flash'] = 'deleted';
    } else {
        $_SESSION['flash'] = 'delete_error';
    }
    
    header("Location: select.php?p=" . (isset($_GET['p']) ? intval($_GET['p']) : 1));
    exit;
}
?>