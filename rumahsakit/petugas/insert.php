<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config.php';
require_once "../dbcontroller.php";
$db = new dbcontroller();

require_once "../check_role.php";
requireRole(['Admin']); 

if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password_plain = $_POST['password'];
    $role = $_POST['role'];
    $unitkerja = ($_POST['role'] == "Admin") ? "" : trim($_POST['unitkerja']);
    $created = date("Y-m-d H:i:s");
    $updated = date("Y-m-d H:i:s");

    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $sql_check = "SELECT f_username FROM t_petugas WHERE f_username = ?";
    $check_result = $db->runQueryWithParams($sql_check, "s", [$username]);

    if ($check_result && $check_result->num_rows > 0) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Username <strong>' . htmlspecialchars($username) . '</strong> sudah digunakan.';
        header("Location: insert.php"); 
        exit;
    }

    $sql_insert = "
        INSERT INTO t_petugas 
        (f_nama, f_username, f_password, f_role, f_unitkerja, f_created, f_updated)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    
    $result = $db->execute( 
        $sql_insert,
        "sssssss",
        [$nama, $username, $password_hash, $role, $unitkerja, $created, $updated]
    );

    if ($result) {
        $_SESSION['flash'] = 'success';
        $_SESSION['flash_message'] = 'Data petugas <strong>' . htmlspecialchars($nama) . '</strong> berhasil ditambahkan.';
    } else {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Gagal menyimpan data petugas.';
    }

    header("Location: select.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Data Petugas - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        select.form-control {
            height: calc(2.75rem + 2px);
            line-height: 1.5;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
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
                        <i class="fas fa-user-plus"></i> Tambah Data Petugas
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

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-edit"></i> Form Insert Petugas
                        </h6>
                    </div>

                    <div class="card-body">

                        <form action="insert.php" method="POST" id="formPetugas">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-user text-primary"></i> Nama Lengkap <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               name="nama" 
                                               class="form-control" 
                                               placeholder="Masukkan nama lengkap"
                                               required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-user-tag text-primary"></i> Username <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               name="username" 
                                               class="form-control" 
                                               placeholder="Masukkan username"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-key text-primary"></i> Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="password" 
                                           id="password"
                                           class="form-control" 
                                           placeholder="Masukkan password (min. 6 karakter)"
                                           required>
                                    <div class="input-group-append">
                                        <span class="input-group-text" onclick="togglePassword()" style="cursor:pointer;">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-user-shield text-primary"></i> Role / Jabatan <span class="text-danger">*</span>
                                        </label>
                                        <select name="role" 
                                                id="role" 
                                                class="form-control" 
                                                required 
                                                onchange="checkRole()">
                                            <option value="">-- Pilih Role --</option>
                                            <option value="Admin">Admin</option>
                                            <option value="Admisi">Admisi</option>
                                            <option value="Perawat">Perawat</option>
                                            <option value="Petugas Kebersihan">Petugas Kebersihan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-building text-primary"></i> Unit Kerja <span class="text-danger" id="unitRequired">*</span>
                                        </label>
                                        <input type="text" 
                                               name="unitkerja" 
                                               id="unitkerja" 
                                               class="form-control"
                                               placeholder="Masukkan unit kerja">
                                        <small class="form-text text-muted" id="unitInfo">
                                            Unit kerja tidak wajib untuk Admin
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group mb-0">
                                <button type="submit" name="simpan" class="btn btn-primary btn-md">
                                    <i class="fas fa-save"></i> Simpan Data
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
        const unitRequired = document.getElementById("unitRequired");
        const unitInfo = document.getElementById("unitInfo");

        if (role === "Admin" || role === "") {
            unit.value = "";
            unit.disabled = true;
            unit.removeAttribute('required');
            unitRequired.style.display = 'none';
            unitInfo.innerHTML = 'Unit kerja tidak wajib untuk Admin';
            unitInfo.className = 'form-text text-muted';
        } else {
            unit.disabled = false; 
            unit.setAttribute('required', 'required');
            unitRequired.style.display = 'inline';
            unitInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Wajib diisi untuk role ' + role;
            unitInfo.className = 'form-text text-warning';
        }
    }
    
    window.onload = checkRole;
    
    document.getElementById('formPetugas').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password harus minimal 6 karakter');
            return false;
        }
    });
</script>

</body>
</html>