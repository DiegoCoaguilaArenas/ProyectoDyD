<?php
require_once 'revista-admin/config/db.php';

function fechaEs($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Ago', '09'=>'Set', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'];
    $ts = strtotime($fecha);
    return ($meses[date('m', $ts)] ?? '') . ' ' . date('d', $ts) . ', ' . date('Y', $ts);
}

// Consultar todos los podcasts ordenados del más reciente al más antiguo
$stmt = $pdo->query("SELECT * FROM podcasts ORDER BY fecha_publicacion DESC, id DESC");
$podcasts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Podcasts - DDP Noticias</title>
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style-starter.css">
    <style>
        .podcast-card {
            transition: transform 0.3s ease;
        }
        .podcast-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

<!-- header -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="index.php">
              <img src="assets/images/logo.png" alt="Logo DDP" style="height:75px;" />
          </a> 
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse"
              data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
              aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item">
                      <a class="nav-link" href="index.php">Inicio</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="index.php#actualidad">Actualidad</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="reportajes.php">Reportajes</a>
                  </li>
                  <li class="nav-item active">
                      <a class="nav-link" href="podcasts.php">Podcast</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="boletines.php">Boletín NTEP</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="about.html">Alianzas</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="contact.html">Sobre D&D</a>
                  </li>               
                  <li class="ml-2">
                      <a href="#footer" class="btn btn-style btn-outline-secondary">Contacto</a>
                  </li>
              </ul>
          </div>
      </nav>
  </div>
</header>
<!-- //header -->

<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Podcasts</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="index.php">Inicio</a></li>
                            <li class="active">Podcasts</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="grids-block-5 py-5">
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php if (empty($podcasts)): ?>
                    <div class="col-12 text-center py-5">
                        <p class="lead text-muted">No hay podcasts publicados por el momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($podcasts as $pod): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-5 podcast-card">
                        <a href="<?= htmlspecialchars($pod['url_embed']) ?>" target="_blank" class="d-block text-center text-decoration-none p-3 border rounded bg-light shadow-sm h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-3">
                                    <img src="assets/images/podcast.png" alt="Podcast" class="img-fluid rounded-circle" style="width:90px; height:90px; object-fit:cover; margin:0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                                </div>
                                <span class="text-muted small d-block mb-2"><?= fechaEs($pod['fecha_publicacion']) ?></span>
                                <p style="font-family: 'Cabin', sans-serif; font-size:1.05rem; line-height:1.5; color:#333; font-weight:600;">
                                    <?= htmlspecialchars($pod['titulo']) ?>
                                </p>
                            </div>
                            <div class="mt-3">
                                <span class="btn btn-sm btn-outline-danger font-weight-bold px-3">Escuchar <span class="fa fa-arrow-right ml-1"></span></span>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- footer block -->
<section class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="row footer-top-29">
        <div class="col-lg-6 col-md-6 footer-list-29 footer-1">
          <h6 class="footer-title-29">Quiénes Somos</h6>
          <p>Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
          <div class="main-social-footer-29">
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokp.png" alt="TikTok"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
          <ul>
            <h6 class="footer-title-29">Contenido</h6>
            <li><a href="index.php#actualidad">Noticias</a></li>
            <li><a href="reportajes.php">Reportajes</a></li>
            <li><a href="boletines.php">Boletines</a></li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-6 mt-lg-0 mt-5 footer-list-29 footer-3">
          <div class="properties">
            <h6 class="footer-title-29">Contacto</h6>
            <ul>
              <li><a href="mailto:info@dialogoydesarrollo.com.pe">info@dialogoydesarrollo.com.pe</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="bottom-copies text-center">
            <p class="copy-footer-29">© <?= date('Y') ?> Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info">WebSolutions</a></p>
      </div>
    </div>
  </div>
  <button onclick="topFunction()" id="movetop" title="Go to top">
    <span class="fa fa-angle-up"></span>
  </button>
  <script>
    window.onscroll = function () { scrollFunction() };
    function scrollFunction() {
      if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        document.getElementById("movetop").style.display = "block";
      } else {
        document.getElementById("movetop").style.display = "none";
      }
    }
    function topFunction() {
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    }
  </script>
</section>

<script src="assets/js/jquery-3.3.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>