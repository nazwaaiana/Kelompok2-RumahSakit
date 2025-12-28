<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']); 

$id_petugas = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_petugas = (int)$_GET['id'];
} else {
    header("Location: select.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password_baru = trim($_POST['password']);
    $role = $_POST['role'];
    
    $unitkerja = ($role == "Admin") ? "" : trim($_POST['unitkerja'] ?? '');
    $updated = date("Y-m-d H:i:s");

    $params = [$nama, $username, $role, $unitkerja, $updated];
    $types = "sssss";

    $sql_update = "UPDATE t_petugas SET 
        f_nama = ?,
        f_username = ?,
        f_role = ?,
        f_unitkerja = ?,
        f_updated = ?";

    if (!empty($password_baru)) {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $sql_update .= ", f_password = ?";
        $types .= "s";
        array_push($params, $password_hash);
    }

    $sql_update .= " WHERE f_idpetugas = ?";
    $types .= "i";
    array_push($params, $id_petugas);

    $result = $db->execute($sql_update, $types, $params);

    if ($result) {
        $_SESSION['flash'] = 'success';
        $_SESSION['flash_message'] = 'Data petugas <strong>' . htmlspecialchars($nama) . '</strong> berhasil diperbarui.';
        header("Location: select.php");
        exit;
    } else {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Gagal memperbarui data petugas.';
    }
}

$sql_select = "SELECT f_nama, f_username, f_role, f_unitkerja FROM t_petugas WHERE f_idpetugas = ?"; 
$result_select = $db->runQueryWithParams($sql_select, "i", [$id_petugas]);

if ($result_select && $result_select->num_rows > 0) {
    $petugas = $result_select->fetch_assoc();
} else {
    header("Location: select.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Data Petugas - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        
        .form-control {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            height: calc(2.75rem + 2px);
        }
        
        .form-control:focus {
            border-color: #f6b93b;
            box-shadow: 0 0 0 0.2rem rgba(246, 185, 59, 0.25);
        }
        
        select.form-control {
            height: calc(2.75rem + 2px);
            line-height: 1.5;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            color: white;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(246, 185, 59, 0.4);
            color: white;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            color: white;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            border: none;
            color: #2d3436;
        }
    </style>
</head>

<body id="page-top">

<div id="wrapper">
    <?php include '../sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../topbar.php'; ?>
            
            <div class="container-fluid">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-user-edit"></i> Ubah Data Petugas
                    </h1>
                    <a href="select.php" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php
                if (isset($_SESSION['flash'])) {
                    $alert_class = $_SESSION['flash'] == 'success' ? 'alert-success' : 'alert-danger';
                    $icon = $_SESSION['flash'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                    
                    if (isset($_SESSION['flash_message'])) {
                        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show shadow-sm">';
                        echo '<i class="fas '.$icon.'"></i> ' . $_SESSION['flash_message'];
                        echo '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';
                        unset($_SESSION['flash_message']);
                    }
                    unset($_SESSION['flash']);
                }
                ?>

                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Info:</strong> Anda sedang mengubah data petugas <strong><?= htmlspecialchars($petugas['f_nama']) ?></strong>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-edit"></i> Form Update Petugas
                        </h6>
                    </div>

                    <div class="card-body">

                        <form action="" method="POST" id="formUpdatePetugas">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-user text-warning"></i> Nama Lengkap <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               name="nama" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($petugas['f_nama'] ?? ''); ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-user-tag text-warning"></i> Username <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               name="username" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($petugas['f_username'] ?? ''); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-key text-warning"></i> Password Baru (Opsional)
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-control"
                                           placeholder="Kosongkan jika tidak ingin mengubah password">
                                    <div class="input-group-append">
                                        <span class="input-group-text" onclick="togglePassword()" style="cursor:pointer;">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-lightbulb"></i> Kosongkan jika tidak ingin mengubah password
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-user-shield text-warning"></i> Role / Jabatan <span class="text-danger">*</span>
                                        </label>
                                        <select name="role" 
                                                id="role" 
                                                class="form-control" 
                                                required 
                                                onchange="checkRole()">
                                            <option value="">-- Pilih Role --</option>
                                            <option value="Admin" <?php if(($petugas['f_role'] ?? '')=="Admin") echo "selected"; ?>>Admin</option>
                                            <option value="Admisi" <?php if(($petugas['f_role'] ?? '')=="Admisi") echo "selected"; ?>>Admisi</option>
                                            <option value="Perawat" <?php if(($petugas['f_role'] ?? '')=="Perawat") echo "selected"; ?>>Perawat</option>
                                            <option value="Petugas Kebersihan" <?php if(($petugas['f_role'] ?? '')=="Petugas Kebersihan") echo "selected"; ?>>Petugas Kebersihan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-building text-warning"></i> Unit Kerja
                                        </label>
                                        <input type="text" 
                                               name="unitkerja" 
                                               id="unitkerja" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($petugas['f_unitkerja'] ?? ''); ?>"
                                               placeholder="Masukkan unit kerja">
                                        <small class="form-text text-muted" id="unitInfo">
                                            Unit kerja tidak wajib untuk Admin
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group mb-0">
                                <button type="submit" name="simpan" class="btn btn-warning btn-md">
                                    <i class="fas fa-save"></i> Update Data
                                </button>

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
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    function checkRole() {
        const role = document.getElementById("role").value;
        const unit = document.getElementById("unitkerja");
        const unitInfo = document.getElementById("unitInfo");

        if (role === "Admin") {
            unit.value = "";
            unit.disabled = true;
            unit.removeAttribute('required');
            unitInfo.innerHTML = 'Unit kerja tidak wajib untuk Admin';
            unitInfo.className = 'form-text text-muted';
        } else {
            unit.disabled = false; 
            unit.setAttribute('required', 'required');
            unitInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Wajib diisi untuk role ' + role;
            unitInfo.className = 'form-text text-warning';
        }
    }
    
    window.onload = checkRole;
    
    document.getElementById('formUpdatePetugas').addEventListener('submit', function(e) {
        if (!confirm('Apakah Anda yakin ingin memperbarui data petugas ini?')) {
            e.preventDefault();
            return false;
        }
    });
</script>

</body>
</html>