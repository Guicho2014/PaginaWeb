<?php
require_once BASE_PATH . '/includes/conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ''; // Guarda los mensajes de éxito o error
$modo_edicion = false; // Indica si se está editando un área
$area_editar = ['id' => '', 'nombre' => '', 'descripcion' => '']; // Guarda los datos del área seleccionada

// Verifica si se envió el formulario para guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre']); // Obtiene el nombre del área
    $descripcion = trim($_POST['descripcion']); // Obtiene la descripción del área
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0; // Obtiene el ID del área a editar

    if (!empty($nombre)) { // Verifica que el nombre no esté vacío

        if ($id_editar > 0) { // Si existe un ID, actualiza el área
            $sql = "UPDATE cat_areas_produccion SET nombre=?, descripcion=? WHERE id=?";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("ssi", $nombre, $descripcion, $id_editar); // Asigna los valores
            $accion = "actualizado"; // Indica que se realizó una actualización

        } else { // Si no existe un ID, registra una nueva área
            $sql = "INSERT INTO cat_areas_produccion (nombre, descripcion) VALUES (?, ?)";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("ss", $nombre, $descripcion); // Asigna los valores
            $accion = "agregado"; // Indica que se agregó un área
        }

        if ($stmt->execute()) { // Ejecuta la consulta
            $mensaje = "<div class='alert success'>Área $accion correctamente.</div>"; // Mensaje de éxito
        } else {
            $mensaje = "<div class='alert error'>Error al guardar el área.</div>"; // Mensaje de error
        }
        $stmt->close(); // Cierra la consulta preparada
    }
}

// Verifica si se solicitó eliminar un área
if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']); // Obtiene el ID del área que se eliminará

    try {
        $sql = "DELETE FROM cat_areas_produccion WHERE id = ?";
        $stmt = $conn->prepare($sql); // Prepara la consulta de eliminación
        $stmt->bind_param("i", $id_eliminar); // Asigna el ID del área
        $stmt->execute(); // Ejecuta la eliminación
        $stmt->close(); // Cierra la consulta

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/areas"); // Regresa al catálogo
        exit();

    } catch (mysqli_sql_exception $e) { // Captura errores de eliminación

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: el área ya está en uso en asignaciones.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>";
        }
    }
}

// Verifica si se seleccionó un área para editar
if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']); // Obtiene el ID del área seleccionada
    $res = $conn->query("SELECT * FROM cat_areas_produccion WHERE id = $id_editar"); // Busca el área

    if ($res && $res->num_rows > 0) { // Verifica que exista el registro
        $area_editar = $res->fetch_assoc(); // Guarda los datos del área
        $modo_edicion = true; // Activa el modo de edición
    }
}

// Obtiene el texto que se utilizará para buscar áreas
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_lista = "SELECT * FROM cat_areas_produccion"; // Consulta todos los registros

if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre LIKE '%$buscar%'"; // Filtra las áreas por nombre
}

$sql_lista .= " ORDER BY id DESC"; // Ordena los registros del más reciente al más antiguo
$resultado = $conn->query($sql_lista); // Ejecuta la consulta y guarda los resultados
?>

<!-- Título principal del catálogo -->
<h2>Catálogo: Áreas de Producción</h2>

<?php echo $mensaje; // Muestra los mensajes del sistema ?>

<!-- Formulario para registrar o editar áreas -->
<div class="form-container">

    <!-- Cambia el título dependiendo del modo -->
    <h3><?php echo $modo_edicion ? 'Editar Área' : 'Añadir Nueva Área'; ?></h3>

    <form method="POST" action="">

        <!-- Guarda el ID del área que se está editando -->
        <input type="hidden" name="id_editar" value="<?php echo $area_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre del Área:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($area_editar['nombre']); ?>" required>
                <!-- Campo para ingresar el nombre -->
            </div>

            <div>
                <label>Descripción:</label>
                <input type="text" name="descripcion" value="<?php echo htmlspecialchars($area_editar['descripcion']); ?>">
                <!-- Campo para ingresar la descripción -->
            </div>
        </div>

        <!-- Cambia el texto del botón según la acción -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <!-- Permite cancelar la edición -->
            <a href="panel.php?vista=catalogos/areas" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<!-- Contenedor de la tabla de áreas -->
<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Áreas</h3>

        <!-- Formulario para buscar áreas por nombre -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/areas">

            <!-- Campo donde se escribe el nombre que se desea buscar -->
            <input type="text" name="buscar" placeholder="Buscar área..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra las áreas registradas -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Verifica si existen resultados -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada área encontrada -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td> <!-- Muestra el ID -->
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td> <!-- Muestra el nombre -->
                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td> <!-- Muestra la descripción -->

                        <td>
                            <!-- Enlace para editar el área seleccionada -->
                            <a href="panel.php?vista=catalogos/areas&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar el área con confirmación -->
                            <a href="panel.php?vista=catalogos/areas&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no existen áreas registradas -->
                <tr>
                    <td colspan="4" style="text-align: center;">No hay áreas registradas.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>