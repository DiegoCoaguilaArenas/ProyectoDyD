<?php
require_once 'config/db.php';

$nombres    = 'Administrador';
$ap_paterno = 'Principal';
$email      = 'admin@revista.com';
$password   = 'admin1234'; 
$hash       = password_hash($password, PASSWORD_BCRYPT);
$rol        = 'admin';

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombres, ap_paterno, email, password_hash, rol) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nombres, $ap_paterno, $email, $hash, $rol]);
    echo "<b>Usuario Admin creado exitosamente.</b><br>";
    echo "Email: admin@revista.com<br>";
    echo "Contraseña: admin1234<br>";
    echo "<a href='login.php'>Ir al Login</a>";
} catch (PDOException $e) {
    echo "El usuario ya existe o hubo un error: " . $e->getMessage();
}