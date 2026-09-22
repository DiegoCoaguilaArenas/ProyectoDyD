<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Consultas para los contadores
$totalReportajes = $pdo->query("SELECT COUNT(*) FROM reportajes")->fetchColumn();
$totalNoticias   = $pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$totalBoletines  = $pdo->query("SELECT COUNT(*) FROM boletines")->fetchColumn();
$totalPodcasts   = $pdo->query("SELECT COUNT(*) FROM podcasts")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard | DDP ADMIN</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>

  <?php include 'includes/sidebar.php'; ?>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6"><h3 class="mb-0">Panel de Control</h3></div>
        </div>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
              <div class="inner"><h3><?= $totalReportajes ?></h3><p>Reportajes</p></div>
              <i class="small-box-icon bi bi-journal-text"></i>
              <a href="reportajes.php" class="small-box-footer link-light link-underline-opacity-0">Gestionar <i class="bi bi-link-45deg"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
              <div class="inner"><h3><?= $totalNoticias ?></h3><p>Noticias Rápidas</p></div>
              <i class="small-box-icon bi bi-newspaper"></i>
              <a href="noticias.php" class="small-box-footer link-light link-underline-opacity-0">Gestionar <i class="bi bi-link-45deg"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
              <div class="inner"><h3><?= $totalBoletines ?></h3><p>Boletines PDF</p></div>
              <i class="small-box-icon bi bi-file-earmark-pdf"></i>
              <a href="boletines.php" class="small-box-footer link-light link-underline-opacity-0">Gestionar <i class="bi bi-link-45deg"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
              <div class="inner"><h3><?= $totalPodcasts ?></h3><p>Podcasts</p></div>
              <i class="small-box-icon bi bi-mic"></i>
              <a href="podcasts.php" class="small-box-footer link-light link-underline-opacity-0">Gestionar <i class="bi bi-link-45deg"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/js/adminlte.min.js"></script>
</body>
</html>