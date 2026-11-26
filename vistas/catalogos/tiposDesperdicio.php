<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';
$modo_edicion = false;
$tipo_editar = ['id' => '', 'nombre' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre']);
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;
    
    if (!empty($nombre)) {
        if ($id_editar > 0) {
            $sql = "UPDATE cat_tipos_desperdicio SET nombre=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nombre, $id_editar);
            $accion = "actualizado";
        } else {
            $sql = "INSERT INTO cat_tipos_desperdicio (nombre) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $nombre);
            $accion = "agregado";
        }
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Tipo de desperdicio $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar.</div>";
        }
        $stmt->close();
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $sql = "DELETE FROM cat_tipos_desperdicio WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/tiposDesperdicio");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: este tipo está en uso.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM cat_tipos_desperdicio WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $tipo_editar = $res->fetch_assoc();
        $modo_edicion = true;
    }
    $stmt->close();
}

$resultado = $conn->query("SELECT * FROM cat_tipos_desperdicio ORDER BY id DESC");
?>

<h2>Catálogo: Tipos de Desperdicio</h2>
<?php echo $mensaje; ?>

<div class="form-container">
    <h3><?php echo $modo_edicion ? 'Editar Tipo' : 'Añadir Nuevo Tipo'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="id_editar" value="<?php echo htmlspecialchars($tipo_editar['id']); ?>">
        <div class="form-grid">
            <div>
                <label>Nombre del Tipo:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($tipo_editar['nombre']); ?>" required>
            </div>
        </div>
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/tiposDesperdicio" style="margin-left: 15px; color: #cc0000;">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <h3>Lista de Tipos de Desperdicio</h3>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td>
                            <a href="panel.php?vista=catalogos/tiposDesperdicio&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                            <a href="panel.php?vista=catalogos/tiposDesperdicio&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align: center;">No hay tipos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>