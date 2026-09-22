<?php
session_start();
require_once 'config/db.php';

$mensaje = '';
$tipo_alerta = '';
$token_valido = false;
$usuario_id = null;

// Validar el token de la URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Buscar usuario con ese token y que no haya expirado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires >= NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $token_valido = true;
        $usuario_id = $user['id'];
    } else {
        $mensaje = 'El enlace de recuperación es inválido o ha expirado. Por favor, solicita uno nuevo.';
        $tipo_alerta = 'alert-danger';
    }
} else {
    header("Location: login.php");
    exit;
}

// Procesar el cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirma = $_POST['password_confirma'] ?? '';

    if (strlen($password_nueva) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
        $tipo_alerta = 'alert-danger';
    } elseif ($password_nueva !== $password_confirma) {
        $mensaje = 'Las contraseñas no coinciden.';
        $tipo_alerta = 'alert-danger';
    } else {
        // Encriptar la nueva contraseña
        $hash_nuevo = password_hash($password_nueva, PASSWORD_DEFAULT);

        // Actualizar la contraseña en la base de datos y limpiar el token para que no se re-use
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmtUpdate->execute([$hash_nuevo, $usuario_id]);

        $mensaje = '¡Tu contraseña ha sido restablecida con éxito! Ya puedes iniciar sesión.';
        $tipo_alerta = 'alert-success';
        $token_valido = false; // Ocultar el formulario
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DDP ADMIN | Restablecer Contraseña</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <h1 class="h3 mb-0"><b>Revista</b>Digital</h1>
    </div>
    <div class="card-body login-card-body">
      
      <?php if ($mensaje): ?>
        <div class="alert <?= $tipo_alerta ?> py-2"><?= $mensaje ?></div>
      <?php endif; ?>

      <?php if ($token_valido): ?>
          <p class="login-box-msg">Escribe tu nueva contraseña.</p>
          <form action="" method="post">
            <div class="input-group mb-3">
              <input type="password" name="password_nueva" class="form-control" placeholder="Nueva contraseña" required minlength="6">
              <div class="input-group-text"><span class="bi bi-lock"></span></div>
            </div>
            <div class="input-group mb-3">
              <input type="password" name="password_confirma" class="form-control" placeholder="Confirma nueva contraseña" required minlength="6">
              <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            <div class="row">
              <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Guardar contraseña</button>
              </div>
            </div>
          </form>
      <?php else: ?>
          <p class="mt-3 mb-1 text-center">
            <a href="login.php" class="btn btn-primary">Ir al inicio de sesión</a>
          </p>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>