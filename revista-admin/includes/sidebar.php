<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userRol = $_SESSION['rol'] ?? '';
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="dashboard.php" class="brand-link">
      <span class="brand-text fw-light"><b>DDP</b> ADMIN</span>
    </a>
  </div>
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <li class="nav-header">CONTENIDO</li>
        <li class="nav-item">
          <a href="reportajes.php" class="nav-link">
            <i class="nav-icon bi bi-journal-text"></i>
            <p>Reportajes</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="noticias.php" class="nav-link">
            <i class="nav-icon bi bi-newspaper"></i>
            <p>Noticias Rápidas</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="boletines.php" class="nav-link">
            <i class="nav-icon bi bi-file-earmark-pdf"></i>
            <p>Boletines PDF</p>
          </a>
        </li>
        <li class="nav-header">MULTIMEDIA</li>
        <li class="nav-item">
          <a href="podcasts.php" class="nav-link">
            <i class="nav-icon bi bi-mic"></i>
            <p>Podcasts</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="especiales.php" class="nav-link">
            <i class="nav-icon bi bi-play-circle-fill"></i>
            <p>Especiales (Videos)</p>
          </a>
        </li>
        <?php if ($userRol === 'admin'): ?>
        <li class="nav-header">ADMINISTRACIÓN</li>
        <li class="nav-item">
          <a href="autores.php" class="nav-link">
            <i class="nav-icon bi bi-person-badge"></i>
            <p>Autores</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="usuarios.php" class="nav-link">
            <i class="nav-icon bi bi-people"></i>
            <p>Usuarios del Sistema</p>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</aside>