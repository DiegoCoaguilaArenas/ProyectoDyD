<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}