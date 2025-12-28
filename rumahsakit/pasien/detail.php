<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin', 'Perawat', 'Admisi']);

$id_pasien = null;
if (isset($_GET['id'])) {
    $id_pasien = intval($_GET['id']);
} else {
    header("Location: select.php");
    exit;
}

$sql_select = "SELECT * FROM t_pasien WHERE f_idpasien = $id_pasien";
$pasien_data = $db->getALL($sql_select);

if (empty($pasien_data)) {
    $_SESSION['flash'] = 'error';
    $_SESSION['flash_message'] = 'Data pasien tidak ditemukan.';
    header("Location: select.php");
    exit;
}

$pasien = $pasien_data[0];
$sql_ri_aktif = "SELECT f_idrawatinap FROM t_rawatinap 
                 WHERE f_idpasien = $id_pasien AND f_waktukeluar IS NULL";
$ri_data_aktif = $db->getALL($sql_ri_aktif);
$sedang_ri = !empty($ri_data_aktif);

$sql_ri_history_count = "SELECT COUNT(f_idrawatinap) AS total FROM t_rawatinap WHERE f_idpasien = $id_pasien";
$ri_history_count = $db->getITEM($sql_ri_history_count);
$sudah_pernah_ri = $ri_history_count['total'] > 0;

$sql_history = "SELECT ri.*, r.f_nama as nama_ruangan, t.f_nomorbed
                 FROM t_rawatinap ri
                 LEFT JOIN t_tempattidur t ON ri.f_idbed = t.f_idbed
                 LEFT JOIN t_ruangan r ON t.f_idruangan = r.f_idruangan
                 WHERE ri.f_idpasien = $id_pasien
                 ORDER BY ri.f_waktumasuk DESC
                 LIMIT 5";
$history = $db->getALL($sql_history);

$mode = 'view';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Pasien - <?= htmlspecialchars($pasien['f_nama']) ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

<div id="wrapper">
     <?php 
        include '../sidebar.php'; 
        ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <ul class="navbar-nav ml-auto">
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                <?php echo $_SESSION['petugas'] ?? 'Guest'; ?>
                            </span>
                            <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                Profile
                            </a>
                            <a class="dropdown-item" href="?log=logout">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        Detail Data Pasien
                    </h1>
                    <a href="select.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if ($sedang_ri): ?>
                <div class="alert alert-warning" role="alert">
                    <strong>Perhatian!</strong> Pasien ini sedang dalam status Rawat Inap. 
                    Data tidak dapat dihapus sampai proses rawat inap selesai.
                </div>
                <?php endif; ?>
                
                <?php if ($sudah_pernah_ri && !$sedang_ri): ?>
                <div class="alert alert-info" role="alert">
                    <strong>Informasi:</strong> Pasien ini sudah memiliki riwayat Rawat Inap. Data pasien tidak dapat dihapus untuk menjaga rekam medis.
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Informasi Pasien</h6>
                                
                                <?php 
                                if ($sedang_ri) {
                                    $status_badge = '<span class="badge badge-danger">Sedang Rawat Inap</span>';
                                } elseif ($sudah_pernah_ri) {
                                    $status_badge = '<span class="badge badge-secondary">Sudah Pulang</span>';
                                } else {
                                    $status_badge = '<span class="badge badge-success">Tidak Ada Riwayat RI</span>';
                                }
                                echo $status_badge;
                                ?>
                                </div>

                            <div class="card-body">
                                <form> 
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold">No. Rekam Medis</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext"><?= htmlspecialchars($pasien['f_norekmed']) ?></p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold">Nama Lengkap</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext"><?= htmlspecialchars($pasien['f_nama']) ?></p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold">Tanggal Lahir</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext">
                                                <?= date('d F Y', strtotime($pasien['f_tgllahir'])) ?>
                                                <span class="text-muted">(<?= date_diff(date_create($pasien['f_tgllahir']), date_create('today'))->y ?> tahun)</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold">Jenis Kelamin</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext">
                                                <i class="fas fa-<?= $pasien['f_jnskelamin'] == 'Laki-laki' ? 'mars' : 'venus' ?>"></i>
                                                <?= $pasien['f_jnskelamin'] ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold">No. Telepon</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext">
                                                <i class="fas fa-phone"></i> <?= $pasien['f_notlp'] ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold">Alamat</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext"><?= nl2br(htmlspecialchars($pasien['f_alamat'])) ?></p>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold text-muted">Dibuat</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext text-muted">
                                                <i class="far fa-calendar-plus"></i> <?= date('d F Y H:i', strtotime($pasien['f_created'])) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label font-weight-bold text-muted">Terakhir Diupdate</label>
                                        <div class="col-sm-8">
                                            <p class="form-control-plaintext text-muted">
                                                <i class="far fa-calendar-check"></i> <?= date('d F Y H:i', strtotime($pasien['f_updated'])) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="text-right">
                                        <a href="update.php?id=<?= $id_pasien ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit Data
                                        </a>
                                        
                                        <?php if (!$sudah_pernah_ri): ?>
                                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                                                <i class="fas fa-trash"></i> Hapus Data
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Riwayat Rawat Inap (5 Terakhir)</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($history)): ?>
                                    <?php foreach ($history as $h): ?>
                                        <div class="card mb-2 <?= is_null($h['f_waktukeluar']) ? 'border-danger' : 'border-secondary' ?>">
                                            <div class="card-body p-2">
                                                <p class="mb-1">
                                                    <strong><?= $h['nama_ruangan'] ?></strong> - Bed <?= $h['f_nomorbed'] ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-sign-in-alt"></i> Masuk: <?= date('d/m/Y H:i', strtotime($h['f_waktumasuk'])) ?>
                                                </small><br>
                                                <?php if (!is_null($h['f_waktukeluar'])): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-sign-out-alt"></i> Keluar: <?= date('d/m/Y H:i', strtotime($h['f_waktukeluar'])) ?>
                                                    </small>
                                                    <span class="badge badge-secondary"><?= $h['f_alasan'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Sedang RI</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">Belum ada riwayat rawat inap</p>
                                <?php endif; ?>
                            </div>
                        </div>
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

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus Data
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pasien:</p>
                <h5 class="text-center font-weight-bold"><?= htmlspecialchars($pasien['f_nama']) ?></h5>
                <p class="text-center text-muted">(<?= htmlspecialchars($pasien['f_norekmed']) ?>)</p>
                <p class="text-danger"><strong>Peringatan:</strong> Data yang sudah dihapus tidak dapat dikembalikan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>

                <form method="POST" action="delete.php?id=<?= $id_pasien ?>" style="display: inline;">
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Ya, Hapus Data
                    </button>
                </form>

            </div>
        </div>
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