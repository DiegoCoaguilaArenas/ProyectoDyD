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
        return '<iframe style="border-radius:12px" src="' . $embedUrl . '" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>';
    }
    return false; // Retorna falso si no es compatible, para mostrar la imagen por defecto
}

// 1. Reportaje Destacado (Solo publicados y fecha actual o pasada)
$stmtDestacado = $pdo->query("SELECT * FROM reportajes WHERE es_destacado = 1 AND estado = 'Publicado' AND fecha_publicacion <= NOW() ORDER BY fecha_publicacion DESC, id DESC LIMIT 1");
$repDestacado = $stmtDestacado->fetch();
if (!$repDestacado) {
    // Si no hay destacado, trae el último publicado
    $stmtDestacado = $pdo->query("SELECT * FROM reportajes WHERE estado = 'Publicado' AND fecha_publicacion <= NOW() ORDER BY fecha_publicacion DESC, id DESC LIMIT 1");
    $repDestacado = $stmtDestacado->fetch();
}

// 2. Grilla de 3 Reportajes Recientes
$stmtReportajes = $pdo->query("SELECT * FROM reportajes WHERE estado = 'Publicado' AND fecha_publicacion <= NOW() ORDER BY fecha_publicacion DESC, id DESC LIMIT 3");
$reportajes = $stmtReportajes->fetchAll();

// 3. Noticias Rápidas Recientes
$stmtNoticias = $pdo->query("SELECT * FROM noticias WHERE estado = 'Publicado' AND fecha_publicacion <= NOW() ORDER BY fecha_publicacion DESC, id DESC LIMIT 3");
$noticias = $stmtNoticias->fetchAll();

// 4. Último Boletín NTEP
$stmtBoletin = $pdo->query("SELECT * FROM boletines ORDER BY fecha_publicacion DESC, id DESC LIMIT 1");
$ultimoBoletin = $stmtBoletin->fetch();

// 5. Podcasts Recientes
$stmtPodcasts = $pdo->query("SELECT * FROM podcasts ORDER BY fecha_publicacion DESC LIMIT 4");
$podcasts = $stmtPodcasts->fetchAll();

// 6. Especiales (Videos) Recientes
$stmtVideos = $pdo->query("SELECT * FROM videos ORDER BY fecha_publicacion DESC LIMIT 6");
$especiales = $stmtVideos->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DDP Noticias - Diálogo y Desarrollo Perú</title>
    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style-starter.css">
    <style>
        /* Estilo para la imagen destacada sin cortes */
        .destacado-img {
            width: 100%;
            height: auto !important;
            max-height: 550px;
            object-fit: cover;
            display: block;
            border-top-right-radius: 140px; 
            border-right: 4px solid #ffffff;  
            border-top: 4px solid #ffffff;
        }

        /* Contenedor con fondo rojo para el detalle de la esquina superior derecha (Destacado) */
        .marco-reportaje {
            position: relative;
            background-color: #d60000; 
            display: inline-block;
            width: 100%;
            max-width: 580px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        /* Marco rojo y blanco para la grilla de reportajes pequeños */
        .marco-reportaje-grid {
            position: relative;
            background-color: #d60000;
            display: block;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s ease;
        }
        .marco-reportaje-grid:hover {
            transform: translateY(-4px);
        }
        .marco-reportaje-grid img {
            width: 100%;
            height: 230px;
            object-fit: cover;
            display: block;
            border-top-right-radius: 90px; 
            border-right: 3px solid #ffffff;
            border-top: 3px solid #ffffff;
        }

        /* Tipografía para el resumen del destacado */
        .video-wrap .resumen-texto, .video-wrap .resumen-texto p {
            font-family: 'Cabin', sans-serif !important;
            font-size: 1.1rem !important;
            font-style: italic !important;
            color: #555 !important;
            line-height: 1.6 !important;
            font-weight: 400 !important;
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
              <img src="assets/images/logo.png" alt="DDP Logo" title="DDP Logo" style="height:75px;" />
          </a> 
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse"
              data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
              aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item active">
                      <a class="nav-link" href="index.php">Inicio <span class="sr-only">(current)</span></a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="#actualidad">Actualidad</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="reportajes.php">Reportajes</a>
                  </li>
                  <li class="nav-item">
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
                    <h2 class="title-big">Reportajes</h2>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reportaje Destacado -->
<?php if ($repDestacado): ?>
<?php 
  $imgDestacada = !empty($repDestacado['foto_principal']) 
    ? 'revista-admin/uploads/' . $repDestacado['foto_principal'] 
    : 'assets/images/video.jpg'; 
  
  $enlaceDestacado = !empty($repDestacado['slug']) ? 'reportaje.php?slug=' . $repDestacado['slug'] : 'reportaje.php?id=' . $repDestacado['id'];
?>
<section class="w3l-video w3l-homeblock3" id="video">
    <div class="container-fluid">
        <div class="video-grids-info row">
            <div class="video-gd-right col-lg-6 p-0 d-flex align-items-center justify-content-center bg-light">
                <div class="position-relative w-100 text-center">
                    <a href="<?= $enlaceDestacado ?>" class="marco-reportaje">
                      <img src="<?= $imgDestacada ?>" alt="<?= htmlspecialchars($repDestacado['titulo']) ?>" class="img-fluid destacado-img">
                    </a>
                </div>
            </div>
            <div class="video-gd-left col-lg-6 p-lg-5 p-4 align-self">
                <div class="p-xl-4 p-0 video-wrap">
                    <h5><?= fechaEs($repDestacado['fecha_publicacion']) ?></h5>
                    <h3 class="title-big text-left mb-4">
                      <a href="<?= $enlaceDestacado ?>"><?= htmlspecialchars($repDestacado['titulo']) ?></a>
                    </h3>
                    <div class="resumen-texto">
                        <?= $repDestacado['resumen_corto'] ?>
                    </div>
                    <a href="<?= $enlaceDestacado ?>" class="btn mt-4 p-0 font-weight-bold" style="color:#dc3545;">Leer <span class="fa fa-arrow-right"></span> </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Grilla Reportajes Recientes -->
<div class="grids-block-5 py-1">
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php foreach ($reportajes as $rep): ?>
                <?php 
                  $imgRep = !empty($rep['foto_principal']) 
                    ? 'revista-admin/uploads/' . $rep['foto_principal'] 
                    : 'assets/images/reportaje-18-08-26.jpg'; 
                  
                  $enlaceRep = !empty($rep['slug']) ? 'reportaje.php?slug=' . $rep['slug'] : 'reportaje.php?id=' . $rep['id'];
                ?>
                <div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="<?= $enlaceRep ?>" class="marco-reportaje-grid">
                      <img src="<?= $imgRep ?>" alt="<?= htmlspecialchars($rep['titulo']) ?>" />
                    </a>
                    <div class="blog-info">
                        <h5><?= fechaEs($rep['fecha_publicacion']) ?></h5>
                        <h4><a href="<?= $enlaceRep ?>" class="d-block"><?= htmlspecialchars($rep['titulo']) ?></a></h4>
                        <a href="<?= $enlaceRep ?>" class="btn mt-4 p-0 font-weight-bold" style="color:#dc3545;">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="pagination">
                <ul>
                    <li><a href="reportajes.php">Ver todos</a></li>
                </ul>
            </div>
        </div>
    </section>
</div>

<!-- Noticias Recientes (Actualidad) -->
<section class="breadcrumb-area py-sm-5 py-1">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Noticias Recientes</h2><a class="anchor" id="actualidad"></a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="grids-block-5 py-5">
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php foreach ($noticias as $not): ?>
                <?php 
                  $imgNot = !empty($not['foto']) 
                    ? 'revista-admin/uploads/' . $not['foto'] 
                    : 'assets/images/nota-facebook-21-11-25.png';
                  
                  if (!empty($not['link_externo'])) {
                      $linkNot = $not['link_externo'];
                      $target = '_blank';
                  } else {
                      $parametro = !empty($not['slug']) ? 'slug=' . $not['slug'] : 'id=' . $not['id'];
                      $linkNot = 'noticia.php?' . $parametro;
                      $target = '_self';
                  }
                ?>
                <div class="col-lg-4 col-md-6 grids5-info mb-4">
                    <a target="<?= $target ?>" href="<?= htmlspecialchars($linkNot) ?>" class="d-block">
                      <img src="<?= $imgNot ?>" alt="<?= htmlspecialchars($not['titulo']) ?>" class="img-fluid" style="height:230px; width:100%; object-fit:cover;" />
                    </a>
                    <div class="blog-info">
                        <h5><?= fechaEs($not['fecha_publicacion']) ?></h5>
                        <h4><a target="<?= $target ?>" href="<?= htmlspecialchars($linkNot) ?>" class="d-block"><?= htmlspecialchars($not['titulo']) ?></a></h4>
                        <a target="<?= $target ?>" href="<?= htmlspecialchars($linkNot) ?>" class="btn mt-4 p-0 font-weight-bold" style="color:#dc3545;">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="pagination">
                <ul>
                    <li><a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru">Ver todos</a></li>
                </ul>
            </div>
        </div>
    </section>
</div>

<!-- Sección Último Boletín NTEP -->
<?php if ($ultimoBoletin): ?>
<?php 
  $portadaBol = !empty($ultimoBoletin['foto_portada']) 
    ? 'revista-admin/uploads/' . $ultimoBoletin['foto_portada'] 
    : 'assets/images/boletin-ntep-45.png'; 
?>
<section class="w3l-homeblock5 py-0">
    <div class="container py-lg-5 py-4">
        <div class="row">
            <div class="col-lg-8 align-self">
                <h3 class="title-big mb-4">Boletín NTEP - N° <?= htmlspecialchars($ultimoBoletin['numero_boletin']) ?></h3>
                <p><?= nl2br(htmlspecialchars($ultimoBoletin['resumen'] ?? '')) ?></p>
                <div class="row mt-sm-4 mt-2 px-3">
                    <div class="col-6 p-0">
                        <span>Nº <?= htmlspecialchars($ultimoBoletin['numero_boletin']) ?></span>
                        <h4><?= fechaEs($ultimoBoletin['fecha_publicacion']) ?></h4>
                    </div>
                    <div class="col-6 p-0">
                        <span>
                          <a target="_blank" href="revista-admin/uploads/<?= $ultimoBoletin['archivo_pdf'] ?>" class="facebook">
                            <span class="fa fa-download"></span>
                          </a>
                        </span>
                        <h4>Ver Boletín</h4>
                    </div>
                    <center><a href="boletines.php" class="btn btn-style btn-primary mt-md-5 mt-4">Ver todos</a></center>
                </div>
            </div>
            <div class="col-lg-4 mt-lg-0 mt-4">
                <img src="<?= $portadaBol ?>" class="img-fluid radius-image" alt="Portada Boletín">
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Podcasts Dinámica en el Index -->
<section class="w3l-homeblock3 py-5">
    <div class="container py-lg-5 py-md-4">
        <h3 class="title-big mb-5 text-center">Podcast</h3>
        <div class="row text-center">
            <?php if (empty($podcasts)): ?>
                <div class="col-12 text-muted"><p>No hay podcasts registrados.</p></div>
            <?php else: ?>
                <?php foreach ($podcasts as $pod): ?>
                <div class="col-lg-3 col-sm-6 mt-4 mt-lg-0 mb-4">
                    <div class="d-block p-3 border rounded bg-light shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div class="mb-3">
                            <?php $reproductor = generarReproductor($pod['url_embed']); ?>
                            <?php if($reproductor): ?>
                                <?= $reproductor ?>
                            <?php else: ?>
                                <img src="assets/images/podcast.png" alt="Podcast" class="img-fluid rounded-circle" style="width:85px; height:85px; object-fit:cover; margin:0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                            <?php endif; ?>
                        </div>
                        <p style="font-family: 'Cabin', sans-serif; font-size:1.05rem; line-height:1.5; color:#555; font-weight:500; margin-bottom: 15px;">
                            <?= htmlspecialchars($pod['titulo']) ?>
                        </p>
                        <a href="<?= htmlspecialchars($pod['url_embed']) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100 mt-auto">
                            <i class="fa fa-external-link me-1"></i> Escuchar en plataforma
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <center><a href="podcasts.php" class="btn btn-style mt-md-5 mt-4 text-white" style="background-color: #e60000; border-radius: 6px; padding: 10px 30px; font-weight: bold;">Ver Todos</a></center>
    </div>
</section>

<!-- Sección Especiales (Videos) Dinámica -->
<section class="w3l-team" id="team">
    <div class="teams1 py-5 mb-3">
        <div class="container py-lg-3 pb-lg-5 pb-4">
            <div class="teams1-content">
                <h3 class="title-big text-center mb-5">Especiales</h3>
                <div class="owl-carousel owl-theme text-center">
                    <?php foreach ($especiales as $esp): ?>
                    <?php 
                      $fotoEsp = !empty($esp['foto']) 
                        ? 'revista-admin/uploads/' . $esp['foto'] 
                        : 'assets/images/team2.jpg'; 
                      $reproductorEsp = generarReproductor($esp['url_embed']);
                    ?>
                    <div class="item">
                        <div class="d-grid team-info">
                            <div class="column position-relative">
                                <?php if($reproductorEsp): ?>
                                    <div class="rounded p-2 bg-light shadow-sm mb-3">
                                        <?= $reproductorEsp ?>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($esp['url_embed']) ?>" target="_blank">
                                        <div class="rounded p-2" style="background-color: #111; border-radius: 12px; height: 180px; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 6px 15px rgba(0,0,0,0.2);">
                                            <img src="<?= $fotoEsp ?>" alt="" class="img-fluid rounded" style="width:100%; height:100%; object-fit:cover; opacity: 0.85;" />
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="column mt-2">
                                <p style="font-family: 'Cabin', sans-serif; font-size:1.05rem; line-height:1.4; color:#555; font-weight:500; margin-bottom: 10px;">
                                    <?= htmlspecialchars($esp['titulo']) ?>
                                </p>
                                <a href="<?= htmlspecialchars($esp['url_embed']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                    <i class="fa fa-play-circle me-1"></i> Ver original
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="w3l-banner py-0" id="work">
    <div class="midd-w3 py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mt-lg-0 mt-lg-5 about-right-faq align-self">
                    <h5 class="title-small mb-2">DDP Noticias</h5>
                    <h3 class="title-banner">Diálogo y Desarrollo Perú</h3>
                    <p class="mt-4">Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
                    <a href="about.html" class="btn btn-style btn-primary mt-md-5 mt-4">Nosotros</a>
                </div>
                <div class="col-md-6 left-wthree-img mt-lg-0 mt-4">
                    <div class="position-relative">
                        <img src="assets/images/bannerimg.jpg" alt="" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="middle py-5">
    <div class="container py-xl-5 py-lg-3">
        <div class="welcome-left text-center py-md-5 py-3">
            <h3 class="title-big">Síguenos en nuestras Redes Sociales</h3>
            <div class="main-social-footer-29">
                <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square fa-2x"></span></a>
                <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokg.png" alt="TikTok"></a>
                <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram fa-2x"></span></a>
            </div>
        </div>
    </div>
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

<!-- Template JavaScript -->
<script src="assets/js/jquery-3.3.1.min.js"></script>
<script src="assets/js/theme-change.js"></script>
<script src="assets/js/easyResponsiveTabs.js"></script>
<script src="assets/js/owl.carousel.js"></script>
<script>
  $(document).ready(function () {
    $('.owl-carousel').owlCarousel({
      loop: true, margin: 0, responsiveClass: true,
      responsive: {
        0: { items: 1, nav: true },
        400: { items: 2, nav: true, margin: 20 },
        768: { items: 3, nav: true, margin: 20 },
        1000: { items: 4, nav: true, loop: true, margin: 25 }
      }
    });
  });
</script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>