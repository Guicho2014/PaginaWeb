<?php
ini_set('display_errors', 1); // Activa la visualización de errores de PHP
error_reporting(E_ALL); // Muestra todos los tipos de errores

require_once BASE_PATH . '/includes/conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ''; // Guarda los mensajes de éxito o error
$modo_edicion = false; // Indica si se está editando un empleado
$empleado_editar = ['id' => '', 'nombre_completo' => '', 'puesto' => '', 'turno' => '']; // Guarda los datos del empleado seleccionado

// Verifica si se envió el formulario para guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre']); // Obtiene el nombre completo del empleado
    $puesto = trim($_POST['puesto']); // Obtiene el puesto del empleado
    $turno = trim($_POST['turno']); // Obtiene el turno del empleado
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0; // Obtiene el ID del empleado que se editará

    if (!empty($nombre) && !empty($puesto)) { // Verifica que el nombre y puesto no estén vacíos

        if ($id_editar > 0) { // Si existe un ID, actualiza el empleado
            $sql = "UPDATE cat_empleados SET nombre_completo=?, puesto=?, turno=? WHERE id=?";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("sssi", $nombre, $puesto, $turno, $id_editar); // Asigna los valores
            $accion = "actualizado"; // Indica que el empleado fue actualizado

        } else { // Si no existe un ID, registra un nuevo empleado
            $sql = "INSERT INTO cat_empleados (nombre_completo, puesto, turno) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("sss", $nombre, $puesto, $turno); // Asigna los valores
            $accion = "agregado"; // Indica que el empleado fue agregado
        }

        if ($stmt->execute()) { // Ejecuta la consulta
            $mensaje = "<div class='alert success'>Empleado $accion correctamente.</div>"; // Mensaje de éxito
        } else {
            $mensaje = "<div class='alert error'>Error al guardar el empleado.</div>"; // Mensaje de error
        }

        $stmt->close(); // Cierra la consulta preparada
    }
}

// Verifica si se solicitó eliminar un empleado
if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']); // Obtiene el ID del empleado que se eliminará

    try {
        $sql = "DELETE FROM cat_empleados WHERE id = ?";
        $stmt = $conn->prepare($sql); // Prepara la consulta de eliminación
        $stmt->bind_param("i", $id_eliminar); // Asigna el ID del empleado
        $stmt->execute(); // Ejecuta la eliminación
        $stmt->close(); // Cierra la consulta

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/empleados"); // Regresa al catálogo
        exit();

    } catch (mysqli_sql_exception $e) { // Captura errores al eliminar

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar este empleado porque tiene una cuenta de usuario asociada.</div>"; // Error por registros relacionados
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>"; // Muestra otro error
        }
    }
}

// Verifica si se seleccionó un empleado para editar
if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']); // Obtiene el ID del empleado seleccionado
    $res = $conn->query("SELECT * FROM cat_empleados WHERE id = $id_editar"); // Busca los datos del empleado

    if ($res && $res->num_rows > 0) { // Verifica que exista el registro
        $empleado_editar = $res->fetch_assoc(); // Guarda los datos del empleado
        $modo_edicion = true; // Activa el modo de edición
    }
}

// Obtiene el texto que se utilizará para buscar empleados
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_lista = "SELECT id, nombre_completo, puesto, turno FROM cat_empleados"; // Consulta los datos de los empleados

if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre_completo LIKE '%$buscar%' OR puesto LIKE '%$buscar%'"; // Filtra por nombre o puesto
}

$sql_lista .= " ORDER BY id DESC"; // Ordena los empleados del más reciente al más antiguo
$resultado = $conn->query($sql_lista); // Ejecuta la consulta y guarda los resultados
?>

<!-- Título principal del catálogo -->
<h2>Catálogo: Empleados</h2>

<?php echo $mensaje; // Muestra los mensajes del sistema ?>

<!-- Formulario para registrar o editar empleados -->
<div class="form-container">

    <!-- Cambia el título dependiendo del modo -->
    <h3><?php echo $modo_edicion ? 'Editar Empleado' : 'Añadir Nuevo Empleado'; ?></h3>

    <form method="POST" action="">

        <!-- Guarda el ID del empleado que se está editando -->
        <input type="hidden" name="id_editar" value="<?php echo $empleado_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($empleado_editar['nombre_completo']); ?>" required>
                <!-- Campo para ingresar el nombre del empleado -->
            </div>

            <div>
                <label>Puesto:</label>
                <input type="text" name="puesto" value="<?php echo htmlspecialchars($empleado_editar['puesto']); ?>" required>
                <!-- Campo para ingresar el puesto -->
            </div>

            <div>
                <label>Turno:</label>
                <select name="turno">
                    <!-- Opciones disponibles para seleccionar el turno -->
                    <option value="Matutino" <?php echo ($empleado_editar['turno'] == 'Matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="Vespertino" <?php echo ($empleado_editar['turno'] == 'Vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                    <option value="Nocturno" <?php echo ($empleado_editar['turno'] == 'Nocturno') ? 'selected' : ''; ?>>Nocturno</option>
                </select>
            </div>
        </div>

        <!-- Cambia el texto del botón según la acción -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar Empleado' : 'Guardar Empleado'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <!-- Permite cancelar la edición -->
            <a href="panel.php?vista=catalogos/empleados" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<!-- Contenedor de la tabla de empleados -->
<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Empleados</h3>

        <!-- Formulario para buscar empleados -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/empleados">

            <!-- Campo para buscar por nombre o puesto -->
            <input type="text" name="buscar" placeholder="Buscar por nombre o puesto..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra los empleados registrados -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Puesto</th>
                <th>Turno</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Verifica si existen resultados -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada empleado encontrado -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td> <!-- Muestra el ID -->
                        <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td> <!-- Muestra el nombre -->
                        <td><?php echo htmlspecialchars($row['puesto']); ?></td> <!-- Muestra el puesto -->
                        <td><?php echo htmlspecialchars($row['turno']); ?></td> <!-- Muestra el turno -->

                        <td>
                            <!-- Enlace para editar el empleado seleccionado -->
                            <a href="panel.php?vista=catalogos/empleados&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar el empleado con confirmación -->
                            <a href="panel.php?vista=catalogos/empleados&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no existen empleados registrados -->
                <tr>
                    <td colspan="5" style="text-align: center;">No se encontraron empleados.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>