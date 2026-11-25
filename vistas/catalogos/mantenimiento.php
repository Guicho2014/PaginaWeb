<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';

$modo_edicion = false;

$mantenimiento_editar = ['id' => '', 'nombre' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;

    if (!empty($nombre)) {

        if ($id_editar > 0) {

            $sql = "UPDATE cat_tipos_mantenimiento SET nombre=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("si", $nombre, $id_editar);
                $accion = "actualizado";
            } else {
                $accion = "";
                $mensaje = "<div class='alert error'>Error al guardar.</div>";
            }

        } else {

            $sql = "INSERT INTO cat_tipos_mantenimiento (nombre) VALUES (?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("s", $nombre);
                $accion = "agregado";
            } else {
                $accion = "";
                $mensaje = "<div class='alert error'>Error al guardar.</div>";
            }
        }

        if (isset($stmt) && $stmt) {
            if ($stmt->execute()) {
                $mensaje = "<div class='alert success'>Tipo de mantenimiento $accion correctamente.</div>";
            } else {
                $mensaje = "<div class='alert error'>Error al guardar.</div>";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']);

    try {

        $sql = "DELETE FROM cat_tipos_mantenimiento WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta.");
        }
        
        $stmt->bind_param("i", $id_eliminar);

        $stmt->execute();
        $stmt->close();

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/mantenimiento");
        exit();

    } catch (mysqli_sql_exception $e) {

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: el tipo ya está en uso en registros de mantenimiento.</div>";
        } else {
            error_log($e->getMessage());
            $mensaje = "<div class='alert error'>Error al eliminar el registro.</div>";
        }
    }
}

if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']);

    $res = $conn->query("SELECT * FROM cat_tipos_mantenimiento WHERE id = $id_editar");

    if ($res && $res->num_rows > 0) {

        $mantenimiento_editar = $res->fetch_assoc();

        $modo_edicion = true;
    }
}

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_lista = "SELECT * FROM cat_tipos_mantenimiento";

if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre LIKE '%$buscar%'";
}

$sql_lista .= " ORDER BY id DESC";

$resultado = $conn->query($sql_lista);
?>

<h2>Catálogo: Tipos de Mantenimiento</h2>

<?php echo $mensaje; ?>

<div class="form-container">

    <h3><?php echo $modo_edicion ? 'Editar Tipo' : 'Añadir Nuevo Tipo'; ?></h3>

    <form method="POST" action="">

        <input type="hidden" name="id_editar" value="<?php echo $mantenimiento_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre del Mantenimiento:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($mantenimiento_editar['nombre']); ?>" required>
            </div>
        </div>

        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/mantenimiento" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Tipos de Mantenimiento</h3>

        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/mantenimiento">

            <input type="text" name="buscar" placeholder="Buscar..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>

                        <td>
                            <a href="panel.php?vista=catalogos/mantenimiento&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <a href="panel.php?vista=catalogos/mantenimiento&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="3" style="text-align: center;">No hay tipos registrados.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>