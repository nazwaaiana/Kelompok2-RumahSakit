<?php
session_start();
require_once "dbcontroller.php";

$db = new dbcontroller;
$alert = "";

if (isset($_SESSION['idpetugas'])) {
    header("location:index.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = $db->escapeString(trim($_POST['username']));
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM t_petugas WHERE f_username='$username'";
    $user = $db->getITEM($sql);

    if ($user) {
        $db_password = $user['f_password'];
        $login_sukses = false;

        if (strpos($db_password, '$2y$') === 0) {
            if (password_verify($password, $db_password)) {
                $login_sukses = true;
            }
        } 

        else {
            if ($password === $db_password) {
                $login_sukses = true;
            }
        }

        if ($login_sukses) {
            $_SESSION['petugas'] = $user['f_nama'];
            $_SESSION['idpetugas'] = $user['f_idpetugas'];
            $_SESSION['role'] = $user['f_role']; 

            header("location:index.php");
            exit;
        } else {
            $alert = '<div class="alert alert-danger text-center">Password salah!</div>';
        }
    } else {
        $alert = '<div class="alert alert-danger text-center">Username tidak ditemukan!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Halaman Login MacaroNiez">
    <meta name="author" content="Nazwa Aiana Putri">

    <title>Login</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="css/sb-admin-2.css" rel="stylesheet">
    
    <style>
        body {
           background: linear-gradient(
        180deg,
        #ffffff 0%,
        #f2f7fd 35%,
        #d6e8f8 70%,
        #c7def3 100%
    );
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        overflow: hidden;
        font-family: 'Nunito', sans-serif;
        }

        .login-card {
        background-color: #bfe1f6 !important;

        border: 2px solid #000000 !important;
        border-radius: 22px !important;

        width: 580px;
        min-height: 480px;
        margin: auto;

        box-shadow: none !important;
        overflow: visible !important;
}

        .navbar-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px 20px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-custom a {
            color: #0b5f9e;
            text-decoration: none;
            font-weight: 800;
            margin-left: 15px;
        }

        .navbar-custom .logo-text {
            color: #0b5f9e;
            font-size: 1.7rem;
            font-weight: 800;
            display: flex;
            align-items: center;
        }

        .h4.text-gray-900.mb-4 {
            font-family: 'Times New Roman', serif;
            font-weight: 700;
            color: #000;
            font-size: 2rem;
             margin-bottom: 2.2rem;
        }
        
        .form-control-user {
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.15);
            background-color: rgba(255, 255, 255, 0.9); 
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-user {
            border-radius: 30px;
            padding:  0.65rem;
            font-size: 0.95rem;
            font-weight:  600;
            background: #0b5f9e;
            border: none;
            box-shadow: 0 3px 0 rgba(0, 0, 0, 0.25);
        }

        .btn-user:hover {
             background-color: #094c80;
             transform: translateY(1px);
        }
        
        .p-5 {
            padding: 3.2rem !important;
        }

        .footer-custom {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            padding: 10px;
            background-color: transparent;
            color: rgba(0, 0, 0, 0.6);
            font-size: 0.85rem;
        }

        .login-card {
    background-color: #bfe1f6 !important;

    border: 1.8px solid #000000 !important;

    border-radius: 22px !important;

    width: 520px;
    min-height: 420px;
    margin: auto;

    overflow: visible !important;
    box-shadow: none !important;
}


    </style>

</head>

<body>
    
    <nav class="navbar-custom">
        <div class="d-flex align-items-center">
            <a href="#" class="logo-text">
                <img src="img/logo.png" alt="Logo" style="height: 48px; width: 48px; margin-right: 5px; vertical-align: middle;">
                <span>InsanMedika</span>
            </a>
        </div>
    </nav>


    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-xl-6 col-lg-7 col-md-9">

               <div class="card login-card o-hidden my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">
                                            Login InsanMedika
                                        </h1>
                                    </div>

                                    <?= $alert ?>

                                    <form class="user" method="post">
                                        
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user" name="username" id="inputUsername" placeholder="Enter your username" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user" name="password" id="inputPassword" placeholder="Enter your password" required>
                                            
                                        </div>
                                        
                                        <button type="submit" name="login" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>

                                    </form>
                                    
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <footer class="footer-custom">
        <p class="copy">
            Copyright <i class="far fa-copyright"> 2025 RS InsanMedika | Kelompok 2</i>
        </p>
    </footer>


    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>