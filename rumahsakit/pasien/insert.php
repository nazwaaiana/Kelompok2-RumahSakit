<?php

date_default_timezone_set('Asia/Jakarta');

require_once "../dbcontroller.php";
require_once '../config.php';
require_once "../check_role.php";

requireRole(['Admin', 'Perawat', 'Admisi']);

try {
    $db = new dbcontroller();
} catch (Exception $e) {
    die("Koneksi database error: " . $e->getMessage());
}

if (isset($_POST['simpan'])) {
    $norekmed = trim($_POST['norekmed']); 
    $nama = $_POST['nama'];
    $tgllahir = $_POST['tgllahir'];
    $jnskelamin = $_POST['jnskelamin'];
    
    $notlp = filter_var($_POST['notlp'], FILTER_SANITIZE_NUMBER_INT);
    $notlp = (string) $notlp; 
    
    $alamat = $_POST['alamat'];
    $created = date("Y-m-d H:i:s");
    $updated = date("Y-m-d H:i:s");

    // CEK DUPLIKAT NO REKAM MEDIS
    $sql_check = "SELECT f_norekmed FROM t_pasien WHERE f_norekmed = ? LIMIT 1";
    $check_result = $db->execute($sql_check, "s", [$norekmed]);
    
    if ($check_result && $check_result->num_rows > 0) {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Nomor Rekam Medis <strong>' . htmlspecialchars($norekmed) . '</strong> sudah terdaftar! Gunakan nomor lain.';
        header("Location: insert.php");
        exit;
    }

    // JIKA TIDAK DUPLIKAT, LANJUTKAN INSERT
    $sql_insert = "INSERT INTO t_pasien 
                    (f_norekmed, f_nama, f_tgllahir, f_jnskelamin, f_notlp, f_alamat, f_created, f_updated)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $types = "ssssssss"; 
    $params = [
        $norekmed, 
        $nama, 
        $tgllahir, 
        $jnskelamin, 
        $notlp,
        $alamat, 
        $created, 
        $updated
    ];

    try {
        $result = $db->execute($sql_insert, $types, $params);
    } catch (Exception $e) {
        $result = false;
        error_log("Insert Pasien Error: " . $e->getMessage());
    }

    if ($result !== false) {
        $_SESSION['flash'] = 'success';
        $_SESSION['flash_message'] = 'Data pasien <strong>' . htmlspecialchars($nama) . '</strong> berhasil ditambahkan!';
    } else {
        $_SESSION['flash'] = 'error';
        $_SESSION['flash_message'] = 'Gagal menambahkan data pasien.'; 
    }

    header("Location: select.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Tambah Data Pasien - RS InsanMedika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
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
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }
        
        .form-text {
            font-size: 0.875rem;
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
                            <i class="fas fa-user-plus"></i> Tambah Data Pasien
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
                                <i class="fas fa-edit"></i> Form Insert Pasien
                            </h6>
                        </div>

                        <div class="card-body">
                            <form action="insert.php" method="POST" id="formPasien">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-id-card text-primary"></i> Nomor Rekam Medis <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   name="norekmed" 
                                                   class="form-control" 
                                                   required 
                                                   placeholder="Contoh: RM-2025-001"
                                                   value="<?php echo isset($_POST['norekmed']) ? htmlspecialchars($_POST['norekmed']) : ''; ?>">
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Nomor rekam medis harus unik
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-user text-primary"></i> Nama Lengkap Pasien <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   name="nama" 
                                                   class="form-control" 
                                                   required 
                                                   placeholder="Masukkan nama lengkap"
                                                   value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-calendar-alt text-primary"></i> Tanggal Lahir <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" 
                                                   name="tgllahir" 
                                                   class="form-control" 
                                                   required
                                                   value="<?php echo isset($_POST['tgllahir']) ? htmlspecialchars($_POST['tgllahir']) : ''; ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-venus-mars text-primary"></i> Jenis Kelamin <span class="text-danger">*</span>
                                            </label>
                                            <select name="jnskelamin" class="form-control" required>
                                                <option value="">-- Pilih Jenis Kelamin --</option>
                                                <option value="Laki-laki" <?php echo (isset($_POST['jnskelamin']) && $_POST['jnskelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                                <option value="Perempuan" <?php echo (isset($_POST['jnskelamin']) && $_POST['jnskelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-phone text-primary"></i> Nomor Telepon <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           name="notlp" 
                                           class="form-control" 
                                           required 
                                           placeholder="Contoh: 081234567890"
                                           value="<?php echo isset($_POST['notlp']) ? htmlspecialchars($_POST['notlp']) : ''; ?>">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Format: 08xxxxxxxxxx
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-map-marker-alt text-primary"></i> Alamat Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="alamat" 
                                              class="form-control" 
                                              rows="3" 
                                              required 
                                              placeholder="Masukkan alamat lengkap pasien"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                                </div>
                                
                                <hr>

                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="simpan" class="btn btn-primary">
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

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    
    <script>
        document.getElementById('formPasien').addEventListener('submit', function(e) {
            const notlp = document.querySelector('input[name="notlp"]').value;
            
            if (notlp.length < 10) {
                e.preventDefault();
                alert('Nomor telepon harus minimal 10 digit');
                return false;
            }
        });
    </script>
</body>

</html>