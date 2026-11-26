<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';
$modo_edicion = false;
$producto_editar = ['id' => '', 'nombre' => '', 'id_unidad' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre']);
    $id_unidad = intval($_POST['id_unidad']);
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;
    
    if (!empty($nombre) && $id_unidad > 0) {
        if ($id_editar > 0) {
            $sql = "UPDATE cat_productos SET nombre=?, id_unidad=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $nombre, $id_unidad, $id_editar);
            $accion = "actualizado";
        } else {
            $sql = "INSERT INTO cat_productos (nombre, id_unidad) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nombre, $id_unidad);
            $accion = "agregado";
        }
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Producto $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar el producto.</div>";
        }
        $stmt->close();
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $sql = "DELETE FROM cat_productos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/productos");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: este producto ya está en uso.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM cat_productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $producto_editar = $res->fetch_assoc();
        $modo_edicion = true;
    }
    $stmt->close();
}

$resultado = $conn->query("SELECT p.id, p.nombre, u.nombre as unidad, u.abreviatura 
                           FROM cat_productos p 
                           LEFT JOIN cat_unidades_medida u ON p.id_unidad = u.id 
                           ORDER BY p.id DESC");
$unidades = $conn->query("SELECT id, nombre, abreviatura FROM cat_unidades_medida");
?>

<h2>Catálogo: Productos</h2>
<?php echo $mensaje; ?>

<div class="form-container">
    <h3><?php echo $modo_edicion ? 'Editar Producto' : 'Añadir Nuevo Producto'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="id_editar" value="<?php echo htmlspecialchars($producto_editar['id']); ?>">
        <div class="form-grid">
            <div>
                <label>Nombre del Producto:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto_editar['nombre']); ?>" required>
            </div>
            <div>
                <label>Unidad de Medida:</label>
                <select name="id_unidad" required>
                    <option value="">Selecciona una unidad...</option>
                    <?php while($u = $unidades->fetch_assoc()): ?>
                        <option value="<?php echo $u['id']; ?>" 
                                <?php echo ($producto_editar['id_unidad'] == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nombre'] . " (" . $u['abreviatura'] . ")"); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/productos" style="margin-left: 15px; color: #cc0000;">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <h3>Lista de Productos</h3>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Unidad</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['unidad'] . " (" . $row['abreviatura'] . ")"); ?></td>
                        <td>
                            <a href="panel.php?vista=catalogos/productos&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                            <a href="panel.php?vista=catalogos/productos&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center;">No hay productos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>