<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$role_petugas = $_SESSION['role'] ?? 'Guest';
$nama_petugas = $_SESSION['petugas'] ?? 'Guest';
$current_url = $_SERVER['PHP_SELF'];

$app_folder = defined('APP_FOLDER') ? APP_FOLDER : '/rumahsakit/'; 

$current_url_path = str_replace($app_folder, '', $current_url);

$current_url_path = ltrim($current_url_path, '/');


function isActive($target_path, $current_path) {
    return ($target_path == $current_path) ? 'active' : '';
}
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $base_url ?>index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-hospital"></i>
        </div>
        <div class="sidebar-brand-text mx-3">RS InsanMedika</div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item <?= isActive('index.php', $current_url_path) ?>">
        <a class="nav-link" href="<?= $base_url ?>index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Menu</div>

    <?php if (in_array($role_petugas, ['Admin'])): ?>
    <?php
        $master_active = isActive('petugas/select.php', $current_url_path) || 
                         isActive('ruangan/select.php', $current_url_path) || 
                         isActive('tempattidur/select.php', $current_url_path);
    ?>
    <li class="nav-item <?= $master_active ? 'active' : '' ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMaster"
            aria-expanded="<?= $master_active ? 'true' : 'false' ?>" aria-controls="collapseMaster">
            <i class="fas fa-fw fa-database"></i>
            <span>Master Data</span>
        </a>
        <div id="collapseMaster" class="collapse <?= $master_active ? 'show' : '' ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Data Master:</h6>
                <a class="collapse-item <?= isActive('petugas/select.php', $current_url_path) ?>" href="<?= $base_url ?>petugas/select.php">Data Petugas</a>
                <a class="collapse-item <?= isActive('ruangan/select.php', $current_url_path) ?>" href="<?= $base_url ?>ruangan/select.php">Data Ruangan</a>
                <a class="collapse-item <?= isActive('tempattidur/select.php', $current_url_path) ?>" href="<?= $base_url ?>tempattidur/select.php">Data Tempat Tidur</a>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if (in_array($role_petugas, ['Admin', 'Perawat'])): ?>
    <li class="nav-item <?= isActive('status/select.php', $current_url_path) ?>">
        <a class="nav-link" href="<?= $base_url ?>status/select.php">
            <i class="fas fa-fw fa-bed"></i>
            <span>Manajemen Bed</span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (in_array($role_petugas, ['Admin', 'Admisi'])): ?>
    <li class="nav-item <?= isActive('pasien/select.php', $current_url_path) ?>">
        <a class="nav-link" href="<?= $base_url ?>pasien/select.php">
           <i class="fas fa-fw fa-user-injured"></i>
           <span>Manajemen Pasien</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if (in_array($role_petugas, ['Admin','Petugas Kebersihan'])): ?>
    <li class="nav-item <?= isActive('kebersihan/select.php', $current_url_path) ?>">
        <a class="nav-link" href="<?= $base_url ?>kebersihan/select.php">
            <i class="fas fa-broom"></i>
            <span>Manajemen Kebersihan</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if (in_array($role_petugas, ['Admin', 'Perawat', 'Admisi'])): ?>
    <li class="nav-item <?= isActive('rawatinap/select.php', $current_url_path) ?>">
        <a class="nav-link" href="<?= $base_url ?>rawatinap/select.php">
          <i class="fas fa-fw fa-procedures"></i>
            <span>Rawat Inap</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if (in_array($role_petugas, ['Admin'])): ?>
        <li class="nav-item <?= isActive('laporan/laporan.php', $current_url_path) ?>">
            <a class="nav-link" href="<?= $base_url ?>laporan/laporan.php">
            <i class="fas fa-tasks"></i>
                <span>Laporan</span>
            </a>
        </li>
    <?php endif; ?>
    
    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>