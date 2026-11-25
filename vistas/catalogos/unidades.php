<?php
// Incluye el archivo de conexión a la base de datos.
require_once BASE_PATH . '/includes/conexion.php';

// Guarda los mensajes de éxito o error.
$mensaje = '';

// Indica si se está editando una unidad.
$modo_edicion = false;

// Guarda los datos de la unidad que se va a editar.
$unidad_editar = ['id' => '', 'nombre' => '', 'abreviatura' => ''];

// Comprueba si se envió el formulario y se presionó Guardar.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    // Obtiene el nombre de la unidad y elimina espacios innecesarios.
    $nombre = trim($_POST['nombre']);

    // Obtiene la abreviatura de la unidad.
    $abreviatura = trim($_POST['abreviatura']);

    // Obtiene el ID del registro a editar; si no existe, asigna 0.
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;

    // Comprueba que el nombre y la abreviatura no estén vacíos.
    if (!empty($nombre) && !empty($abreviatura)) {

        // Si el ID es mayor a 0, actualiza una unidad existente.
        if ($id_editar > 0) {

            $sql = "UPDATE cat_unidades_medida SET nombre=?, abreviatura=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nombre, $abreviatura, $id_editar);

            // Indica que la unidad fue actualizada.
            $accion = "actualizado";

        } else {

            // Si no existe ID, registra una nueva unidad.
            $sql = "INSERT INTO cat_unidades_medida (nombre, abreviatura) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $nombre, $abreviatura);

            // Indica que la unidad fue agregada.
            $accion = "agregado";
        }

        // Ejecuta la consulta y muestra el resultado.
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Unidad $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar la unidad.</div>";
        }

        // Cierra la consulta preparada.
        $stmt->close();
    }
}

// Comprueba si se solicitó eliminar una unidad.
if (isset($_GET['eliminar'])) {

    // Obtiene el ID de la unidad que se eliminará.
    $id_eliminar = intval($_GET['eliminar']);

    try {

        // Consulta SQL para eliminar la unidad seleccionada.
        $sql = "DELETE FROM cat_unidades_medida WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);

        // Ejecuta la eliminación.
        $stmt->execute();
        $stmt->close();

        // Regresa al catálogo después de eliminar.
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/unidades");
        exit();

    } catch (mysqli_sql_exception $e) {

        // Comprueba si la unidad está relacionada con otros registros.
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: esta unidad está en uso en materias primas o productos.</div>";
        } else {

            // Muestra cualquier otro error de eliminación.
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>";
        }
    }
}

// Comprueba si se seleccionó una unidad para editar.
if (isset($_GET['editar'])) {

    // Obtiene el ID de la unidad seleccionada.
    $id_editar = intval($_GET['editar']);

    // Busca la unidad en la base de datos.
    $res = $conn->query("SELECT * FROM cat_unidades_medida WHERE id = $id_editar");

    // Comprueba que el registro exista.
    if ($res && $res->num_rows > 0) {

        // Guarda los datos encontrados.
        $unidad_editar = $res->fetch_assoc();

        // Activa el modo de edición.
        $modo_edicion = true;
    }
}

// Obtiene el texto que el usuario escribió para buscar.
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Consulta las unidades registradas.
$sql_lista = "SELECT id, nombre, abreviatura FROM cat_unidades_medida";

// Si hay una búsqueda, filtra las unidades por nombre.
if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre LIKE '%$buscar%'";
}

// Ordena las unidades de la más reciente a la más antigua.
$sql_lista .= " ORDER BY id DESC";

// Ejecuta la consulta y guarda los resultados.
$resultado = $conn->query($sql_lista);
?>

<!-- Título del catálogo -->
<h2>Catálogo: Unidades de Medida</h2>

<!-- Muestra los mensajes de éxito o error -->
<?php echo $mensaje; ?>

<div class="form-container">

    <!-- Cambia el título dependiendo de si se agrega o edita -->
    <h3><?php echo $modo_edicion ? 'Editar Unidad' : 'Añadir Nueva Unidad'; ?></h3>

    <!-- Formulario para agregar o modificar unidades -->
    <form method="POST" action="">

        <!-- Guarda el ID de la unidad sin mostrarlo -->
        <input type="hidden" name="id_editar" value="<?php echo $unidad_editar['id']; ?>">

        <div class="form-grid">
            <div>

                <!-- Campo para escribir el nombre de la unidad -->
                <label>Nombre (Ej. Kilogramos):</label>

                <!-- Muestra el nombre anterior si se está editando -->
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($unidad_editar['nombre']); ?>" required>
            </div>

            <div>

                <!-- Campo para escribir la abreviatura -->
                <label>Abreviatura (Ej. kg):</label>

                <!-- Muestra la abreviatura anterior si se está editando -->
                <input type="text" name="abreviatura" value="<?php echo htmlspecialchars($unidad_editar['abreviatura']); ?>" required>
            </div>
        </div>

        <!-- Botón que cambia según la operación -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>

        <!-- Muestra cancelar solamente cuando se está editando -->
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/unidades" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <!-- Título de la tabla -->
        <h3>Lista de Unidades</h3>

        <!-- Formulario para buscar unidades -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <!-- Indica qué catálogo se está consultando -->
            <input type="hidden" name="vista" value="catalogos/unidades">

            <!-- Campo para escribir el nombre que se desea buscar -->
            <input type="text" name="buscar" placeholder="Buscar..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <!-- Envía la búsqueda -->
            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra las unidades registradas -->
    <table class="data-table">

        <!-- Encabezados de la tabla -->
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Abreviatura</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Comprueba si existen unidades registradas -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada unidad encontrada -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>

                        <!-- Muestra el ID de la unidad -->
                        <td><?php echo $row['id']; ?></td>

                        <!-- Muestra el nombre de la unidad -->
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>

                        <!-- Muestra la abreviatura -->
                        <td><?php echo htmlspecialchars($row['abreviatura']); ?></td>

                        <td>

                            <!-- Enlace para editar la unidad -->
                            <a href="panel.php?vista=catalogos/unidades&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar con confirmación -->
                            <a href="panel.php?vista=catalogos/unidades&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>

                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no hay unidades registradas -->
                <tr>
                    <td colspan="4" style="text-align: center;">No hay unidades.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>