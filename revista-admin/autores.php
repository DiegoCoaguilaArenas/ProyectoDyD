<?php
$pageTitle = "Gestión de Autores";
require_once 'config/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Crear Autor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $nombres     = trim($_POST['nombres']);
    $ap_paterno  = trim($_POST['ap_paterno']);
    $ap_materno  = trim($_POST['ap_materno']);
    $nickname    = trim($_POST['nickname']);
    $es_nickname = isset($_POST['es_nickname']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO autores (nombres, ap_paterno, ap_materno, nickname, es_nickname) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nombres, $ap_paterno, $ap_materno, $nickname, $es_nickname]);
    header("Location: autores.php");
    exit;
}

// Eliminar Autor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM autores WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: autores.php");
    exit;
}

$autores = $pdo->query("SELECT * FROM autores ORDER BY id DESC")->fetchAll();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3>Autores</h3>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAutor"><i class="bi bi-person-plus"></i> Nuevo Autor</button>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="card mb-4">
        <div class="card-body p-0">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Seudónimo/Nickname</th>
                <th>Mostrar Como</th>
                <th class="text-end pe-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($autores as $a): ?>
              <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['nombres'] . ' ' . $a['ap_paterno'] . ' ' . $a['ap_materno']) ?></td>
                <td><?= htmlspecialchars($a['nickname'] ?? '-') ?></td>
                <td>
                  <span class="badge <?= $a['es_nickname'] ? 'text-bg-info' : 'text-bg-secondary' ?>">
                    <?= $a['es_nickname'] ? 'Nickname' : 'Nombre Real' ?>
                  </span>
                </td>
                <td class="text-end pe-3 text-nowrap">
                  <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este autor?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modal Crear Autor -->
<div class="modal fade" id="modalAutor" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title">Registrar Autor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombres *</label>
          <input type="text" name="nombres" class="form-control" required>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Apellido Paterno</label>
            <input type="text" name="ap_paterno" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Apellido Materno</label>
            <input type="text" name="ap_materno" class="form-control">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Nickname / Seudónimo</label>
          <input type="text" name="nickname" class="form-control">
        </div>
        <div class="form-check">
          <input type="checkbox" name="es_nickname" class="form-check-input" id="es_nickname" value="1">
          <label class="form-check-label" for="es_nickname">Usar Nickname como firma pública</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>