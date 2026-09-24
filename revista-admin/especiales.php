<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$es_admin_o_editor = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor');

// Crear Especial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $titulo = trim($_POST['titulo']);
    $url    = trim($_POST['url_embed']);
    $fecha  = $_POST['fecha_publicacion'];
    $usuario_id = $_SESSION['user_id'];
    $foto_nombre = ''; // CORRECCIÓN: Inicializar vacío en vez de null

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nombre = 'especial_' . time() . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nombre);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO videos (titulo, foto, url_embed, fecha_publicacion, usuario_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $foto_nombre, $url, $fecha, $usuario_id]);
        header("Location: especiales.php"); 
        exit;
    } catch (\PDOException $e) {
        die("<div style='background:#ffdddd; color:#d8000c; padding:20px; border: 1px solid #d8000c;'><strong>¡Error al guardar el especial!</strong><br><br>Detalle: " . $e->getMessage() . "</div>");
    }
}

// Editar Especial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $sql = "UPDATE videos SET titulo = ?, url_embed = ?, fecha_publicacion = ?";
    $params = [trim($_POST['titulo']), trim($_POST['url_embed']), $_POST['fecha_publicacion']];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nombre = 'especial_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nombre);
        $sql .= ", foto = ?"; $params[] = $foto_nombre;
    }

    $sql .= " WHERE id = ?"; $params[] = $id;
    if (!$es_admin_o_editor) {
        $sql .= " AND usuario_id = ?"; $params[] = $_SESSION['user_id'];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header("Location: especiales.php"); 
        exit;
    } catch (\PDOException $e) {
        die("<div style='background:#ffdddd; color:#d8000c; padding:20px; border: 1px solid #d8000c;'><strong>¡Error al actualizar el especial!</strong><br><br>Detalle: " . $e->getMessage() . "</div>");
    }
}

// Eliminar Especial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    try {
        if ($es_admin_o_editor) {
            $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$id]);
        } else {
            $pdo->prepare("DELETE FROM videos WHERE id = ? AND usuario_id = ?")->execute([$id, $_SESSION['user_id']]);
        }
        header("Location: especiales.php"); 
        exit;
    } catch (\PDOException $e) {
        die("<div style='background:#ffdddd; color:#d8000c; padding:20px; border: 1px solid #d8000c;'><strong>¡Error al eliminar el especial!</strong><br><br>Detalle: " . $e->getMessage() . "</div>");
    }
}

$especiales = $pdo->query("SELECT * FROM videos ORDER BY fecha_publicacion DESC")->fetchAll();
$pageTitle = "Especiales / Videos";
require_once 'includes/header.php'; require_once 'includes/sidebar.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between">
      <h3 class="mb-0 text-dark"><i class="bi bi-play-circle-fill me-2"></i>Especiales (Videos)</h3>
      <button class="btn btn-danger text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="bi bi-plus-circle me-1"></i> Nuevo Especial</button>
    </div>
  </div>
  <div class="app-content"><div class="container-fluid"><div class="card shadow-sm border-0"><div class="card-body p-0">
    <table class="table table-hover align-middle mb-0"><thead class="table-light">
      <tr><th>Imagen</th><th>Título</th><th>Enlace / URL</th><th>Fecha</th><th class="text-end pe-3">Acciones</th></tr>
    </thead><tbody>
      <?php foreach ($especiales as $esp): ?>
      <tr>
        <td><?php if(!empty($esp['foto'])): ?><img src="uploads/<?= $esp['foto'] ?>" class="rounded" style="height:40px; width:60px; object-fit:cover;"><?php endif; ?></td>
        <td><b class="text-dark"><?= htmlspecialchars($esp['titulo']) ?></b></td>
        <td><a href="<?= htmlspecialchars($esp['url_embed']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width:200px;"><?= htmlspecialchars($esp['url_embed']) ?></a></td>
        <td class="text-secondary"><?= $esp['fecha_publicacion'] ?></td>
        <td class="text-end pe-3 text-nowrap">
          <?php if ($es_admin_o_editor || $esp['usuario_id'] == $_SESSION['user_id']): ?>
            <div class="d-inline-flex gap-1 align-items-center">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditar" data-id="<?= $esp['id'] ?>" data-titulo="<?= htmlspecialchars($esp['titulo']) ?>" data-url="<?= htmlspecialchars($esp['url_embed']) ?>" data-fecha="<?= $esp['fecha_publicacion'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" onsubmit="return confirm('¿Eliminar especial?');" class="m-0">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $esp['id'] ?>">
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

<div class="modal fade" id="modalNuevo" tabindex="-1"><div class="modal-dialog"><form method="post" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
  <input type="hidden" name="action" value="create">
  <div class="modal-header bg-danger text-white border-0"><h5 class="modal-title fw-bold">Nuevo Especial</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body bg-light">
    <div class="mb-3"><label class="form-label">Título *</label><input type="text" name="titulo" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Imagen *</label><input type="file" name="foto" class="form-control" accept="image/*" required></div>
    <div class="mb-3"><label class="form-label">Enlace / URL *</label><input type="url" name="url_embed" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Fecha *</label><input type="date" name="fecha_publicacion" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
  </div>
  <div class="modal-footer"><button type="submit" class="btn btn-danger shadow">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditar" tabindex="-1"><div class="modal-dialog"><form method="post" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
  <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
  <div class="modal-header bg-primary text-white border-0"><h5 class="modal-title fw-bold">Editar Especial</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body bg-light">
    <div class="mb-3"><label class="form-label">Título *</label><input type="text" name="titulo" id="edit_titulo" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Nueva Imagen (Opcional)</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
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