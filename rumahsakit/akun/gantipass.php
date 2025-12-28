<?php
session_start();
require_once '../config.php';
require_once '../dbcontroller.php';

if (!isset($_SESSION['idpetugas'])) {
    header("Location: " . BASE_URL); 
    exit;
}

$db = new dbcontroller();
$error = '';

$id_petugas = $_SESSION['idpetugas'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lama = trim($_POST['password_lama'] ?? '');
    $baru = trim($_POST['password_baru'] ?? '');
    $konfirmasi = trim($_POST['konfirmasi_baru'] ?? '');

    if ($lama === '' || $baru === '' || $konfirmasi === '') {
        $error = "Semua field wajib diisi.";
    } elseif ($baru !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } else {

        $sql = "SELECT f_password FROM t_petugas WHERE f_idpetugas = ?";
        $result = $db->runQueryWithParams($sql, "i", [$id_petugas]);

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $password_db = $row['f_password'];

            $valid =
                password_verify($lama, $password_db) ||
                $lama === $password_db;

            if ($valid) {
                $hash = password_hash($baru, PASSWORD_DEFAULT);

                $update = "UPDATE t_petugas SET f_password = ? WHERE f_idpetugas = ?";
                $ok = $db->execute($update, "si", [$hash, $id_petugas]);

                if ($ok) {
                    $_SESSION['password_change_success'] = true;
                    header("Location: " . BASE_URL . "akun/profile.php");
                    exit;
                } else {
                    $error = "Gagal memperbarui password.";
                }
            } else {
                $error = "Password lama salah.";
            }
        } else {
            $error = "Data petugas tidak ditemukan.";
        }
    }
}
$success_message = '';
if (isset($_SESSION['password_change_success'])) {
    $success_message = '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Sukses!</strong> Password Anda telah berhasil diubah.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    unset($_SESSION['password_change_success']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ganti Password - RS InsanMedika</title>
    <link href="<?= BASE_URL ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(253, 160, 133, 0.3);
        }
        
        .page-header h1 {
            color: white;
            font-weight: 700;
            margin: 0;
            font-size: 2rem;
        }
        
        .page-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            margin-top: 5px;
        }
        
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            padding: 20px 30px;
        }
        
        .card-header-custom h6 {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border: 1px solid #d1d3e2;
            border-right: 0;
            color: #fda085;
        }
        
        .form-control {
            border-left: 0;
            border-color: #d1d3e2;
            padding: 12px 15px;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #fda085;
            box-shadow: 0 0 0 0.2rem rgba(253, 160, 133, 0.15);
        }
        
        .toggle-password {
            cursor: pointer;
            transition: all 0.2s ease;
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border: 1px solid #d1d3e2;
            border-left: 0;
            color: #fda085;
        }
        
        .toggle-password:hover {
            color: #f6d365;
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8d5 100%);
        }
        
        .btn-save {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            color: white;
            padding: 12px 35px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(253, 160, 133, 0.3);
        }
        
        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(253, 160, 133, 0.4);
            color: white;
        }
        
        .btn-secondary {
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 18px 20px;
            animation: slideDown 0.4s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #721c24;
        }
        
        .password-strength {
            height: 6px;
            border-radius: 10px;
            margin-top: 10px;
            transition: all 0.3s ease;
            background: #e9ecef;
        }
        
        .strength-indicator {
            height: 100%;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .strength-weak .strength-indicator { 
            width: 25%;
            background: linear-gradient(90deg, #e74a3b 0%, #ea5e4f 100%);
        }
        
        .strength-medium .strength-indicator { 
            width: 50%;
            background: linear-gradient(90deg, #f6c23e 0%, #f8d15a 100%);
        }
        
        .strength-good .strength-indicator { 
            width: 75%;
            background: linear-gradient(90deg, #36b9cc 0%, #4ec3d4 100%);
        }
        
        .strength-strong .strength-indicator { 
            width: 100%;
            background: linear-gradient(90deg, #1cc88a 0%, #36d89e 100%);
        }
        
        .strength-text {
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-label {
            font-weight: 600;
            color: #4e5d78;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .password-match {
            font-size: 0.8rem;
            margin-top: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .match-success {
            color: #1cc88a;
        }
        
        .match-error {
            color: #e74a3b;
        }
        
        .security-tips {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .security-tips h6 {
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .security-tips ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .security-tips li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .divider {
            border-top: 2px solid #e9ecef;
            margin: 30px 0;
        }
        
        .form-text {
            font-size: 0.85rem;
            color: #8898aa;
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

<div class="page-header">
    <h1><i class="fas fa-key mr-3"></i>Ganti Password</h1>
    <p>Perbarui password Anda untuk keamanan lebih baik</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i><strong>Error!</strong> <?= $error ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="card card-custom">
    <div class="card-header card-header-custom">
        <h6><i class="fas fa-lock mr-2"></i>Formulir Ganti Password</h6>
    </div>

    <div class="card-body p-4">
        <form method="POST" id="passwordForm">

            <div class="form-group">
                <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                    </div>
                    <input type="password" name="password_lama" id="password_lama" class="form-control" 
                           placeholder="Masukkan password lama" required>
                    <div class="input-group-append">
                        <span class="input-group-text toggle-password" onclick="togglePassword('password_lama', 'toggle_lama')">
                            <i class="fas fa-eye" id="toggle_lama"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-key"></i>
                        </span>
                    </div>
                    <input type="password" name="password_baru" id="password_baru" class="form-control" 
                           placeholder="Masukkan password baru (min. 6 karakter)" required>
                    <div class="input-group-append">
                        <span class="input-group-text toggle-password" onclick="togglePassword('password_baru', 'toggle_baru')">
                            <i class="fas fa-eye" id="toggle_baru"></i>
                        </span>
                    </div>
                </div>
                <div class="password-strength" id="strengthBar">
                    <div class="strength-indicator"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                    <input type="password" name="konfirmasi_baru" id="konfirmasi_baru" class="form-control" 
                           placeholder="Ulangi password baru" required>
                    <div class="input-group-append">
                        <span class="input-group-text toggle-password" onclick="togglePassword('konfirmasi_baru', 'toggle_konfirmasi')">
                            <i class="fas fa-eye" id="toggle_konfirmasi"></i>
                        </span>
                    </div>
                </div>
                <div class="password-match" id="matchText"></div>
            </div>

            <div class="divider"></div>

            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <button type="submit" class="btn btn-save mb-3 mb-md-0">
                    <i class="fas fa-save mr-2"></i>Simpan Password Baru
                </button>

                <a href="<?= BASE_URL ?>akun/profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

        </form>
    </div>
</div>

<div class="security-tips">
    <h6><i class="fas fa-shield-alt mr-2"></i>Tips Keamanan Password</h6>
    <ul>
        <li>🔒 Gunakan minimal 6 karakter (lebih panjang lebih baik)</li>
        <li>🔤 Kombinasikan huruf besar, huruf kecil, angka, dan simbol</li>
        <li>🚫 Hindari menggunakan informasi pribadi (nama, tanggal lahir)</li>
        <li>🔐 Jangan gunakan password yang sama untuk akun lain</li>
        <li>⏰ Ubah password secara berkala untuk keamanan maksimal</li>
    </ul>
</div>

</div>
</div>

<footer class="sticky-footer bg-white mt-5">
    <div class="container my-auto text-center">
        <span>&copy; RS InsanMedika <?= date('Y') ?></span>
    </div>
</footer>

</div>
</div>

<script src="<?= BASE_URL ?>vendor/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>js/sb-admin-2.min.js"></script>

<script>

function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('password_baru').addEventListener('input', function(e) {
    const password = e.target.value;
    let strength = 0;
    
    if (password.length >= 6) strength += 25;
    if (password.match(/[a-z]+/)) strength += 25;
    if (password.match(/[A-Z]+/)) strength += 25;
    if (password.match(/[0-9]+/) || password.match(/[^a-zA-Z0-9]+/)) strength += 25;
    
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    
    if (password.length === 0) {
        bar.className = 'password-strength';
        text.innerHTML = '';
        return;
    }
    
    if (strength <= 25) {
        bar.className = 'password-strength strength-weak';
        text.innerHTML = '<i class="fas fa-exclamation-circle"></i> Lemah - Tambahkan lebih banyak karakter';
        text.style.color = '#e74a3b';
    } else if (strength <= 50) {
        bar.className = 'password-strength strength-medium';
        text.innerHTML = '<i class="fas fa-info-circle"></i> Sedang - Tambahkan huruf besar atau angka';
        text.style.color = '#f6c23e';
    } else if (strength <= 75) {
        bar.className = 'password-strength strength-good';
        text.innerHTML = '<i class="fas fa-check-circle"></i> Baik - Password cukup kuat';
        text.style.color = '#36b9cc';
    } else {
        bar.className = 'password-strength strength-strong';
        text.innerHTML = '<i class="fas fa-check-double"></i> Kuat - Password sangat aman!';
        text.style.color = '#1cc88a';
    }
    
    checkPasswordMatch();
});

document.getElementById('konfirmasi_baru').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password_baru').value;
    const confirm = document.getElementById('konfirmasi_baru').value;
    const matchText = document.getElementById('matchText');
    
    if (confirm.length === 0) {
        matchText.innerHTML = '';
        return;
    }
    
    if (password === confirm) {
        matchText.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok!';
        matchText.className = 'password-match match-success';
    } else {
        matchText.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak cocok';
        matchText.className = 'password-match match-error';
    }
}

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password_baru').value;
    const confirm = document.getElementById('konfirmasi_baru').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Konfirmasi password tidak cocok!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password minimal 6 karakter!');
        return false;
    }
});
</script>

</body>
</html>