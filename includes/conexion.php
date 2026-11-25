<?php
$host = "127.0.0.1"; // Dirección del servidor donde está alojada la base de datos
$usuario = "root"; // Usuario para acceder a MySQL
$contrasena = "pass"; // Contraseña del usuario de MySQL
$base_datos = "planta_local"; // Nombre de la base de datos que se utilizará

// Crea la conexión con el servidor MySQL
$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

// Verifica si ocurrió un error al conectar
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error); // Detiene el programa y muestra el error
}

// Establece la codificación UTF-8 para mostrar correctamente acentos y caracteres especiales
$conn->set_charset("utf8");
?>