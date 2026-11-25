<?php
require_once BASE_PATH . '/includes/conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ''; // Guarda los mensajes de éxito o error
$modo_edicion = false; // Indica si se está editando una materia prima
$materia_editar = ['id' => '', 'nombre' => '', 'id_unidad' => '']; // Guarda los datos del material seleccionado

// Verifica si se envió el formulario para guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre']); // Obtiene el nombre de la materia prima
    $id_unidad = intval($_POST['id_unidad']); // Obtiene el ID de la unidad de medida
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0; // Obtiene el ID del material que se editará

    if (!empty($nombre) && $id_unidad > 0) { // Verifica que el nombre y la unidad sean válidos

        if ($id_editar > 0) { // Si existe un ID, actualiza la materia prima
            $sql = "UPDATE cat_materias_primas SET nombre=?, id_unidad=? WHERE id=?";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("sii", $nombre, $id_unidad, $id_editar); // Asigna los valores
            $accion = "actualizado"; // Indica que el material fue actualizado

        } else { // Si no existe un ID, registra una nueva materia prima
            $sql = "INSERT INTO cat_materias_primas (nombre, id_unidad) VALUES (?, ?)";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("si", $nombre, $id_unidad); // Asigna los valores
            $accion = "agregado"; // Indica que el material fue agregado
        }

        if ($stmt->execute()) { // Ejecuta la consulta
            $mensaje = "<div class='alert success'>Materia prima $accion correctamente.</div>"; // Mensaje de éxito
        } else {
            $mensaje = "<div class='alert error'>Error al guardar la materia prima.</div>"; // Mensaje de error
        }

        $stmt->close(); // Cierra la consulta preparada
    }
}

// Verifica si se solicitó eliminar una materia prima
if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']); // Obtiene el ID del material que se eliminará

    try {
        $sql = "DELETE FROM cat_materias_primas WHERE id = ?";
        $stmt = $conn->prepare($sql); // Prepara la consulta de eliminación
        $stmt->bind_param("i", $id_eliminar); // Asigna el ID del material
        $stmt->execute(); // Ejecuta la eliminación
        $stmt->close(); // Cierra la consulta

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/materiasPrimas"); // Regresa al catálogo
        exit();

    } catch (mysqli_sql_exception $e) { // Captura errores al eliminar

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: esta materia prima ya está en uso en recepciones u otros módulos.</div>"; // Error por registros relacionados
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>"; // Muestra otro error
        }
    }
}

// Verifica si se seleccionó una materia prima para editar
if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']); // Obtiene el ID del material seleccionado
    $res = $conn->query("SELECT * FROM cat_materias_primas WHERE id = $id_editar"); // Busca los datos del material

    if ($res && $res->num_rows > 0) { // Verifica que exista el registro
        $materia_editar = $res->fetch_assoc(); // Guarda los datos de la materia prima
        $modo_edicion = true; // Activa el modo de edición
    }
}

// Obtiene el texto que se utilizará para buscar materiales
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Consulta las materias primas junto con sus unidades de medida
$sql_lista = "SELECT m.id, m.nombre, u.nombre as unidad, u.abreviatura 
              FROM cat_materias_primas m 
              LEFT JOIN cat_unidades_medida u ON m.id_unidad = u.id";

if (!empty($buscar)) {
    $sql_lista .= " WHERE m.nombre LIKE '%$buscar%'"; // Filtra las materias primas por nombre
}

$sql_lista .= " ORDER BY m.id DESC"; // Ordena los materiales del más reciente al más antiguo
$resultado = $conn->query($sql_lista); // Ejecuta la consulta y guarda los resultados

$unidades = $conn->query("SELECT id, nombre, abreviatura FROM cat_unidades_medida"); // Obtiene las unidades de medida disponibles
?>

<!-- Título principal del catálogo -->
<h2>Catálogo: Materias Primas</h2>

<?php echo $mensaje; // Muestra los mensajes del sistema ?>

<!-- Formulario para registrar o editar materias primas -->
<div class="form-container">

    <!-- Cambia el título dependiendo del modo -->
    <h3><?php echo $modo_edicion ? 'Editar Materia Prima' : 'Añadir Nueva Materia Prima'; ?></h3>

    <form method="POST" action="">

        <!-- Guarda el ID del material que se está editando -->
        <input type="hidden" name="id_editar" value="<?php echo $materia_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre del Material:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($materia_editar['nombre']); ?>" required>
                <!-- Campo para ingresar el nombre de la materia prima -->
            </div>

            <div>
                <label>Unidad de Medida:</label>

                <select name="id_unidad" required>
                    <option value="">Selecciona una unidad...</option>

                    <!-- Recorre las unidades de medida disponibles -->
                    <?php while($u = $unidades->fetch_assoc()): ?>

                        <!-- Genera una opción con el ID y nombre de cada unidad -->
                        <option value="<?php echo $u['id']; ?>" <?php echo ($materia_editar['id_unidad'] == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo $u['nombre'] . " (" . $u['abreviatura'] . ")"; ?>
                        </option>

                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Cambia el texto del botón según la acción -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar Material' : 'Guardar Material'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <!-- Permite cancelar la edición -->
            <a href="panel.php?vista=catalogos/materiasPrimas" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<!-- Contenedor de la tabla de materias primas -->
<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Materias Primas</h3>

        <!-- Formulario para buscar materiales -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/materiasPrimas">

            <!-- Campo para escribir el nombre del material que se desea buscar -->
            <input type="text" name="buscar" placeholder="Buscar material..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra las materias primas registradas -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre del Material</th>
                <th>Unidad de Medida</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Verifica si existen resultados -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada materia prima encontrada -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td> <!-- Muestra el ID -->
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td> <!-- Muestra el nombre del material -->

                        <!-- Muestra la unidad y su abreviatura -->
                        <td><?php echo htmlspecialchars($row['unidad'] . " (" . $row['abreviatura'] . ")"); ?></td>

                        <td>
                            <!-- Enlace para editar la materia prima seleccionada -->
                            <a href="panel.php?vista=catalogos/materiasPrimas&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar el material con confirmación -->
                            <a href="panel.php?vista=catalogos/materiasPrimas&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no existen materiales registrados -->
                <tr>
                    <td colspan="4" style="text-align: center;">No se encontraron materiales.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>