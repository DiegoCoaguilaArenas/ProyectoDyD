<?php
session_start();
require_once 'revista-admin/config/db.php';

function fechaEs($fecha) {
    if (!$fecha) return '';
    $meses = ['01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Ago', '09'=>'Set', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'];
    $ts = strtotime($fecha);
    return ($meses[date('m', $ts)] ?? '') . ' ' . date('d', $ts) . ', ' . date('Y', $ts);
}

// 1. Recibir ID o SLUG (Para URLs amigables o antiguas)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// 2. Consulta a la Base de Datos (Prioriza el slug, si no hay, busca por ID)
$stmt = $pdo->prepare("
    SELECT r.*, 
           CASE 
             WHEN r.autor_id IS NULL THEN 'Redacción DDP'
             WHEN a.es_nickname = 1 THEN a.nickname 
             ELSE CONCAT(a.nombres, ' ', COALESCE(a.ap_paterno, '')) 
           END AS nombre_autor
    FROM reportajes r
    LEFT JOIN autores a ON r.autor_id = a.id
    WHERE (r.slug = ? OR r.id = ?)
");
$stmt->execute([$slug, $id]);
$reportaje = $stmt->fetch();

// 3. Validaciones de Existencia y Estado
if (!$reportaje) {
    header("Location: reportajes.php");
    exit;
}

// Lógica de "Vista Previa": Si es un Borrador/Oculto, solo pueden verlo los administradores o editores.
$es_admin_o_editor = (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'editor', 'redactor']));

if ($reportaje['estado'] !== 'Publicado' && !$es_admin_o_editor) {
    // Si no está publicado y es un usuario común, lo enviamos al listado general
    header("Location: reportajes.php");
    exit;
}

// 4. Consultas para el Sidebar (Omitiendo el actual y asegurando que estén publicados)
$rep_id_actual = $reportaje['id'];
$stmtSidebar = $pdo->query("SELECT slug, id, titulo, fecha_publicacion FROM reportajes WHERE id != $rep_id_actual AND estado = 'Publicado' AND fecha_publicacion <= NOW() ORDER BY fecha_publicacion DESC LIMIT 3");
$ultimasNoticias = $stmtSidebar->fetchAll();

$stmtArchivos = $pdo->query("SELECT DISTINCT DATE_FORMAT(fecha_publicacion, '%Y-%m') as mes_anio, fecha_publicacion FROM reportajes WHERE estado = 'Publicado' ORDER BY fecha_publicacion DESC LIMIT 6");
$archivos = $stmtArchivos->fetchAll();

// URL absoluta para el SEO (Open Graph)
$dominio = "https://" . $_SERVER['HTTP_HOST'];
$url_actual = $dominio . $_SERVER['REQUEST_URI'];
$imagen_og = !empty($reportaje['foto_principal']) ? $dominio . '/revista-admin/uploads/' . $reportaje['foto_principal'] : $dominio . '/assets/images/logo.png';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- ETIQUETAS SEO BÁSICAS -->
    <title><?= htmlspecialchars($reportaje['titulo']) ?> - DDP Noticias</title>
    <meta name="description" content="<?= htmlspecialchars(strip_tags($reportaje['resumen_corto'] ?? 'Conoce los detalles de este reportaje especial de Diálogo y Desarrollo Perú.')) ?>">
    
    <!-- ETIQUETAS OPEN GRAPH (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?= htmlspecialchars($reportaje['titulo']) ?>" />
    <meta property="og:description" content="<?= htmlspecialchars(strip_tags($reportaje['resumen_corto'] ?? 'Conoce los detalles de este reportaje especial de Diálogo y Desarrollo Perú.')) ?>" />
    <meta property="og:image" content="<?= htmlspecialchars($imagen_og) ?>" />
    <meta property="og:url" content="<?= htmlspecialchars($url_actual) ?>" />
    <meta property="og:site_name" content="DDP Noticias" />
    
    <!-- ETIQUETAS TWITTER CARDS -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($reportaje['titulo']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars(strip_tags($reportaje['resumen_corto'] ?? 'Conoce los detalles de este reportaje especial de Diálogo y Desarrollo Perú.')) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($imagen_og) ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600,700&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style-starter.css">
    <style>
        .resumen-destacado {
            font-family: 'Cabin', sans-serif !important;
            font-size: 1.25rem !important;
            font-style: italic !important;
            font-weight: 600 !important;
            line-height: 1.65 !important;
            color: #222 !important;
            margin: 2rem 0 !important;
            padding: 0 0.5rem !important;
            display: block;
        }
        .resumen-destacado p, .resumen-destacado span {
            display: inline !important;
            font-family: inherit !important;
            font-size: inherit !important;
            font-style: inherit !important;
            font-weight: inherit !important;
            color: inherit !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .resumen-destacado::before { content: '" '; font-size: 1.3rem; font-weight: bold; }
        .resumen-destacado::after { content: ' "'; font-size: 1.3rem; font-weight: bold; }

        .content-body, .content-body p {
            font-family: 'Cabin', sans-serif !important;
            font-size: 1.05rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 1.5rem;
        }
        .content-body h1, .content-body h2, .content-body h3, .content-body h4, .content-body h5 {
            font-family: 'Cabin', sans-serif !important;
            font-weight: 700 !important;
            color: #000 !important;
            margin-top: 2.2rem;
            margin-bottom: 0.8rem;
        }
        .content-body h1 { font-size: 1.8rem; }
        .content-body h2 { font-size: 1.6rem; }
        .content-body h3 { font-size: 1.4rem; }
        .content-body h4 { font-size: 1.2rem; }

        .content-body img {
            border-radius: 10px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 2rem auto 0.5rem auto; 
        }
        .leyenda-imagen {
            font-family: 'Cabin', sans-serif !important;
            font-style: italic !important;
            color: #000 !important;
            text-align: center !important;
            font-size: 0.95rem !important;
            line-height: 1.6 !important;
            margin-top: 0.4rem !important;
            margin-bottom: 2rem !important;
        }

        .sidebar-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 1.5rem; color: #222; }
        .sidebar-list { list-style: none; padding: 0; margin: 0 0 2.5rem 0; }
        .sidebar-list li { margin-bottom: 1.2rem; }
        .sidebar-list li a { color: #333; font-size: 0.95rem; font-weight: 500; text-decoration: none; display: block; line-height: 1.4; }
        .sidebar-list li a:hover { color: #dc3545; }
        .sidebar-list .date { font-size: 0.8rem; color: #888; display: block; margin-top: 0.3rem; }
        .archivo-list { list-style: disc; padding-left: 1.2rem; }
        .archivo-list li { margin-bottom: 0.8rem; }
        .archivo-list li a { color: #666; text-decoration: none; font-size: 1rem; }
        .archivo-list li a:hover { color: #dc3545; }
        .back-link { display: inline-block; font-weight: bold; color: #dc3545; text-decoration: none; font-size: 1.1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eaeaea; width: 100%; }
        .back-link:hover { color: #b02a37; }
        .img-hover-zoom { transition: transform 0.3s ease; }
        .img-hover-zoom:hover { transform: scale(1.01); opacity: 0.95; }
        
        /* Etiqueta de borrador para administradores */
        .badge-borrador {
            background-color: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
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

<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="index.php">
              <img src="assets/images/logo.png" alt="Logo DDP" style="height:75px;" />
          </a> 
          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                  <li class="nav-item active"><a class="nav-link" href="reportajes.php">Reportajes</a></li>
                  <li class="nav-item"><a class="nav-link" href="boletines.php">Boletín NTEP</a></li>
              </ul>
          </div>
      </nav>
  </div>
</header>

<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents d-flex justify-content-between align-items-center">
                    <h2 class="title-big">Reportajes</h2>
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

<div class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 pr-lg-5">
                
                <!-- Alerta de Vista Previa para Editores -->
                <?php if ($reportaje['estado'] !== 'Publicado'): ?>
                    <div class="badge-borrador">
                        <i class="fa fa-eye"></i> VISTA PREVIA - Este reportaje está en estado "<?= htmlspecialchars($reportaje['estado']) ?>" y no es visible para el público.
                    </div>
                <?php endif; ?>

                <h1 class="font-weight-bold mb-4" style="font-family: 'Cabin', sans-serif; color: #222; font-size: 2rem; line-height: 1.3;">
                    <?= htmlspecialchars($reportaje['titulo']) ?>
                </h1>
                
                <p class="text-muted mb-4 pb-3 border-bottom" style="font-family: 'Cabin', sans-serif;">
                    Por: <b><?= htmlspecialchars($reportaje['nombre_autor']) ?></b> | <?= fechaEs($reportaje['fecha_publicacion']) ?>
                </p>

                <?php if ($reportaje['foto_principal']): ?>
                    <?php 
                      $fotoSrc = file_exists('revista-admin/uploads/' . $reportaje['foto_principal']) 
                        ? 'revista-admin/uploads/' . $reportaje['foto_principal'] 
                        : 'assets/images/' . $reportaje['foto_principal'];

                      $hasPdf = !empty($reportaje['pdf_adjunto']);
                      $pdfUrl = $hasPdf ? 'revista-admin/uploads/' . $reportaje['pdf_adjunto'] : '#';
                    ?>

                    <?php if ($hasPdf): ?>
                        <a href="<?= $pdfUrl ?>" target="_blank" title="Haz clic para ver el PDF completo">
                            <img src="<?= $fotoSrc ?>" class="img-fluid mb-2 w-100 img-hover-zoom" style="object-fit:cover; border-radius:10px; cursor:pointer;" alt="<?= htmlspecialchars($reportaje['titulo']) ?>">
                        </a>
                        <small class="text-danger d-block text-center font-weight-bold" style="margin-bottom: 2px;">
                            <a href="<?= $pdfUrl ?>" target="_blank" class="text-danger text-decoration-none" style="font-family:'Cabin', sans-serif;">
                                Clic en la imagen para ver la infografía completa
                            </a>
                        </small>
                    <?php else: ?>
                        <img src="<?= $fotoSrc ?>" class="img-fluid mb-2 w-100" style="object-fit:cover; border-radius:10px;" alt="<?= htmlspecialchars($reportaje['titulo']) ?>">
                    <?php endif; ?>
                    
                    <!-- CRÉDITOS DE LA IMAGEN (Fase 2) -->
                    <?php if (!empty($reportaje['fuente_imagen'])): ?>
                        <p class="leyenda-imagen text-muted" style="margin-top: 5px; margin-bottom: 2rem;">
                            <?= htmlspecialchars($reportaje['fuente_imagen']) ?>
                        </p>
                    <?php else: ?>
                        <p style="margin-bottom: 2rem;"></p>
                    <?php endif; ?>
                    
                <?php endif; ?>
                
                <?php if ($reportaje['resumen_corto']): ?>
                    <div class="resumen-destacado"><?= $reportaje['resumen_corto'] ?></div>
                <?php endif; ?>

                <div class="content-body text-justify">
                    <?= $reportaje['desarrollo'] ?>
                </div>

                <!-- Botones para Compartir en Redes Sociales (SEO) -->
                <div class="mt-5 pt-3 border-top">
                    <b style="font-family:'Cabin', sans-serif; font-size:1.1rem;">Compartir:</b>
                    <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($url_actual) ?>" class="btn btn-sm text-white" style="background-color: #3b5998; border-radius: 5px; margin-left:10px;">
                        <i class="fa fa-facebook"></i> Facebook
                    </a>
                    <a target="_blank" href="https://api.whatsapp.com/send?text=<?= urlencode($reportaje['titulo'] . ' - ' . $url_actual) ?>" class="btn btn-sm text-white" style="background-color: #25D366; border-radius: 5px; margin-left:5px;">
                        <i class="fa fa-whatsapp"></i> WhatsApp
                    </a>
                    <a target="_blank" href="https://twitter.com/intent/tweet?text=<?= urlencode($reportaje['titulo']) ?>&url=<?= urlencode($url_actual) ?>" class="btn btn-sm text-white" style="background-color: #1DA1F2; border-radius: 5px; margin-left:5px;">
                        <i class="fa fa-twitter"></i> Twitter
                    </a>
                </div>

                <a href="reportajes.php" class="back-link"><span class="fa fa-arrow-left mr-2"></span> Volver a Reportajes</a>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4 mt-5 mt-lg-0 pl-lg-4">
                <h3 class="sidebar-title">Últimas noticias</h3>
                <ul class="sidebar-list">
                    <?php foreach ($ultimasNoticias as $un): ?>
                    <?php 
                        // Generar el enlace usando el Slug si existe, si no, el ID (retrocompatibilidad)
                        $enlace = !empty($un['slug']) ? 'reportaje.php?slug=' . htmlspecialchars($un['slug']) : 'reportaje.php?id=' . $un['id'];
                    ?>
                    <li>
                        <a href="<?= $enlace ?>"><?= htmlspecialchars($un['titulo']) ?></a>
                        <span class="date"><?= fechaEs($un['fecha_publicacion']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <h3 class="sidebar-title mt-5">Archivos</h3>
                <ul class="archivo-list">
                    <?php foreach ($archivos as $arc): ?>
                    <?php 
                        $tsArc = strtotime($arc['fecha_publicacion']);
                        $mesesNombres = ['01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio', '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'];
                        $nombreMes = $mesesNombres[date('m', $tsArc)] . ' ' . date('Y', $tsArc);
                    ?>
                    <li><a href="reportajes.php"><?= $nombreMes ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery-3.3.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>