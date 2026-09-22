<?php
session_start();
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre']  = $user['nombres'] . ' ' . $user['ap_paterno'];
            $_SESSION['rol']     = $user['rol'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Credenciales incorrectas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DDP ADMIN | Iniciar Sesión</title>
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
      <p class="login-box-msg">Ingresa tus datos para iniciar sesión</p>
      
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="" method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
          <div class="input-group-text"><span class="bi bi-envelope"></span></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
          <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Acceder</button>
          </div>
        </div>
      </form>
      <!-- Enlace agregado para la recuperación -->
      <p class="mt-3 mb-1 text-center">
        <a href="recuperar_password.php">Olvide mi contraseña</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>