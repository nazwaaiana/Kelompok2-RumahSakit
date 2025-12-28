<?php
if (!isset($_SESSION['idpetugas'])) {
    header("location: " . BASE_URL . "login.php");
    exit;
}

$current_role = $_SESSION['role'] ?? 'Guest';
function hasAccess($allowed_roles, $current_role) {
    return in_array($current_role, $allowed_roles);
}

function requireRole($allowed_roles) {
    global $current_role;
    
    if (!hasAccess($allowed_roles, $current_role)) {
        $_SESSION['error_message'] = "Akses ditolak! Role Anda ($current_role) tidak memiliki izin untuk halaman ini.";
        
        $index_page = (strpos($_SERVER['PHP_SELF'], '/') !== false && strpos($_SERVER['PHP_SELF'], '/') != 0) 
                      ? "../index.php" 
                      : "index.php";
        
        header("location: $index_page");
        exit;
    }
}

function requireRoleOr403($allowed_roles) {
    global $current_role;
    
    if (!hasAccess($allowed_roles, $current_role)) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 - Akses Ditolak</title>
            <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Nunito', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; }
                .error-container { text-align: center; background: white; padding: 60px 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; width: 100%; animation: slideUp 0.5s ease; }
                @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
                .error-icon { font-size: 80px; margin-bottom: 20px; }
                .error-code { font-size: 120px; font-weight: bold; color: #e74a3b; margin: 0; line-height: 1; }
                .error-message { font-size: 28px; color: #5a5c69; margin: 20px 0; font-weight: 600; }
                .error-details { color: #858796; margin: 10px 0 30px 0; line-height: 1.6; font-size: 16px; }
                .role-badge { display: inline-block; background: #e74a3b; color: white; padding: 5px 15px; border-radius: 20px; font-weight: 600; margin: 10px 0; }
                .btn { display: inline-block; padding: 15px 40px; background: #4e73df; color: white; text-decoration: none; border-radius: 50px; transition: all 0.3s; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4); }
                .btn:hover { background: #2e59d9; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(78, 115, 223, 0.6); }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">🚫</div>
                <h1 class="error-code">403</h1>
                <p class="error-message">Akses Ditolak</p>
                <p class="error-details">
                    Anda tidak memiliki izin untuk mengakses halaman ini.<br>
                    Role Anda saat ini:
                </p>
                <span class="role-badge"><?= htmlspecialchars($current_role) ?></span>
                <p class="error-details" style="margin-top: 20px;">
                    Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
                </p>
                <a href="<?= BASE_URL ?>index.php" class="btn">Kembali ke Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
?>