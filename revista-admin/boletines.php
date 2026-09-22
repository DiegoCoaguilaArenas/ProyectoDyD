<?php
$pageTitle = "Boletines PDF";
require_once 'config/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$es_admin_o_editor = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor');

// Crear Boletín
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $num_boletin = trim($_POST['numero_boletin']);
    $resumen     = trim($_POST['resumen']);
    $fecha       = $_POST['fecha_publicacion'];
    $usuario_id  = $_SESSION['user_id'];
    $pdf_nombre  = ''; $portada = null;

    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_nombre = 'boletin_' . time() . '_' . uniqid() . '.pdf';
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], 'uploads/' . $pdf_nombre);
    }
    if (isset($_FILES['foto_portada']) && $_FILES['foto_portada']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_portada']['name'], PATHINFO_EXTENSION);
        $portada = 'portada_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto_portada']['tmp_name'], 'uploads/' . $portada);
    }

    if ($pdf_nombre !== '') {
        $stmt = $pdo->prepare("INSERT INTO boletines (numero_boletin, resumen, foto_portada, archivo_pdf, fecha_publicacion, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$num_boletin, $resumen, $portada, $pdf_nombre, $fecha, $usuario_id]);
    }
    header("Location: boletines.php"); exit;
}

// Editar Boletín
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $sql = "UPDATE boletines SET numero_boletin = ?, resumen = ?, fecha_publicacion = ?";
    $params = [trim($_POST['numero_boletin']), trim($_POST['resumen']), $_POST['fecha_publicacion']];

    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_nombre = 'boletin_' . time() . '_' . uniqid() . '.pdf';
        move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], 'uploads/' . $pdf_nombre);
        $sql .= ", archivo_pdf = ?"; $params[] = $pdf_nombre;
    }
    if (isset($_FILES['foto_portada']) && $_FILES['foto_portada']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_portada']['name'], PATHINFO_EXTENSION);
        $portada = 'portada_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto_portada']['tmp_name'], 'uploads/' . $portada);
        $sql .= ", foto_portada = ?"; $params[] = $portada;
    }

    $sql .= " WHERE id = ?"; $params[] = $id;
    if (!$es_admin_o_editor) {
        $sql .= " AND usuario_id = ?"; $params[] = $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    header("Location: boletines.php"); exit;
}

// Eliminar Boletín
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($es_admin_o_editor) {
        $pdo->prepare("DELETE FROM boletines WHERE id = ?")->execute([$id]);
    } else {
        $pdo->prepare("DELETE FROM boletines WHERE id = ? AND usuario_id = ?")->execute([$id, $_SESSION['user_id']]);
    }
    header("Location: boletines.php"); exit;
}

$boletines = $pdo->query("SELECT * FROM boletines ORDER BY fecha_publicacion DESC")->fetchAll();
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3>Boletines Informativos</h3>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBoletin"><i class="bi bi-file-earmark-plus"></i> Nuevo Boletín</button>
    </div>
  </div>
  <div class="app-content"><div class="container-fluid"><div class="card"><div class="card-body p-0">
    <table class="table table-striped align-middle mb-0">
      <thead><tr><th>N° Boletín</th><th>Resumen</th><th>Fecha Pub.</th><th>PDF</th><th class="text-end pe-3">Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($boletines as $b): ?>
        <tr>
          <td><b><?= htmlspecialchars($b['numero_boletin']) ?></b></td>
          <td><?= htmlspecialchars($b['resumen'] ?? '-') ?></td>
          <td><?= $b['fecha_publicacion'] ?></td>
          <td><a href="uploads/<?= $b['archivo_pdf'] ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a></td>
          <td class="text-end pe-3 text-nowrap">
            <?php if ($es_admin_o_editor || $b['usuario_id'] == $_SESSION['user_id']): ?>
              <div class="d-inline-flex gap-1 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditarBoletin" data-id="<?= $b['id'] ?>" data-numero="<?= htmlspecialchars($b['numero_boletin']) ?>" data-resumen="<?= htmlspecialchars($b['resumen'] ?? '') ?>" data-fecha="<?= $b['fecha_publicacion'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" onsubmit="return confirm('¿Eliminar este boletín?');" class="m-0">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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
  </div></div></div></div>
</main>

<!-- Modales Create / Edit -->
<div class="modal fade" id="modalBoletin" tabindex="-1"><div class="modal-dialog"><form method="post" enctype="multipart/form-data" class="modal-content">
  <input type="hidden" name="action" value="create">
  <div class="modal-header"><h5 class="modal-title">Subir Boletín</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Número *</label><input type="text" name="numero_boletin" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Fecha *</label><input type="date" name="fecha_publicacion" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
    <div class="mb-3"><label class="form-label">Resumen</label><textarea name="resumen" class="form-control" rows="2"></textarea></div>
    <div class="mb-3"><label class="form-label">PDF *</label><input type="file" name="archivo_pdf" class="form-control" accept="application/pdf" required></div>
    <div class="mb-3"><label class="form-label">Portada (Opcional)</label><input type="file" name="foto_portada" class="form-control" accept="image/*"></div>
  </div>
  <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditarBoletin" tabindex="-1"><div class="modal-dialog"><form method="post" enctype="multipart/form-data" class="modal-content">
  <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
  <div class="modal-header bg-primary text-white"><h5 class="modal-title fw-bold">Editar Boletín</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body bg-light">
    <div class="mb-3"><label class="form-label">Número *</label><input type="text" name="numero_boletin" id="edit_numero" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Fecha *</label><input type="date" name="fecha_publicacion" id="edit_fecha" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Resumen</label><textarea name="resumen" id="edit_resumen" class="form-control" rows="2"></textarea></div>
    <div class="mb-3"><label class="form-label">Nuevo PDF (Opcional)</label><input type="file" name="archivo_pdf" class="form-control" accept="application/pdf"></div>
    <div class="mb-3"><label class="form-label">Nueva Portada (Opcional)</label><input type="file" name="foto_portada" class="form-control" accept="image/*"></div>
  </div>
  <div class="modal-footer"><button type="submit" class="btn btn-primary">Actualizar</button></div>
</form></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_numero').value = this.getAttribute('data-numero');
            document.getElementById('edit_resumen').value = this.getAttribute('data-resumen');
            document.getElementById('edit_fecha').value = this.getAttribute('data-fecha');
        });
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>