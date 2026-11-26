<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';
$modo_edicion = false;
$maq_editar = ['id' => '', 'nombre' => '', 'modelo' => '', 'estado' => 'Operativa'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre']);
    $modelo = trim($_POST['modelo']);
    $estado = trim($_POST['estado']);
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;
    
    if (!empty($nombre)) {
        if ($id_editar > 0) {
            $sql = "UPDATE cat_maquinaria SET nombre=?, modelo=?, estado=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nombre, $modelo, $estado, $id_editar);
            $accion = "actualizada";
        } else {
            $sql = "INSERT INTO cat_maquinaria (nombre, modelo, estado) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $nombre, $modelo, $estado);
            $accion = "agregada";
        }
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Maquinaria $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar.</div>";
        }
        $stmt->close();
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $sql = "DELETE FROM cat_maquinaria WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/maquinaria");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: esta maquinaria está en uso.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM cat_maquinaria WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $maq_editar = $res->fetch_assoc();
        $modo_edicion = true;
    }
    $stmt->close();
}

$resultado = $conn->query("SELECT * FROM cat_maquinaria ORDER BY id DESC");
?>

<h2>Catálogo: Maquinaria</h2>
<?php echo $mensaje; ?>

<div class="form-container">
    <h3><?php echo $modo_edicion ? 'Editar Maquinaria' : 'Añadir Nueva Maquinaria'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="id_editar" value="<?php echo htmlspecialchars($maq_editar['id']); ?>">
        <div class="form-grid">
            <div>
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($maq_editar['nombre']); ?>" required>
            </div>
            <div>
                <label>Modelo:</label>
                <input type="text" name="modelo" value="<?php echo htmlspecialchars($maq_editar['modelo']); ?>">
            </div>
            <div>
                <label>Estado:</label>
                <select name="estado">
                    <option value="Operativa" <?php echo ($maq_editar['estado'] == 'Operativa') ? 'selected' : ''; ?>>Operativa</option>
                    <option value="Mantenimiento" <?php echo ($maq_editar['estado'] == 'Mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                    <option value="Fuera de Servicio" <?php echo ($maq_editar['estado'] == 'Fuera de Servicio') ? 'selected' : ''; ?>>Fuera de Servicio</option>
                </select>
            </div>
        </div>
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/maquinaria" style="margin-left: 15px; color: #cc0000;">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <h3>Lista de Maquinaria</h3>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Modelo</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                        <td><?php echo htmlspecialchars($row['estado']); ?></td>
                        <td>
                            <a href="panel.php?vista=catalogos/maquinaria&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                            <a href="panel.php?vista=catalogos/maquinaria&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">No hay maquinaria registrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>