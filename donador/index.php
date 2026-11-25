<?php
session_start(); // Inicia la sesión para recuperar mensajes
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> <!-- Define la codificación de caracteres -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Adapta la página a dispositivos -->
    <title>Pre-registro de Material | EcoPlanta</title> <!-- Título de la página -->
    <link rel="stylesheet" href="../assets/css/donador.css"> <!-- Carga los estilos CSS -->
</head>
<body>

    <div class="form-container"> <!-- Contenedor principal del formulario -->
        <h1>Pre-registro de Material</h1>
        <p>Déjanos tus datos y dinos qué material traerás a la planta. Un empleado te esperará.</p>

        <?php if(isset($_SESSION['mensaje_donador'])): ?>
            <!-- Muestra el mensaje guardado en la sesión -->
            <div class="alert success">
                <?php 
                    echo $_SESSION['mensaje_donador']; // Imprime el mensaje
                    unset($_SESSION['mensaje_donador']); // Elimina el mensaje después de mostrarlo
                ?>
            </div>
        <?php endif; ?>

        <!-- Formulario que envía los datos a guardar.php mediante POST -->
        <form action="guardar.php" method="POST">

            <div class="form-group">
                <label>Nombre Completo o Empresa:</label>
                <input type="text" name="nombre_donador" required> <!-- Captura el nombre del donador -->
            </div>

            <div class="form-group">
                <label>Teléfono de Contacto:</label>
                <input type="tel" name="telefono" required> <!-- Captura el teléfono -->
            </div>

            <div class="form-group">
                <label>Material a entregar:</label>
                <select name="id_materia_prima" required> <!-- Permite seleccionar el material -->
                    <option value="">Selecciona el material...</option>

                    <?php
                    require_once '../includes/conexion.php'; // Conecta con la base de datos

                    // Consulta los materiales registrados en el catálogo
                    $materias = $conn->query("SELECT id, nombre FROM cat_materias_primas");

                    // Recorre los resultados de la consulta
                    while($row = $materias->fetch_assoc()) {

                        // Genera una opción con el ID y nombre de cada material
                        echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cantidad aproximada (en kg):</label>
                <input type="number" step="0.01" name="cantidad_aproximada" required> <!-- Captura la cantidad en kilogramos -->
            </div>

            <button type="submit" class="btn-submit">Enviar Aviso</button> <!-- Envía el formulario -->
        </form>

        <a href="../index.php" class="link-back">← Volver al inicio</a> <!-- Regresa a la página principal -->
    </div>

</body>
</html>
