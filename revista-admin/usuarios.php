<?php
$pageTitle = "Gestión de Usuarios";
require_once 'config/db.php';
require_once 'includes/header.php';

// Restringir acceso solo a Admin
if ($_SESSION['rol'] !== 'admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Acceso restringido a Administradores.</div></div>";
    require_once 'includes/footer.php';
    exit;
}

require_once 'includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $nombres    = trim($_POST['nombres']);
    $ap_paterno = trim($_POST['ap_paterno']);
    $ap_materno = trim($_POST['ap_materno']);
    $email      = trim($_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $rol        = $_POST['rol'];

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombres, ap_paterno, ap_materno, email, password_hash, rol) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombres, $ap_paterno, $ap_materno, $email, $password, $rol]);
    header("Location: usuarios.php");
    exit;
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3>Usuarios del Sistema</h3>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUser"><i class="bi bi-person-plus-fill"></i> Crear Usuario</button>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Registro</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usuarios as $u): ?>
              <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['nombres'] . ' ' . $u['ap_paterno']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge text-bg-dark"><?= strtoupper($u['rol']) ?></span></td>
                <td><?= $u['created_at'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<div class="modal fade" id="modalUser" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombres *</label>
          <input type="text" name="nombres" class="form-control" required>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Apellido Paterno *</label>
            <input type="text" name="ap_paterno" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Apellido Materno</label>
            <input type="text" name="ap_materno" class="form-control">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Correo Electrónico *</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña *</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Rol del Sistema *</label>
          <select name="rol" class="form-select" required>
            <option value="redactor">Redactor</option>
            <option value="editor">Editor</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Registrar</button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>