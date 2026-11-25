<?php
session_start(); // Inicia la sesión para guardar mensajes

require_once '../includes/conexion.php'; // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = trim($_POST['nombre_donador']); // Obtiene el nombre del donador
    $telefono = trim($_POST['telefono']); // Obtiene el teléfono del donador
    $id_materia = intval($_POST['id_materia_prima']); // Obtiene el ID de la materia prima
    $cantidad = floatval($_POST['cantidad_aproximada']); // Obtiene la cantidad aproximada

    // Verifica que los datos obligatorios estén completos
    if (!empty($nombre) && !empty($telefono) && $id_materia > 0) {

        // Consulta para insertar los datos en la tabla pre_registros
        $sql = "INSERT INTO pre_registros (nombre_donador, telefono, id_materia_prima, cantidad_aproximada) VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql); // Prepara la consulta SQL
        $stmt->bind_param("ssid", $nombre, $telefono, $id_materia, $cantidad); // Asigna los valores a la consulta

        if ($stmt->execute()) {
            // Guarda un mensaje si el registro fue exitoso
            $_SESSION['mensaje_donador'] = "¡Gracias! Tu aviso ha sido enviado. Por favor, acércate a la planta.";
        } else {
            // Guarda un mensaje si ocurrió un error
            $_SESSION['mensaje_donador'] = "Hubo un error al guardar tus datos.";
        }

        $stmt->close(); // Cierra la consulta preparada
    }
}

// Regresa al formulario principal
header("Location: index.php");
exit(); // Finaliza la ejecución
?>