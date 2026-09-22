<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Variable global de roles
$es_admin_o_editor = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM reportajes WHERE id = ?");
$stmt->execute([$id]);
$reportaje = $stmt->fetch();

// Redirigir si no existe
if (!$reportaje) {
    header("Location: reportajes.php");
    exit;
}

// --- PROTECCIÓN POR ROLES ---
// Si NO es admin/editor y tampoco es el creador de este reportaje, se le deniega el acceso
if (!$es_admin_o_editor && $reportaje['usuario_id'] != $_SESSION['user_id']) {
    header("Location: reportajes.php");
    exit;
}

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

// --- PROCESAR ACTUALIZACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $titulo       = trim($_POST['titulo']);
    $resumen      = trim($_POST['resumen_corto']);
    $desarrollo   = trim($_POST['desarrollo']);
    $fecha        = $_POST['fecha_publicacion'];
    $destacado    = isset($_POST['es_destacado']) ? 1 : 0;
    $autor_id     = !empty($_POST['autor_id']) ? (int)$_POST['autor_id'] : null;

    $foto_nombre = $reportaje['foto_principal'];
    $pdf_nombre  = $reportaje['pdf_adjunto'];

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

    // Construcción de consulta dinámica por seguridad
    $sqlUp = "UPDATE reportajes SET titulo = ?, resumen_corto = ?, desarrollo = ?, foto_principal = ?, pdf_adjunto = ?, fecha_publicacion = ?, es_destacado = ?, autor_id = ? WHERE id = ?";
    $paramsUp = [$titulo, $resumen, $desarrollo, $foto_nombre, $pdf_nombre, $fecha, $destacado, $autor_id, $id];

    // Si es un redactor, forzamos que solo modifique si es su propio usuario_id (Doble seguridad)
    if (!$es_admin_o_editor) {
        $sqlUp .= " AND usuario_id = ?";
        $paramsUp[] = $_SESSION['user_id'];
    }

    $stmtUp = $pdo->prepare($sqlUp);
    $stmtUp->execute($paramsUp);
    
    header("Location: reportajes.php");
    exit;
}

$autores = $pdo->query("SELECT id, nombres, ap_paterno, nickname, es_nickname FROM autores")->fetchAll();

$pageTitle = "Editar Reportaje";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<main class="app-main">
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
  
  <style>
      @import url('https://fonts.googleapis.com/css?family=Cabin:400,500,600,700&subset=latin-ext,vietnamese');
      .note-editable { font-family: 'Cabin', sans-serif !important; font-size: 1.05rem !important; line-height: 1.8 !important; color: #555 !important; background-color: #fff; }
      .note-editable h1, .note-editable h2, .note-editable h3, .note-editable h4 { color: #000 !important; font-weight: 700 !important; }
      .note-editable img { border-radius: 10px !important; max-width: 100% !important; height: auto !important; display: block !important; margin: 2rem auto 0.5rem auto !important; }
      .note-editable .leyenda-imagen { font-style: italic !important; color: #000 !important; text-align: center !important; font-size: 0.95rem !important; line-height: 1.6 !important; margin: 0.5rem 0 2rem 0 !important; }
      .btn-leyenda-custom { background-color: #e9ecef !important; color: #0d6efd !important; font-weight: 600 !important; border-radius: 5px !important; padding: 4px 8px !important; border: 1px solid #dee2e6 !important; }
      .btn-leyenda-custom:hover { background-color: #0d6efd !important; color: #fff !important; }
  </style>

  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0 text-dark"><i class="bi bi-pencil-square me-2"></i>Editar Reportaje #<?= $id ?></h3>
      <a href="reportajes.php" class="btn btn-outline-secondary shadow-sm"><i class="bi bi-arrow-left me-1"></i> Regresar a la lista</a>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-info-circle me-1"></i> Información General</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título del Reportaje <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control form-control-lg" value="<?= htmlspecialchars($reportaje['titulo']) ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Autor</label>
                        <select name="autor_id" class="form-select">
                        <option value="">-- Administrador (Por defecto) --</option>
                        <?php foreach ($autores as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= ($reportaje['autor_id'] == $a['id']) ? 'selected' : '' ?>>
                            <?= $a['es_nickname'] ? $a['nickname'] : $a['nombres'] . ' ' . $a['ap_paterno'] ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Fecha de Publicación <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_publicacion" class="form-control" value="<?= $reportaje['fecha_publicacion'] ?>" required>
                    </div>
                </div>
                
                <?php if ($es_admin_o_editor): ?>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="es_destacado" id="destacadoCheckEdit" value="1" <?= $reportaje['es_destacado'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-dark" for="destacadoCheckEdit">Marcar como Reportaje Destacado (Aparecerá en la portada)</label>
                </div>
                <?php else: ?>
                    <input type="hidden" name="es_destacado" value="<?= $reportaje['es_destacado'] ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-images me-1"></i> Archivos y Portada</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Reemplazar Foto Principal (Desde PC)</label>
                        <input type="file" name="foto_principal" class="form-control" accept="image/*">
                        <?php if ($reportaje['foto_principal']): ?>
                            <small class="text-success d-block mt-2"><i class="bi bi-check-circle-fill"></i> Foto actual: <?= $reportaje['foto_principal'] ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-muted">O seleccionar imagen guardada en el servidor</label>
                        <select name="foto_existente" class="form-select">
                        <option value="">-- Mantener imagen actual --</option>
                        <?php foreach ($existingImages as $img): ?>
                            <option value="<?= htmlspecialchars($img) ?>" <?= ($reportaje['foto_principal'] === $img) ? 'selected' : '' ?>><?= htmlspecialchars($img) ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <hr class="text-muted">
                <div class="mb-2">
                    <label class="form-label fw-semibold">PDF Adjunto (Opcional)</label>
                    <input type="file" name="pdf_adjunto" class="form-control" accept="application/pdf">
                    <?php if ($reportaje['pdf_adjunto']): ?>
                        <small class="text-success d-block mt-2"><i class="bi bi-file-earmark-pdf-fill"></i> PDF actual: <?= $reportaje['pdf_adjunto'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-file-earmark-text me-1"></i> Redacción del Contenido</div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Resumen Corto (Destacado)</label>
                    <textarea name="resumen_corto" id="summernote_resumen" class="form-control" rows="2"><?= htmlspecialchars($reportaje['resumen_corto']) ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Cuerpo del Reportaje <span class="text-danger">*</span></label>
                    <textarea name="desarrollo" id="summernote_desarrollo" class="form-control" rows="6" required><?= htmlspecialchars($reportaje['desarrollo']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <a href="reportajes.php" class="btn btn-outline-secondary me-3 px-4"><i class="bi bi-x-lg me-1"></i> Cancelar</a>
            <button type="submit" class="btn btn-success shadow px-4"><i class="bi bi-save me-1"></i> Guardar Cambios</button>
        </div>

      </form>
    </div>
  </div>
</main>

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
          placeholder: 'Escriba todo el cuerpo aquí...',
          tabsize: 2, height: 500,
          toolbar: [
            ['style', ['style']], // <--- TITULOS (H1, H2, H3, H4, Párrafo)
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['color', ['color']], // <--- CAMBIO DE COLOR DE FUENTE Y FONDO
            ['para', ['ul', 'ol', 'paragraph']],
            ['custom', ['pieDeFotoBtn']], // BOTÓN PIE DE FOTO
            ['insert', ['picture', 'link', 'video', 'table']],
            ['view', ['fullscreen', 'codeview']]
          ],
          buttons: { pieDeFotoBtn: BotonPieDeFoto }
      });
  });
</script>
<?php require_once 'includes/footer.php'; ?>