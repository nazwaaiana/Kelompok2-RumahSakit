<?php
require_once '../config.php'; 
require_once '../dbcontroller.php';
$db = new dbcontroller();
require_once '../check_role.php';

$id_petugas = $_SESSION['idpetugas'];
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

$query_fetch = "SELECT f_nama, f_username, f_unitkerja, f_role FROM t_petugas WHERE f_idpetugas = ?";
$result_fetch = $db->runQueryWithParams($query_fetch, "i", [$id_petugas]);

if (!$result_fetch || $result_fetch->num_rows == 0) {
    $_SESSION['error_message'] = "Data profil tidak ditemukan.";
    header("location: " . BASE_URL . "akun/profile.php");
    exit;
}
$data_petugas = $result_fetch->fetch_assoc();
$current_role = $data_petugas['f_role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_baru = htmlspecialchars(trim($_POST['nama']));
    $username_baru = htmlspecialchars(trim($_POST['username']));
    $unitkerja_input = htmlspecialchars(trim($_POST['unitkerja']));
    
    if ($current_role === 'Admin') {
        $unitkerja_baru = "";
        if (empty($nama_baru) || empty($username_baru)) {
            $error_message = "Nama dan Username wajib diisi.";
        }
    } else {
        $unitkerja_baru = $unitkerja_input;
        if (empty($nama_baru) || empty($username_baru) || empty($unitkerja_baru)) {
            $error_message = "Nama, Username, dan Unit Kerja wajib diisi.";
        }
    }

    if (empty($error_message)) {
        $query_check = "SELECT f_idpetugas FROM t_petugas WHERE f_username = ? AND f_idpetugas != ?";
        $result_check = $db->runQueryWithParams($query_check, "si", [$username_baru, $id_petugas]);
        
        if ($result_check && $result_check->num_rows > 0) {
            $error_message = "Username ($username_baru) sudah digunakan oleh petugas lain.";
        } else {
            $query_update = "UPDATE t_petugas SET f_nama = ?, f_username = ?, f_unitkerja = ?, f_updated = NOW() WHERE f_idpetugas = ?";
            
            $param_type = "sssi"; 
            $param_values = [$nama_baru, $username_baru, $unitkerja_baru, $id_petugas];

            if ($db->execute($query_update, $param_type, $param_values)) {
                $_SESSION['petugas'] = $nama_baru;
                $_SESSION['success_message'] = "Profil berhasil diperbarui!";
                header("location: " . BASE_URL . "akun/profile.php");
                exit;
            } else {
                $error_message = "Gagal memperbarui profil. Silakan coba lagi. Error: " . ($db->get_error() ?? 'Tidak diketahui');
            }
        }
    }
    
    $data_petugas['f_nama'] = $nama_baru;
    $data_petugas['f_username'] = $username_baru;
    $data_petugas['f_unitkerja'] = $unitkerja_input; 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Profil - RS InsanMedika</title>
    <link href="<?= BASE_URL ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="<?= BASE_URL ?>css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
        }
        
        .form-control {
            border-left: 0;
            border-color: #d1d3e2;
            padding: 12px 15px;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .form-control:focus + .input-group-append .input-group-text,
        .input-group-prepend + .form-control:focus {
            border-color: #667eea;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 35px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary, .btn-warning {
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            color: white;
        }
        
        .btn-secondary:hover, .btn-warning:hover {
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
        
        .alert-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #721c24;
        }
        
        .form-label {
            font-weight: 600;
            color: #4e5d78;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .form-text {
            font-size: 0.85rem;
            color: #8898aa;
        }
        
        .role-display {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fc 100%);
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #d1d3e2;
            font-size: 0.95rem;
        }
        
        .divider {
            border-top: 2px solid #e9ecef;
            margin: 30px 0;
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
                        <h1><i class="fas fa-user-edit mr-3"></i>Edit Profil</h1>
                        <p>Perbarui informasi profil Anda</p>
                    </div>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><strong>Berhasil!</strong> <?= $success_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><strong>Error!</strong> <?= $error_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card card-custom">
                        <div class="card-header card-header-custom">
                            <h6><i class="fas fa-edit mr-2"></i>Formulir Edit Profil</h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="nama" class="form-label">
                                                Nama Lengkap <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-user"></i>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control" id="nama" name="nama" 
                                                            value="<?= htmlspecialchars($data_petugas['f_nama'] ?? '') ?>" 
                                                            placeholder="Masukkan nama lengkap" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="username" class="form-label">
                                                Username <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-at"></i>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control" id="username" name="username" 
                                                            value="<?= htmlspecialchars($data_petugas['f_username'] ?? '') ?>" 
                                                            placeholder="Masukkan username" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="unitkerja" class="form-label">
                                                Unit Kerja 
                                                <span class="text-danger" id="unitkerja_required"></span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-building"></i>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control" id="unitkerja" name="unitkerja" 
                                                            value="<?= htmlspecialchars($data_petugas['f_unitkerja'] ?? '') ?>" 
                                                            <?php if ($current_role !== 'Admin') echo 'required'; ?>
                                                            placeholder="<?= $current_role === 'Admin' ? 'Opsional untuk Admin' : 'Masukkan unit kerja' ?>">
                                            </div>
                                            <small class="form-text text-muted" id="unitkerja_helper">
                                                <?php if ($current_role === 'Admin'): ?>
                                                    <i class="fas fa-info-circle"></i> Unit Kerja bersifat opsional untuk Admin
                                                <?php else: ?>
                                                    <i class="fas fa-info-circle"></i> Unit Kerja wajib diisi untuk <?= htmlspecialchars($current_role) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-label">Role</label>
                                            <div class="role-display">
                                                <i class="fas fa-user-tag mr-2"></i><?= htmlspecialchars($current_role ?? 'N/A') ?>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lock"></i> Role tidak dapat diubah
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="divider"></div>
                                
                                <div class="d-flex flex-wrap justify-content-between align-items-center">
                                    <button type="submit" class="btn btn-save text-white mb-3 mb-md-0">
                                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                    </button>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= BASE_URL ?>akun/profile.php" class="btn btn-secondary mr-2 mb-2">
                                            <i class="fas fa-times mr-2"></i>Batal
                                        </a>
                                        <a href="<?= BASE_URL ?>akun/gantipass.php" class="btn btn-warning mb-2">
                                            <i class="fas fa-key mr-2"></i>Ganti Password
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <footer class="sticky-footer bg-white mt-5">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; RS InsanMedika <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <script src="<?= BASE_URL ?>vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_URL ?>js/sb-admin-2.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const role = '<?= $current_role ?>';
            const unitKerjaInput = document.getElementById('unitkerja');
            const unitKerjaRequiredSpan = document.getElementById('unitkerja_required');

            if (role === 'Admin') {
                unitKerjaInput.removeAttribute('required');
                unitKerjaRequiredSpan.textContent = ''; 
            } else {
                unitKerjaInput.setAttribute('required', 'required');
                unitKerjaRequiredSpan.textContent = '*'; 
            }
        });
    </script>
</body>
</html>