<?php
require_once '../config.php';
date_default_timezone_set('Asia/Jakarta');
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Petugas Kebersihan', 'Admisi']);

if (!isset($_SESSION['idpetugas'])) {
    header("Location: ../login.php"); 
    exit;
}

if (isset($_POST['submit_pelepasan'])) {
    $idrawatinap = (int)$_POST['idrawatinap'];
    $idbed = (int)$_POST['idbed'];
    $nomorbed = $_POST['nomorbed'];
    $alasan = $_POST['alasan'];
    $keterangan = trim($_POST['keterangan'] ?? '');
    $waktu_keluar = date('Y-m-d H:i:s');
    $idpetugas = $_SESSION['idpetugas'];

    if (empty($idrawatinap) || empty($idbed) || empty($alasan)) {
        $pesan_error = 'Data tidak lengkap!';
        $_SESSION['flash_message'] = '
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal!</strong> ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        header("Location: select.php");
        exit;
    }

    $alasan_valid = ['Pulang', 'Pindah', 'Meninggal'];
    if (!in_array($alasan, $alasan_valid)) {
        $pesan_error = 'Alasan keluar tidak valid!';
        $_SESSION['flash_message'] = '
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal!</strong> ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        header("Location: select.php");
        exit;
    }

  try {
        $sql_ri = "UPDATE t_rawatinap 
                   SET f_waktukeluar = '$waktu_keluar', 
                       f_alasan = '$alasan',
                       f_stsbersih = 'Kotor',
                       f_updated = '$waktu_keluar'
                   WHERE f_idrawatinap = $idrawatinap AND f_waktukeluar IS NULL";
        
        $db->runSQL($sql_ri);

        $sql_tutup_lama = "UPDATE t_bedstatus 
                           SET f_waktuselesai = '$waktu_keluar' 
                           WHERE f_idbed = $idbed AND f_waktuselesai IS NULL";
        
        $db->runSQL($sql_tutup_lama);

        $keterangan_bed = "Pasien keluar ($alasan) - Bed No.$nomorbed perlu dibersihkan";
        if (!empty($keterangan)) {
            $keterangan_escaped = addslashes($keterangan);
            $keterangan_bed .= ". Catatan: " . $keterangan_escaped;
        }
        $keterangan_bed_escaped = addslashes($keterangan_bed);
        
        $sql_bedstatus = "INSERT INTO t_bedstatus 
                  (`f_idpetugas`, `f_idbed`, `f_sts`, `f_waktumulai`, `f_keterangan`, `f_created`) 
                  VALUES 
                  ($idpetugas, $idbed, 'Pembersihan', '$waktu_keluar', '$keterangan_bed_escaped', '$waktu_keluar')";
        
        $db->runSQL($sql_bedstatus);
        
        $pesan_sukses = "Pasien berhasil dilepas dengan alasan: $alasan. Bed akan dibersihkan.";
        $_SESSION['flash_message'] = '
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Berhasil!</strong> ' . $pesan_sukses . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
            
        header("Location: select.php");
        exit;

    } catch (Exception $e) {
        $pesan_error = 'Gagal memproses pelepasan pasien! Detail: ' . $e->getMessage();
        $_SESSION['flash_message'] = '
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal!</strong> ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        error_log("Pelepasan Error: " . $e->getMessage()); 
        header("Location: select.php");
        exit;
    }

} else {
    $pesan_error = 'Akses tidak valid!';
    $_SESSION['flash_message'] = '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Gagal!</strong> ' . $pesan_error . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    header("Location: select.php");
    exit;
}
?>