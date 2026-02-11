<?php
session_start();

// Guardar el tipo de usuario antes de destruir
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

// Destruir sesión
$_SESSION = array();
session_destroy();

// Redirigir al login
header("Location: login.php");
exit;
