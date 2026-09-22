<?php
require_once 'revista-admin/config/db.php';

function fechaEs($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Ago', '09'=>'Set', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'];
    $ts = strtotime($fecha);
    return ($meses[date('m', $ts)] ?? '') . ' ' . date('d', $ts) . ', ' . date('Y', $ts);
}

// Consultar todos los boletines registrados
$stmt = $pdo->query("SELECT * FROM boletines ORDER BY fecha_publicacion DESC, id DESC");
$boletines = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Boletines NTEP - DDP Noticias</title>
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style-starter.css">
    <style>
        /* Estilo para ajustar perfectamente la infografía del boletín sin cortes */
        .boletin-card-img {
            width: 100%;
            height: auto !important;
            max-height: 600px;
            object-fit: contain;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.3s ease;
        }
        .boletin-card-img:hover {
            transform: scale(1.01);
        }
        .btn-ver-boletin {
            color: #dc3545;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 1.1rem;
            margin-top: 1rem;
        }
        .btn-ver-boletin:hover {
            color: #a71d2a;
            text-decoration: none;
        }
        .blog-info h5 {
            color: #777;
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
        }
        .blog-info h4 {
            font-size: 1.2rem;
            font-weight: bold;
            color: #222;
        }
            /* ========================================================= */
    /* AJUSTES RESPONSIVES (MÓVILES Y TABLETS)                   */
    /* ========================================================= */

    @media (max-width: 991px) {
        /* Ajustes para el Reportaje Destacado en Celular/Tablet */
        .video-gd-left {
            padding: 2rem 1rem !important; /* Reduce el espacio interior */
        }
        .video-gd-right {
            padding: 1rem !important;
        }
        .marco-reportaje {
            max-width: 100% !important; /* Que ocupe todo el ancho en móvil */
            display: block;
        }
        .destacado-img {
            max-height: 350px !important; /* Imagen más pequeña en alto */
            border-top-right-radius: 60px !important; /* Curva menos pronunciada en celular */
            border-width: 2px !important;
        }
        .title-big {
            font-size: 1.8rem !important; /* Títulos un poco más pequeños */
            line-height: 1.2 !important;
        }
        .resumen-texto, .resumen-texto p {
            font-size: 1rem !important; /* Texto más legible en móvil */
        }
        
        /* Ajustes para la Grilla de Noticias y Reportajes */
        .marco-reportaje-grid img {
            height: auto !important; /* Anular la altura fija que deforma la foto */
            max-height: 250px;       /* Poner un tope máximo */
            border-top-right-radius: 50px !important;
        }
        .grids5-info {
            margin-top: 2rem !important; /* Separar mejor las tarjetas apiladas */
        }
        
        /* Ajustes para el Menú Superior */
        .navbar-collapse {
            background-color: #ffffff; /* Asegurar fondo blanco al abrir el menú en celular */
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            position: absolute;
            width: 100%;
            top: 80px;
            left: 0;
            z-index: 999;
        }
        
        /* Paginador Responsive */
        .pagination-container .page-link {
            padding: 8px 12px !important;
            font-size: 0.9rem !important;
            margin: 0 2px !important;
        }
    }

    @media (max-width: 576px) {
        /* Ajustes exclusivos para celulares pequeños */
        .title-banner {
            font-size: 2.2rem !important;
        }
        .breadcrumb-contents .title-big {
            font-size: 1.5rem !important;
        }
        .content-body h1 {
            font-size: 1.6rem !important; /* Títulos dentro de la nota */
        }
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
                  <li class="nav-item">
                      <a class="nav-link" href="podcasts.php">Podcast</a>
                  </li>
                  <li class="nav-item active">
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
                    <h2 class="title-big">Boletines NTEP</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="index.php">Inicio</a></li>
                            <li class="active">Boletines</li>
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
                <?php if (empty($boletines)): ?>
                    <div class="col-12 text-center py-5">
                        <p class="lead text-muted">No hay boletines publicados por el momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($boletines as $b): ?>
                    <?php 
                      $portada = !empty($b['foto_portada']) 
                        ? 'revista-admin/uploads/' . $b['foto_portada'] 
                        : 'assets/images/boletin-ntep-45.png';
                      $archivoPdf = 'revista-admin/uploads/' . $b['archivo_pdf'];
                    ?>
                    <div class="col-lg-4 col-md-6 grids5-info mb-5">
                        <a target="_blank" href="<?= $archivoPdf ?>" class="d-block mb-3">
                          <img src="<?= $portada ?>" alt="Boletín N° <?= htmlspecialchars($b['numero_boletin']) ?>" class="img-fluid boletin-card-img" />
                        </a>
                        <div class="blog-info">
                            <h5><?= fechaEs($b['fecha_publicacion']) ?></h5>
                            <a target="_blank" href="<?= $archivoPdf ?>" class="btn-ver-boletin">
                                Ver Boletín &rarr;
                            </a>
                        </div>
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