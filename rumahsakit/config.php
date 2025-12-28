<?php
define('APP_FOLDER', '/rumahsakit/');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$app_root_dir = trim(APP_FOLDER, '/'); 

if ($current_dir === $app_root_dir) {
    $base_url = APP_FOLDER;
} elseif (in_array($current_dir, ['pasien', 'petugas', 'rawatinap', 'ruangan', 'status', 'tempattidur'])) {
    $base_url = "../";
} else {
    $base_url = APP_FOLDER;
}

define('BASE_URL', $base_url);
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASS', '');   
define('DB_NAME', 'rumahsakit');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>