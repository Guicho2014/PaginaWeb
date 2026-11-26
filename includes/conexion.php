<?php
$host = "127.0.0.1";
$usuario = "root";
$contrasena = "Fernando@2022"; // Tu contraseña actual de MariaDB
$base_datos = "planta_local";

// Crea la conexión con el servidor MySQL
$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

// Verifica si ocurrió un error al conectar
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establece la codificación UTF-8 completa para acentos y caracteres especiales
$conn->set_charset("utf8mb4");
?>