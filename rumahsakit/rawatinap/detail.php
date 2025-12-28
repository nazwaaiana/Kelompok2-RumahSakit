<?php
require_once '../config.php';
require_once '../dbcontroller.php';
require_once '../check_role.php';

requireRole(['Admin', 'Perawat', 'Admisi']);

$db = new dbcontroller();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$idrawatinap = (int) $_GET['id'];

$sql = "
    SELECT 
        ri.f_idrawatinap,
        ri.f_waktumasuk,
        ri.f_waktukeluar,
        ri.f_stsbersih,
        ri.f_alasan,

        p.f_norekmed,
        p.f_nama AS nama_pasien,
        p.f_jnskelamin,
        p.f_tgllahir,

        tt.f_nomorbed,
        r.f_nama AS nama_ruangan,
        r.f_kelas,

        pt.f_nama AS nama_petugas
    FROM t_rawatinap ri
    JOIN t_pasien p ON ri.f_idpasien = p.f_idpasien
    JOIN t_tempattidur tt ON ri.f_idbed = tt.f_idbed
    JOIN t_ruangan r ON tt.f_idruangan = r.f_idruangan
    JOIN t_petugas pt ON ri.f_idpetugas = pt.f_idpetugas
    WHERE ri.f_idrawatinap = $idrawatinap
";

$data = $db->getITEM($sql);

if (!$data) {
    echo "<div class='alert alert-danger'>Data rawat inap tidak ditemukan.</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Rawat Inap</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include '../topbar.php'; ?>

            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800">Detail Rawat Inap Pasien</h1>

                <div class="card shadow mb-4">
                    <div class="card-header font-weight-bold text-primary">
                        Informasi Pasien & Bed
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>No. Rekam Medis</th>
                                <td><?= htmlspecialchars($data['f_norekmed']) ?></td>
                            </tr>
                            <tr>
                                <th>Nama Pasien</th>
                                <td><?= htmlspecialchars($data['nama_pasien']) ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td><?= htmlspecialchars($data['f_jnskelamin']) ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Lahir</th>
                                <td><?= date('d-m-Y', strtotime($data['f_tgllahir'])) ?></td>
                            </tr>
                            <tr>
                                <th>Ruangan</th>
                                <td><?= htmlspecialchars($data['nama_ruangan']) ?> (<?= $data['f_kelas'] ?>)</td>
                            </tr>
                            <tr>
                                <th>Nomor Bed</th>
                                <td><?= htmlspecialchars($data['f_nomorbed']) ?></td>
                            </tr>
                            <tr>
                                <th>Waktu Masuk</th>
                                <td><?= date('d-m-Y H:i', strtotime($data['f_waktumasuk'])) ?></td>
                            </tr>
                            <tr>
                                <th>Waktu Keluar</th>
                                <td>
                                    <?= $data['f_waktukeluar']
                                        ? date('d-m-Y H:i', strtotime($data['f_waktukeluar']))
                                        : '<span class="badge badge-danger">Masih Dirawat</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Petugas</th>
                                <td><?= htmlspecialchars($data['nama_petugas']) ?></td>
                            </tr>
                        </table>

                        <a href="../status/select.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto text-center">
                <span>© 2025 RS Insan Medika</span>
            </div>
        </footer>

    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

</body>
</html>
