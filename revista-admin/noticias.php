<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$es_admin_o_editor = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor');

// Función para crear URLs amigables (Slugs)
function crearSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $string = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $string);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    return trim(preg_replace('/-+/', '-', $string), '-');
}

// --- PROCESAR CREACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $titulo       = trim($_POST['titulo']);
    $slug         = crearSlug($titulo) . '-' . uniqid();
    $resumen      = trim($_POST['resumen_corto']);
    $desarrollo   = trim($_POST['desarrollo']);
    $link_externo = trim($_POST['link_externo']);
    $fecha        = $_POST['fecha_publicacion'];
    $estado       = $_POST['estado'] ?? 'Borrador';
    $fuente_img   = trim($_POST['fuente_imagen']);
    $usuario_id   = $_SESSION['user_id'];

    $foto_nombre = null;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nombre = 'noticia_' . time() . '_' . uniqid() . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nombre);
    }

    $stmt = $pdo->prepare("INSERT INTO noticias (titulo, slug, resumen_corto, desarrollo, foto, fuente_imagen, link_externo, fecha_publicacion, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $slug, $resumen, $desarrollo, $foto_nombre, $fuente_img, $link_externo, $fecha, $estado, $usuario_id]);
    header("Location: noticias.php");
    exit;
}

// --- PROCESAR ELIMINACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $noticia_id = (int)$_POST['id'];
    if ($noticia_id > 0) {
        if ($es_admin_o_editor) {
            $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
            $stmt->execute([$noticia_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$noticia_id, $_SESSION['user_id']]);
        }
    }
    header("Location: noticias.php");
    exit;
}

// Obtener noticias
$stmt = $pdo->query("SELECT n.*, CONCAT(u.nombres, ' ', COALESCE(u.ap_paterno, '')) AS nombre_autor FROM noticias n JOIN usuarios u ON n.usuario_id = u.id ORDER BY n.fecha_publicacion DESC, n.id DESC");
$noticias = $stmt->fetchAll();

$pageTitle = "Gestión de Noticias Rápidas";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<main class="app-main">
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
  
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0 text-dark"><i class="bi bi-newspaper me-2"></i>Gestión de Noticias</h3>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-circle me-1"></i> Redactar Noticia
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
                  <th style="width: 90px;">Foto</th>
                  <th>Título de la Noticia</th>
                  <th>Estado</th>
                  <th>Fecha Pub.</th>
                  <th style="width: 130px;" class="text-end pe-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($noticias as $not): ?>
                <tr>
                  <td class="text-center text-muted">#<?= $not['id'] ?></td>
                  <td>
                    <?php if (!empty($not['foto'])): ?>
                      <img src="uploads/<?= $not['foto'] ?>" class="img-thumbnail rounded" style="height: 50px; width: 70px; object-fit: cover;">
                    <?php else: ?>
                      <span class="badge bg-light text-dark border">Sin foto</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <b class="text-dark"><?= htmlspecialchars($not['titulo']) ?></b>
                    <?php if(!empty($not['link_externo'])): ?>
                        <br><small class="text-primary"><i class="bi bi-link-45deg"></i> Link externo activo</small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($not['estado'] == 'Publicado'): ?>
                        <span class="badge bg-success">Publicado</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Borrador</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-secondary"><i class="bi bi-calendar3 me-1 small"></i> <?= $not['fecha_publicacion'] ?></td>
                  <td class="text-end pe-3 text-nowrap">
                    <?php if ($es_admin_o_editor || $not['usuario_id'] == $_SESSION['user_id']): ?>
                      <div class="d-inline-flex gap-1 align-items-center">
                        <?php $url_preview = !empty($not['link_externo']) ? $not['link_externo'] : "../noticia.php?slug=" . ($not['slug'] ?? ''); ?>
                        <a href="<?= htmlspecialchars($url_preview) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Vista Previa">
                          <i class="bi bi-eye"></i>
                        </a>
                        <form method="post" onsubmit="return confirm('¿Eliminar esta noticia permanentemente?');" class="m-0">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= $not['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
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

<!-- Modal Redactar Noticia -->
<div class="modal fade" id="modalNuevo" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form method="post" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
      <input type="hidden" name="action" value="create">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Redactar Nueva Noticia</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título de la Noticia <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control form-control-lg" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Fecha de Publicación <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_publicacion" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-primary">Estado <span class="text-danger">*</span></label>
                        <select name="estado" class="form-select border-primary">
                            <option value="Borrador">Guardar como Borrador</option>
                            <option value="Publicado">Publicar inmediatamente</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold"><i class="bi bi-image me-1"></i> Multimedia</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Foto de la Noticia</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Créditos de la Fotografía</label>
                    <input type="text" name="fuente_imagen" class="form-control" placeholder="Ej: Foto: Reuters / Archivo">
                </div>
                <hr>
                <div class="mb-2">
                    <label class="form-label fw-semibold text-warning"><i class="bi bi-link-45deg"></i> Link Externo (Opcional)</label>
                    <small class="d-block text-muted mb-2">Si llenas esto, la noticia será solo un título que redireccionará a esta web. Ignora el "Cuerpo del artículo" de abajo.</small>
                    <input type="url" name="link_externo" class="form-control" placeholder="https://...">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-file-earmark-text me-1"></i> Redacción del Contenido</div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Resumen Corto</label>
                    <textarea name="resumen_corto" class="form-control" rows="2" placeholder="Un pequeño extracto..."></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Cuerpo del Artículo</label>
                    <textarea name="desarrollo" id="summernote_noticias" class="form-control"></textarea>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary shadow"><i class="bi bi-save me-1"></i> Guardar Noticia</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
  $(document).ready(function() {
      $('#summernote_noticias').summernote({
          placeholder: 'Redacta la noticia aquí...',
          tabsize: 2, height: 300,
          toolbar: [
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['picture', 'link', 'video']],
            ['view', ['fullscreen', 'codeview']]
          ]
      });
  });
</script>
<?php require_once 'includes/footer.php'; ?>