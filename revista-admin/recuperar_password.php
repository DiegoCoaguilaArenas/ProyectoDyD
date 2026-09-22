<?php
session_start();
require_once 'config/db.php';

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email) {
        // Verificamos si el correo existe
        $stmt = $pdo->prepare("SELECT id, nombres FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 1. Generar token de seguridad (64 caracteres)
            $token = bin2hex(random_bytes(32));
            // 2. Fecha de expiración (1 hora desde ahora)
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 3. Guardar en base de datos
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmtUpdate->execute([$token, $expires, $user['id']]);

            // 4. Crear el enlace de recuperación
            // Cambia "localhost/tu_carpeta" por tu dominio real cuando lo subas a internet
            $dominio = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            $enlace = $dominio . "/restablecer_password.php?token=" . $token;

            // 5. Simulación de envío de correo (Para entorno de prueba XAMPP)
            // En producción aquí usarías la función mail() o PHPMailer.
            $mensaje = '<b>Modo de prueba local:</b> Copia y pega el siguiente enlace en tu navegador para restablecer tu contraseña (este enlace expira en 1 hora): <br><br> <a href="'.$enlace.'">'.$enlace.'</a>';
            $tipo_alerta = 'alert-info';
            
            /* CÓDIGO REAL PARA PRODUCCIÓN (Descomentar al subir a un hosting)
            $asunto = "Recuperación de Contraseña - DDP Noticias";
            $cuerpo = "Hola " . $user['nombres'] . ",\n\nHas solicitado restablecer tu contraseña. Haz clic en el siguiente enlace:\n" . $enlace . "\n\nSi no fuiste tú, ignora este correo.";
            $headers = "From: noreply@dialogoydesarrollo.com.pe";
            mail($email, $asunto, $cuerpo, $headers);
            $mensaje = 'Si el correo existe en nuestro sistema, te hemos enviado un enlace de recuperación.';
            $tipo_alerta = 'alert-success';
            */
            
        } else {
            // Por seguridad, no decimos si el correo existe o no, mostramos el mismo mensaje.
            $mensaje = 'Si el correo existe en nuestro sistema, te hemos enviado un enlace de recuperación.';
            $tipo_alerta = 'alert-success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DDP ADMIN | Recuperar Contraseña</title>
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
      <p class="login-box-msg">Ingresa tu correo y te enviaremos las instrucciones.</p>
      
      <?php if ($mensaje): ?>
        <div class="alert <?= $tipo_alerta ?> py-2" style="font-size: 0.9rem; word-wrap: break-word;"><?= $mensaje ?></div>
      <?php endif; ?>

      <form action="" method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Correo electrónico registrado" required>
          <div class="input-group-text"><span class="bi bi-envelope"></span></div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Solicitar nueva contraseña</button>
          </div>
        </div>
      </form>
      <p class="mt-3 mb-1 text-center">
        <a href="login.php">Volver al login</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>