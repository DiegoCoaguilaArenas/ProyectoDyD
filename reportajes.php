<?php
session_start();
require_once 'revista-admin/config/db.php';

function fechaEs($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Ago', '09'=>'Set', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'];
    $ts = strtotime($fecha);
    return ($meses[date('m', $ts)] ?? '') . ' ' . date('d', $ts) . ', ' . date('Y', $ts);
}

// ==========================================
// CONFIGURACIÓN DEL PAGINADOR
// ==========================================
$limite = 9; // Cantidad de reportajes que se verán por cada página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// 1. Contar cuántos reportajes publicados existen en total
$stmtCount = $pdo->query("SELECT COUNT(*) FROM reportajes WHERE estado = 'Publicado' AND fecha_publicacion <= NOW()");
$total_reportajes = $stmtCount->fetchColumn();

// 2. Calcular cuántas páginas existirán en total (Ej. si hay 10 reportajes, serán 2 páginas)
$total_paginas = ceil($total_reportajes / $limite);
if ($total_paginas == 0) $total_paginas = 1; 

// Si un usuario curioso escribe en la URL "?pagina=100" y solo hay 5, lo devolvemos a la última válida
if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

// 3. Calcular el OFFSET (salto de registros). 
// Ej: Página 1 = Offset 0. Página 2 = Offset 9.
$offset = ($pagina_actual - 1) * $limite;

// ==========================================
// CONSULTA PRINCIPAL LIMITADA
// ==========================================
// Usamos bindValue porque PDO requiere tipo entero estricto para LIMIT y OFFSET
$sql = "SELECT r.*, 
           CASE 
             WHEN r.autor_id IS NULL THEN 'Redacción DDP'
             WHEN a.es_nickname = 1 THEN a.nickname 
             ELSE CONCAT(a.nombres, ' ', COALESCE(a.ap_paterno, '')) 
           END AS nombre_autor
        FROM reportajes r
        LEFT JOIN autores a ON r.autor_id = a.id
        WHERE r.estado = 'Publicado' AND r.fecha_publicacion <= NOW() 
        ORDER BY r.fecha_publicacion DESC, r.id DESC 
        LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reportajes = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reportajes Especiales - DDP Noticias</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600,700&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style-starter.css">
    <style>
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
        
        /* ESTILOS DEL PAGINADOR PROFESIONAL */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 4rem;
        }
        .pagination-container .page-item {
            list-style: none;
        }
        .pagination-container .page-item .page-link {
            color: #444;
            border: 1px solid #ddd;
            padding: 10px 18px;
            margin: 0 5px;
            border-radius: 5px;
            font-family: 'Cabin', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            transition: 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .pagination-container .page-item.active .page-link {
            background-color: #d60000;
            color: white;
            border-color: #d60000;
        }
        .pagination-container .page-item .page-link:hover:not(.active) {
            background-color: #f1f1f1;
            color: #d60000;
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

<!-- header unificado -->
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
                  <li class="nav-item">
                      <a class="nav-link" href="index.php">Inicio</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="index.php#actualidad">Actualidad</a>
                  </li>
                  <li class="nav-item active">
                      <a class="nav-link" href="reportajes.php">Reportajes <span class="sr-only">(current)</span></a>
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
<!-- //header unificado -->

<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents d-flex justify-content-between align-items-center">
                    <h2 class="title-big">Reportajes Especiales</h2>
                    <div class="breadcrumb mb-0">
                        <ul class="mb-0">
                            <li><a href="index.php" class="text-black">Inicio</a></li>
                            <li class="active text-gray-50"> Reportajes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="grids-block-5 py-5">
    <div class="container">
        <div class="row">
            <?php if (count($reportajes) > 0): ?>
                <?php foreach ($reportajes as $rep): ?>
                <?php 
                  $imgRep = !empty($rep['foto_principal']) 
                    ? 'revista-admin/uploads/' . $rep['foto_principal'] 
                    : 'assets/images/video.jpg'; 
                    
                  $enlace = !empty($rep['slug']) ? 'reportaje.php?slug=' . htmlspecialchars($rep['slug']) : 'reportaje.php?id=' . $rep['id'];
                ?>
                <div class="col-lg-4 col-md-6 grids5-info mt-4">
                    <a href="<?= $enlace ?>" class="marco-reportaje-grid">
                      <img src="<?= $imgRep ?>" alt="<?= htmlspecialchars($rep['titulo']) ?>" />
                    </a>
                    <div class="blog-info mt-3">
                        <h5 class="text-muted"><i class="fa fa-calendar mr-1"></i> <?= fechaEs($rep['fecha_publicacion']) ?></h5>
                        <h4><a href="<?= $enlace ?>" class="d-block text-dark font-weight-bold" style="font-family:'Cabin',sans-serif;"><?= htmlspecialchars($rep['titulo']) ?></a></h4>
                        
                        <?php if (!empty($rep['resumen_corto'])): ?>
                            <p class="mt-2 text-muted" style="font-size: 0.95rem; line-height: 1.5;">
                                <?= mb_strimwidth(strip_tags($rep['resumen_corto']), 0, 110, "...") ?>
                            </p>
                        <?php endif; ?>
                        
                        <a href="<?= $enlace ?>" class="btn mt-3 p-0 font-weight-bold" style="color:#dc3545;">Leer completo <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fa fa-newspaper-o fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">Aún no hay reportajes publicados.</h3>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========================================== -->
        <!-- RENDERIZADO DEL PAGINADOR HTML             -->
        <!-- ========================================== -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination-container">
            <ul class="pagination mb-0">
                
                <!-- Botón Anterior -->
                <?php if ($pagina_actual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="reportajes.php?pagina=<?= $pagina_actual - 1 ?>"><i class="fa fa-angle-left mr-1"></i> Anterior</a>
                    </li>
                <?php endif; ?>

                <!-- Números de Página dinámicos -->
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                        <a class="page-link" href="reportajes.php?pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Botón Siguiente -->
                <?php if ($pagina_actual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="reportajes.php?pagina=<?= $pagina_actual + 1 ?>">Siguiente <i class="fa fa-angle-right ml-1"></i></a>
                    </li>
                <?php endif; ?>
                
            </ul>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- footer block unificado -->
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
<!-- //footer block unificado -->

<script src="assets/js/jquery-3.3.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>