<?php
require_once 'config/db.php';

$email         = 'admin@revista.com';
$password_plana = 'admin1234';
$hash_correcto = password_hash($password_plana, PASSWORD_BCRYPT);

try {
    // 1. Comprobar si ya existe el usuario
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Si existe, actualizamos su hash de contraseña y nos aseguramos de que sea 'admin'
        $update = $pdo->prepare("UPDATE usuarios SET password_hash = ?, rol = 'admin' WHERE email = ?");
        $update->execute([$hash_correcto, $email]);
        echo "<div style='font-family:sans-serif; padding:20px; background:#d4edda; color:#155724; border-radius:5px;'>";
        echo "<h2>✓ Contraseña reestructurada con éxito</h2>";
        echo "<p>Se ha actualizado la contraseña encriptada para el usuario <strong>$email</strong>.</p>";
        echo "</div>";
    } else {
        // Si no existe, lo creamos desde cero
        $insert = $pdo->prepare("INSERT INTO usuarios (nombres, ap_paterno, email, password_hash, rol) VALUES ('Administrador', 'Principal', ?, ?, 'admin')");
        $insert->execute([$email, $hash_correcto]);
        echo "<div style='font-family:sans-serif; padding:20px; background:#d4edda; color:#155724; border-radius:5px;'>";
        echo "<h2>✓ Usuario Administrador creado con éxito</h2>";
        echo "<p>Se creó el usuario <strong>$email</strong> en la base de datos.</p>";
        echo "</div>";
    }

    echo "<br><a href='login.php' style='display:inline-block; padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px;'>Ir a Iniciar Sesión</a>";

} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif; padding:20px; background:#f8d7da; color:#721c24; border-radius:5px;'>";
    echo "<h2>Error de Base de Datos:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}