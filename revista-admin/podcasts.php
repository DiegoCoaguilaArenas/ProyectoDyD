<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$es_admin_o_editor = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor');

// Crear
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $stmt = $pdo->prepare("INSERT INTO podcasts (titulo, url_embed, fecha_publicacion, usuario_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([trim($_POST['titulo']), trim($_POST['url_embed']), $_POST['fecha_publicacion'], $_SESSION['user_id']]);
    header("Location: podcasts.php"); exit;
}

// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if ($es_admin_o_editor) {
        $stmt = $pdo->prepare("UPDATE podcasts SET titulo = ?, url_embed = ?, fecha_publicacion = ? WHERE id = ?");
        $stmt->execute([trim($_POST['titulo']), trim($_POST['url_embed']), $_POST['fecha_publicacion'], (int)$_POST['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE podcasts SET titulo = ?, url_embed = ?, fecha_publicacion = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([trim($_POST['titulo']), trim($_POST['url_embed']), $_POST['fecha_publicacion'], (int)$_POST['id'], $_SESSION['user_id']]);
    }
    header("Location: podcasts.php"); exit;
}

// Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if ($es_admin_o_editor) {
        $pdo->prepare("DELETE FROM podcasts WHERE id = ?")->execute([(int)$_POST['id']]);
    } else {
        $pdo->prepare("DELETE FROM podcasts WHERE id = ? AND usuario_id = ?")->execute([(int)$_POST['id'], $_SESSION['user_id']]);
    }
    header("Location: podcasts.php"); exit;
}

$podcasts = $pdo->query("SELECT * FROM podcasts ORDER BY fecha_publicacion DESC")->fetchAll();
$pageTitle = "Podcasts";
require_once 'includes/header.php'; require_once 'includes/sidebar.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between">
      <h3 class="mb-0 text-dark"><i class="bi bi-mic-fill me-2"></i>Gestión de Podcasts</h3>
      <button class="btn btn-warning shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-circle me-1"></i> Nuevo Podcast</button>
    </div>
  </div>
  <div class="app-content"><div class="container-fluid"><div class="card shadow-sm border-0"><div class="card-body p-0">
    <table class="table table-hover align-middle mb-0"><thead class="table-light">
      <tr><th>Título</th><th>Enlace / URL</th><th>Fecha</th><th class="text-end pe-3">Acciones</th></tr>
    </thead><tbody>
      <?php foreach ($podcasts as $pod): ?>
      <tr>
        <td><b class="text-dark"><?= htmlspecialchars($pod['titulo']) ?></b></td>
        <td><a href="<?= htmlspecialchars($pod['url_embed']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width:250px;"><?= htmlspecialchars($pod['url_embed']) ?></a></td>
        <td class="text-secondary"><?= $pod['fecha_publicacion'] ?></td>
        <td class="text-end pe-3 text-nowrap">
          <?php if ($es_admin_o_editor || $pod['usuario_id'] == $_SESSION['user_id']): ?>
            <div class="d-inline-flex gap-1 align-items-center">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditar" data-id="<?= $pod['id'] ?>" data-titulo="<?= htmlspecialchars($pod['titulo']) ?>" data-url="<?= htmlspecialchars($pod['url_embed']) ?>" data-fecha="<?= $pod['fecha_publicacion'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" onsubmit="return confirm('¿Eliminar podcast?');" class="m-0">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $pod['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          <?php else: ?>
            <span class="badge bg-light text-muted border">Solo lectura</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
  </div></div></div></div>
</main>

<div class="modal fade" id="modalNuevo" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content border-0 shadow-lg">
  <input type="hidden" name="action" value="create">
  <div class="modal-header bg-warning border-0"><h5 class="modal-title fw-bold">Nuevo Podcast</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body bg-light">
    <div class="mb-3"><label class="form-label">Título *</label><input type="text" name="titulo" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Enlace / URL *</label><input type="url" name="url_embed" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Fecha *</label><input type="date" name="fecha_publicacion" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
  </div>
  <div class="modal-footer"><button type="submit" class="btn btn-warning fw-bold">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditar" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content border-0 shadow-lg">
  <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
  <div class="modal-header bg-primary text-white border-0"><h5 class="modal-title fw-bold">Editar Podcast</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body bg-light">
    <div class="mb-3"><label class="form-label">Título *</label><input type="text" name="titulo" id="edit_titulo" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Enlace / URL *</label><input type="url" name="url_embed" id="edit_url" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Fecha *</label><input type="date" name="fecha_publicacion" id="edit_fecha" class="form-control" required></div>
  </div>
  <div class="modal-footer"><button type="submit" class="btn btn-primary text-white">Actualizar</button></div>
</form></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_titulo').value = this.getAttribute('data-titulo');
            document.getElementById('edit_url').value = this.getAttribute('data-url');
            document.getElementById('edit_fecha').value = this.getAttribute('data-fecha');
        });
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>  