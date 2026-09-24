<?php
// 1. Cargar el autoloader de Composer para que PHP reconozca la librería
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. Solo cargar las variables del archivo .env oculto SI EL ARCHIVO EXISTE (entorno local)
$ruta_env = __DIR__ . '/../../';
if (file_exists($ruta_env . '.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($ruta_env);
    $dotenv->load();
}

// 3. Asignar las variables de entorno soportando tanto local como la nube (Railway)
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$user = $_ENV['DB_USER'] ?? getenv('DB_USER');
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306'; // Agregamos el puerto por si acaso
$charset = 'utf8mb4';

// 4. Agregamos el puerto a la cadena de conexión
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Mostrar el error exacto que devuelve MySQL
    exit('Error de conexión a la base de datos: ' . $e->getMessage());
}
?>