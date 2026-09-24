<?php
require_once 'revista-admin/config/db.php';

function fechaEs($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Ago', '09'=>'Set', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'];
    $ts = strtotime($fecha);
    return ($meses[date('m', $ts)] ?? '') . ' ' . date('d', $ts) . ', ' . date('Y', $ts);
}

// Función para detectar plataforma y generar iframe
function generarReproductor($url) {
    if (strpos($url, 'youtube.com/watch?v=') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $vars);
        $id = $vars['v'] ?? '';
        return '<iframe class="w-100 rounded shadow-sm" style="aspect-ratio: 16/9;" src="https://www.youtube.com/embed/' . $id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    } elseif (strpos($url, 'youtu.be/') !== false) {
        $id = basename(parse_url($url, PHP_URL_PATH));
        return '<iframe class="w-100 rounded shadow-sm" style="aspect-ratio: 16/9;" src="https://www.youtube.com/embed/' . $id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    } elseif (strpos($url, 'open.spotify.com') !== false) {
        $embedUrl = str_replace('open.spotify.com/', 'open.spotify.com/embed/', $url);
        return '<iframe style="border-radius:12px" src="' . $embedUrl . '" width="100%" height="352" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>';
    }
    return false; // Retorna falso si no es compatible
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
                        <div class="p-4 border rounded bg-white shadow-sm h-100 d-flex flex-column justify-content-between" style="border-radius: 16px !important;">
                            <div>
                                <div class="mb-3 text-center">
                                    <img src="assets/images/podcast.png" alt="Podcast" class="img-fluid rounded-circle" style="width:110px; height:110px; object-fit:cover; margin:0 auto; box-shadow: 0 6px 15px rgba(0,0,0,0.1); border: 3px solid #e60000;">
                                </div>
                                <span class="text-muted small d-block mb-2 text-center"><?= fechaEs($pod['fecha_publicacion']) ?></span>
                                <p class="text-center" style="font-family: 'Cabin', sans-serif; font-size:1.15rem; line-height:1.4; color:#333; font-weight:600;">
                                    <?= htmlspecialchars($pod['titulo']) ?>
                                </p>
                            </div>
                            <div class="mt-4 text-center">
                                <!-- Botón Principal Redondeado -->
                                <button class="btn w-100 text-white mb-2 shadow-sm" style="background-color: #e60000; border-radius: 50px; font-weight: 600;" data-toggle="modal" data-target="#modalPodcast<?= $pod['id'] ?>">
                                    <i class="fa fa-play-circle mr-1"></i> Reproducir Aquí
                                </button>
                                <!-- Enlace limpio para plataforma externa -->
                                <a href="<?= htmlspecialchars($pod['url_embed']) ?>" target="_blank" class="d-block text-danger font-weight-bold mt-2" style="font-size: 0.95rem; text-decoration: none;">
                                    Ir a la plataforma <i class="fa fa-external-link ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Ventana Emergente (Modal) para este Podcast -->
                    <div class="modal fade" id="modalPodcast<?= $pod['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                          <div class="modal-header" style="background-color: #e60000; padding: 12px 20px;">
                            <h5 class="modal-title text-white" style="font-family: 'Cabin', sans-serif; font-size: 1.1rem; font-weight: 600;">
                                <i class="fa fa-podcast mr-2"></i><?= htmlspecialchars($pod['titulo']) ?>
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1; text-shadow: none;">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body bg-light p-4 text-center">
                            <?php 
                              $reproductor = generarReproductor($pod['url_embed']); 
                              if($reproductor) {
                                  echo $reproductor;
                              } else {
                                  echo '<div class="alert alert-warning">Este enlace no admite reproducción integrada. Utiliza el botón de "Ir a la plataforma".</div>';
                              }
                            ?>
                          </div>
                        </div>
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
<script>
  // Detener el reproductor si el usuario cierra la ventana emergente
  $('.modal').on('hidden.bs.modal', function () {
    var iframe = $(this).find('iframe');
    if (iframe.length) {
      var src = iframe.attr('src');
      iframe.attr('src', '');
      iframe.attr('src', src);
    }
  });
</script>
</body>
</html>