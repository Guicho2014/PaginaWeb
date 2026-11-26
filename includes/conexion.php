<?php
$host = "127.0.0.1";
$usuario = "root";
$contrasena = "Fernando@2022";  // ← Tu nueva contraseña
$base_datos = "planta_local";

// Crea la conexión
$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

// Verifica errores
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// ✅ Mejora: usar utf8mb4 en lugar de utf8 para soporte completo de emojis y caracteres especiales
$conn->set_charset("utf8mb4");

// ✅ Mejora: desactivar errores en pantalla en producción
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
?>