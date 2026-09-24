<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$es_admin_o_editor = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor');

$assetsImagesDir = '../assets/images/';
$existingImages = [];
if (is_dir($assetsImagesDir)) {
    $files = scandir($assetsImagesDir);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $existingImages[] = $file;
        }
    }
}

// Función para crear URLs amigables (Slugs)
function crearSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $string = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
        $string
    );
    $string = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
        $string
    );
    $string = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
        $string
    );
    $string = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
        $string
    );
    $string = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
        $string
    );
    $string = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç'),
        array('n', 'N', 'c', 'C'),
        $string
    );
    $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// --- PROCESAR CREACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $titulo       = trim($_POST['titulo']);
    $slug         = crearSlug($titulo) . '-' . uniqid(); // Para asegurar que sea único
    $resumen      = trim($_POST['resumen_corto']);
    $desarrollo   = trim($_POST['desarrollo']);
    $fecha        = $_POST['fecha_publicacion'];
    $destacado    = isset($_POST['es_destacado']) ? 1 : 0;
    $estado       = $_POST['estado'] ?? 'Borrador';
    $fuente_img   = trim($_POST['fuente_imagen']);
    $autor_id     = !empty($_POST['autor_id']) ? (int)$_POST['autor_id'] : null;
    $usuario_id   = $_SESSION['user_id'];

    // CORRECCIÓN: Iniciar con texto vacío en lugar de null para evitar bloqueos estrictos de MySQL
    $foto_nombre = '';
    $pdf_nombre  = '';

    if (isset($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_principal']['name'], PATHINFO_EXTENSION);
        $foto_nombre = 'rep_' . time() . '_' . uniqid() . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['foto_principal']['tmp_name'], 'uploads/' . $foto_nombre);
    } elseif (!empty($_POST['foto_existente'])) {
        $foto_nombre = trim($_POST['foto_existente']);
    }

    if (isset($_FILES['pdf_adjunto']) && $_FILES['pdf_adjunto']['error'] === UPLOAD_ERR_OK) {
        $pdf_nombre = 'rep_pdf_' . time() . '_' . uniqid() . '.pdf';
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['pdf_adjunto']['tmp_name'], 'uploads/' . $pdf_nombre);
    }

    // CORRECCIÓN: Envolver en un try-catch para capturar cualquier error futuro de la base de datos
    try {
        $stmt = $pdo->prepare("INSERT INTO reportajes (titulo, slug, resumen_corto, desarrollo, foto_principal, fuente_imagen, pdf_adjunto, fecha_publicacion, es_destacado, estado, autor_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $slug, $resumen, $desarrollo, $foto_nombre, $fuente_img, $pdf_nombre, $fecha, $destacado, $estado, $autor_id, $usuario_id]);
        header("Location: reportajes.php");
        exit;
    } catch (\PDOException $e) {
        // En caso de fallar, en lugar de recargar silenciosamente, mostrará este error
        die("<div style='background:#ffdddd; color:#d8000c; padding:20px; font-family:sans-serif; border: 1px solid #d8000c;'>
                <strong>¡Ocurrió un error al intentar guardar el reportaje!</strong><br><br>
                Detalle técnico: " . $e->getMessage() . "
             </div>");
    }
}

// --- PROCESAR ELIMINACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $rep_id = (int)$_POST['id'];
    if ($rep_id > 0) {
        if ($es_admin_o_editor) {
            $stmt = $pdo->prepare("DELETE FROM reportajes WHERE id = ?");
            $stmt->execute([$rep_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM reportajes WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$rep_id, $_SESSION['user_id']]);
        }
    }
    header("Location: reportajes.php");
    exit;
}

$stmt = $pdo->query("SELECT r.*, CASE WHEN r.autor_id IS NULL THEN 'Administrador' WHEN a.es_nickname = 1 THEN a.nickname ELSE CONCAT(a.nombres, ' ', COALESCE(a.ap_paterno, '')) END AS nombre_autor FROM reportajes r LEFT JOIN autores a ON r.autor_id = a.id ORDER BY r.fecha_publicacion DESC, r.id DESC");
$reportajes = $stmt->fetchAll();
$autores = $pdo->query("SELECT id, nombres, ap_paterno, nickname, es_nickname FROM autores")->fetchAll();

$pageTitle = "Gestión de Reportajes";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<main class="app-main">
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
  
  <style>
    /* ========================================================= */
/* AJUSTES RESPONSIVES ADMINLTE Y SUMMERNOTE                 */
/* ========================================================= */

      @media (max-width: 768px) {
          /* Hacer que los modales aprovechen todo el ancho del celular */
          .modal-dialog {
              max-width: 95% !important;
              margin: 10px auto !important;
          }
          
          /* Asegurar que Summernote no se desborde del formulario */
          .note-editor.note-frame {
              width: 100% !important;
              min-width: 100% !important;
          }
          
          /* Botones de acción del panel apilables */
          .app-content-header .container-fluid {
              flex-direction: column !important;
              align-items: flex-start !important;
              gap: 15px;
          }
          .app-content-header .btn {
              width: 100%; /* Botón de 'Crear Nuevo' ancho completo en móvil */
          }
          
          /* Evitar que los botones de acción (Ver, Editar, Borrar) se rompan */
          .text-nowrap {
              white-space: normal !important;
          }
          .d-inline-flex {
              flex-wrap: wrap;
              justify-content: flex-end;
          }
      }
      @import url('https://fonts.googleapis.com/css?family=Cabin:400,500,600,700&subset=latin-ext,vietnamese');
      
      .note-editable {
          font-family: 'Cabin', sans-serif !important;
          font-size: 1.05rem !important;
          line-height: 1.8 !important;
          color: #555 !important;
          background-color: #fff;
      }
      .note-editable h1, .note-editable h2, .note-editable h3, .note-editable h4 { color: #000 !important; font-weight: 700 !important; }
      .note-editable img { border-radius: 10px !important; max-width: 100% !important; height: auto !important; display: block !important; margin: 2rem auto 0.5rem auto !important; }
      .note-editable .leyenda-imagen { font-style: italic !important; color: #000 !important; text-align: center !important; font-size: 0.95rem !important; line-height: 1.6 !important; margin: 0.5rem 0 2rem 0 !important; }
      
      .btn-leyenda-custom {
          background-color: #e9ecef !important;
          color: #0d6efd !important;
          font-weight: 600 !important;
          border-radius: 5px !important;
          padding: 4px 8px !important;
          border: 1px solid #dee2e6 !important;
      }
      .btn-leyenda-custom:hover { background-color: #0d6efd !important; color: #fff !important; }
  </style>

  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0 text-dark"><i class="bi bi-journal-text me-2"></i>Gestión de Reportajes</h3>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-circle me-1"></i> Redactar Nuevo
      </button>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 60px;" class="text-center">ID</th>
                  <th style="width: 90px;">Portada</th>
                  <th>Título del Reportaje</th>
                  <th>Estado</th>
                  <th>Autor</th>
                  <th>Fecha Pub.</th>
                  <th class="text-center">Destacado</th>
                  <th style="width: 130px;" class="text-end pe-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reportajes as $rep): ?>
                <tr>
                  <td class="text-center text-muted">#<?= $rep['id'] ?></td>
                  <td>
                    <?php if (!empty($rep['foto_principal'])): ?>
                      <?php $fotoSrc = file_exists('uploads/' . $rep['foto_principal']) ? 'uploads/' . $rep['foto_principal'] : '../assets/images/' . $rep['foto_principal']; ?>
                      <img src="<?= $fotoSrc ?>" class="img-thumbnail rounded" style="height: 50px; width: 70px; object-fit: cover;">
                    <?php else: ?>
                      <span class="badge bg-light text-dark border">Sin foto</span>
                    <?php endif; ?>
                  </td>
                  <td><b class="text-dark"><?= htmlspecialchars($rep['titulo']) ?></b></td>
                  <td>
                    <?php if ($rep['estado'] == 'Publicado'): ?>
                        <span class="badge bg-success">Publicado</span>
                    <?php elseif ($rep['estado'] == 'Borrador'): ?>
                        <span class="badge bg-secondary">Borrador</span>
                    <?php else: ?>
                        <span class="badge bg-dark">Oculto</span>
                    <?php endif; ?>
                  </td>
                  <td><?= ($rep['nombre_autor'] === 'Administrador') ? '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">Administrador</span>' : htmlspecialchars($rep['nombre_autor']) ?></td>
                  <td class="text-secondary"><i class="bi bi-calendar3 me-1 small"></i> <?= $rep['fecha_publicacion'] ?></td>
                  <td class="text-center">
                    <?php if($rep['es_destacado']): ?>
                      <i class="bi bi-star-fill text-warning fs-5" title="Destacado en Portada"></i>
                    <?php else: ?>
                      <i class="bi bi-star text-muted opacity-50"></i>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-3 text-nowrap">
                    <?php 
                    $puede_modificar = ($es_admin_o_editor || $rep['usuario_id'] == $_SESSION['user_id']);
                    if ($puede_modificar): 
                    ?>
                      <div class="d-inline-flex gap-1 align-items-center">
                        <a href="../reportaje.php?slug=<?= $rep['slug'] ?? '' ?>&id=<?= $rep['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Vista Previa">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="reportaje_editar.php?id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar este reportaje permanentemente?');" class="m-0">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">Solo lectura</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<div class="modal fade" id="modalNuevo" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form method="post" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
      <input type="hidden" name="action" value="create">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2"></i>Redactar Nuevo Reportaje</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-info-circle me-1"></i> Información General</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título del Reportaje <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control form-control-lg" placeholder="Ej: La minería ilegal avanza en el norte..." required>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Autor del Reportaje</label>
                        <select name="autor_id" class="form-select">
                        <option value="">-- Redacción DDP --</option>
                        <?php foreach ($autores as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= $a['es_nickname'] ? $a['nickname'] : $a['nombres'] . ' ' . $a['ap_paterno'] ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Fecha de Publicación <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_publicacion" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold text-primary">Estado <span class="text-danger">*</span></label>
                        <select name="estado" class="form-select border-primary">
                            <option value="Borrador">Guardar como Borrador</option>
                            <option value="Publicado">Publicar inmediatamente</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="es_destacado" id="destacadoCheck" value="1">
                    <label class="form-check-label fw-bold text-dark" for="destacadoCheck">Marcar como Reportaje Destacado (Aparecerá grande en la portada)</label>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-images me-1"></i> Archivos y Portada</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Subir Foto Principal (Desde PC)</label>
                        <input type="file" name="foto_principal" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-muted">O seleccionar imagen del servidor</label>
                        <select name="foto_existente" class="form-select">
                        <option value="">-- No seleccionar nada --</option>
                        <?php foreach ($existingImages as $img): ?>
                            <option value="<?= htmlspecialchars($img) ?>"><?= htmlspecialchars($img) ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Créditos de la Fotografía <small class="text-muted">(Opcional, pero recomendado)</small></label>
                    <input type="text" name="fuente_imagen" class="form-control" placeholder="Ej: Foto: Agencia Andina / Juan Pérez">
                </div>
                <hr class="text-muted">
                <div class="mb-2">
                    <label class="form-label fw-semibold">PDF Adjunto <small class="text-muted fw-normal">(Opcional. Si lo subes, la foto principal abrirá el PDF al hacerle clic)</small></label>
                    <input type="file" name="pdf_adjunto" class="form-control" accept="application/pdf">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-file-earmark-text me-1"></i> Redacción del Contenido</div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Resumen Corto (Destacado)</label>
                    <textarea name="resumen_corto" id="summernote_resumen" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Cuerpo del Reportaje <span class="text-danger">*</span></label>
                    <textarea name="desarrollo" id="summernote_desarrollo" class="form-control" rows="6" required></textarea>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancelar</button>
        <button type="submit" class="btn btn-primary shadow"><i class="bi bi-save me-1"></i> Guardar Reportaje</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
  $(document).ready(function() {
      
      var BotonPieDeFoto = function (context) {
        var ui = $.summernote.ui;
        var button = ui.button({
          contents: '<span class="btn-leyenda-custom"><i class="bi bi-text-center"></i> Formato Pie de Foto</span>',
          tooltip: 'Selecciona un texto e insértalo como Pie de Foto',
          click: function () {
            var selectedText = context.invoke('editor.getSelectedText');
            if (selectedText === '') selectedText = 'Escribe aquí el pie de foto...';
            var node = $('<div class="leyenda-imagen">' + selectedText + '</div>')[0];
            context.invoke('editor.insertNode', node);
            context.invoke('editor.insertParagraph');
          }
        });
        return button.render();
      }

      $('#summernote_resumen').summernote({
          placeholder: 'Escriba un breve extracto...',
          tabsize: 2, height: 110,
          toolbar: [
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['view', ['codeview']]
          ]
      });

      $('#summernote_desarrollo').summernote({
          placeholder: 'Escriba todo el desarrollo del reportaje aquí...',
          tabsize: 2, height: 450,
          toolbar: [
            ['style', ['style']], 
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['custom', ['pieDeFotoBtn']],
            ['insert', ['picture', 'link', 'video', 'table']],
            ['view', ['fullscreen', 'codeview']]
          ],
          buttons: {
            pieDeFotoBtn: BotonPieDeFoto
          }
      });
  });
</script>
<?php require_once 'includes/footer.php'; ?>  