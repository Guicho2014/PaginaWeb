<?php
require_once BASE_PATH . '/includes/conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ''; // Guarda los mensajes de éxito o error
$modo_edicion = false; // Indica si se está editando una máquina
$maquinaria_editar = ['id' => '', 'nombre' => '', 'modelo' => '', 'estado' => 'Operativa']; // Guarda los datos de la máquina seleccionada

// Verifica si se envió el formulario para guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre']); // Obtiene el nombre de la máquina
    $modelo = trim($_POST['modelo']); // Obtiene el modelo o número de serie
    $estado = trim($_POST['estado']); // Obtiene el estado de la máquina
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0; // Obtiene el ID de la máquina que se editará

    if (!empty($nombre)) { // Verifica que el nombre no esté vacío

        if ($id_editar > 0) { // Si existe un ID, actualiza la máquina
            $sql = "UPDATE cat_maquinaria SET nombre=?, modelo=?, estado=? WHERE id=?";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("sssi", $nombre, $modelo, $estado, $id_editar); // Asigna los valores
            $accion = "actualizado"; // Indica que la máquina fue actualizada

        } else { // Si no existe un ID, registra una nueva máquina
            $sql = "INSERT INTO cat_maquinaria (nombre, modelo, estado) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("sss", $nombre, $modelo, $estado); // Asigna los valores
            $accion = "agregado"; // Indica que la máquina fue agregada
        }

        if ($stmt->execute()) { // Ejecuta la consulta
            $mensaje = "<div class='alert success'>Máquina $accion correctamente.</div>"; // Mensaje de éxito
        } else {
            $mensaje = "<div class='alert error'>Error al guardar la máquina.</div>"; // Mensaje de error
        }

        $stmt->close(); // Cierra la consulta preparada
    }
}

// Verifica si se solicitó eliminar una máquina
if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']); // Obtiene el ID de la máquina que se eliminará

    try {
        $sql = "DELETE FROM cat_maquinaria WHERE id = ?";
        $stmt = $conn->prepare($sql); // Prepara la consulta de eliminación
        $stmt->bind_param("i", $id_eliminar); // Asigna el ID de la máquina
        $stmt->execute(); // Ejecuta la eliminación
        $stmt->close(); // Cierra la consulta

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/maquinaria"); // Regresa al catálogo
        exit();

    } catch (mysqli_sql_exception $e) { // Captura errores al eliminar

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: la máquina ya está en uso en asignaciones.</div>"; // Error por registros relacionados
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>"; // Muestra otro error
        }
    }
}

// Verifica si se seleccionó una máquina para editar
if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']); // Obtiene el ID de la máquina seleccionada
    $res = $conn->query("SELECT * FROM cat_maquinaria WHERE id = $id_editar"); // Busca los datos de la máquina

    if ($res && $res->num_rows > 0) { // Verifica que exista el registro
        $maquinaria_editar = $res->fetch_assoc(); // Guarda los datos de la máquina
        $modo_edicion = true; // Activa el modo de edición
    }
}

// Obtiene el texto que se utilizará para buscar máquinas
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_lista = "SELECT * FROM cat_maquinaria"; // Consulta todas las máquinas registradas

if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre LIKE '%$buscar%'"; // Filtra las máquinas por nombre
}

$sql_lista .= " ORDER BY id DESC"; // Ordena las máquinas del registro más reciente al más antiguo
$resultado = $conn->query($sql_lista); // Ejecuta la consulta y guarda los resultados
?>

<!-- Título principal del catálogo -->
<h2>Catálogo: Maquinaria</h2>

<?php echo $mensaje; // Muestra los mensajes del sistema ?>

<!-- Formulario para registrar o editar maquinaria -->
<div class="form-container">

    <!-- Cambia el título dependiendo del modo -->
    <h3><?php echo $modo_edicion ? 'Editar Máquina' : 'Añadir Nueva Máquina'; ?></h3>

    <form method="POST" action="">

        <!-- Guarda el ID de la máquina que se está editando -->
        <input type="hidden" name="id_editar" value="<?php echo $maquinaria_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($maquinaria_editar['nombre']); ?>" required>
                <!-- Campo para ingresar el nombre de la máquina -->
            </div>

            <div>
                <label>Modelo / Serie:</label>
                <input type="text" name="modelo" value="<?php echo htmlspecialchars($maquinaria_editar['modelo']); ?>">
                <!-- Campo para ingresar el modelo o número de serie -->
            </div>

            <div>
                <label>Estado:</label>
                <select name="estado">
                    <!-- Opciones disponibles para seleccionar el estado -->
                    <option value="Operativa" <?php echo ($maquinaria_editar['estado'] == 'Operativa') ? 'selected' : ''; ?>>Operativa</option>
                    <option value="En Mantenimiento" <?php echo ($maquinaria_editar['estado'] == 'En Mantenimiento') ? 'selected' : ''; ?>>En Mantenimiento</option>
                    <option value="Fuera de Servicio" <?php echo ($maquinaria_editar['estado'] == 'Fuera de Servicio') ? 'selected' : ''; ?>>Fuera de Servicio</option>
                </select>
            </div>
        </div>

        <!-- Cambia el texto del botón según la acción -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <!-- Permite cancelar la edición -->
            <a href="panel.php?vista=catalogos/maquinaria" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<!-- Contenedor de la tabla de maquinaria -->
<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Maquinaria</h3>

        <!-- Formulario para buscar máquinas -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/maquinaria">

            <!-- Campo para escribir el nombre de la máquina que se desea buscar -->
            <input type="text" name="buscar" placeholder="Buscar máquina..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra las máquinas registradas -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Modelo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Verifica si existen resultados -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada máquina encontrada -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td> <!-- Muestra el ID -->
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td> <!-- Muestra el nombre -->
                        <td><?php echo htmlspecialchars($row['modelo']); ?></td> <!-- Muestra el modelo -->
                        <td><?php echo htmlspecialchars($row['estado']); ?></td> <!-- Muestra el estado -->

                        <td>
                            <!-- Enlace para editar la máquina seleccionada -->
                            <a href="panel.php?vista=catalogos/maquinaria&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar la máquina con confirmación -->
                            <a href="panel.php?vista=catalogos/maquinaria&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no existen máquinas registradas -->
                <tr>
                    <td colspan="5" style="text-align: center;">No hay maquinaria registrada.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>