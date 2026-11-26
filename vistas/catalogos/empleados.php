<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';
$modo_edicion = false;
$emp_editar = ['id' => '', 'nombre_completo' => '', 'puesto' => '', 'turno' => '', 'estado' => 'Activo'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre_completo']);
    $puesto = trim($_POST['puesto']);
    $turno = trim($_POST['turno']);
    $estado = trim($_POST['estado']);
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;
    
    if (!empty($nombre)) {
        if ($id_editar > 0) {
            $sql = "UPDATE cat_empleados SET nombre_completo=?, puesto=?, turno=?, estado=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nombre, $puesto, $turno, $estado, $id_editar);
            $accion = "actualizado";
        } else {
            $sql = "INSERT INTO cat_empleados (nombre_completo, puesto, turno, estado) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nombre, $puesto, $turno, $estado);
            $accion = "agregado";
        }
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Empleado $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar.</div>";
        }
        $stmt->close();
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $sql = "DELETE FROM cat_empleados WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/empleados");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: este empleado está en uso.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM cat_empleados WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $emp_editar = $res->fetch_assoc();
        $modo_edicion = true;
    }
    $stmt->close();
}

$resultado = $conn->query("SELECT * FROM cat_empleados ORDER BY id DESC");
?>

<h2>Catálogo: Empleados</h2>
<?php echo $mensaje; ?>

<div class="form-container">
    <h3><?php echo $modo_edicion ? 'Editar Empleado' : 'Añadir Nuevo Empleado'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="id_editar" value="<?php echo htmlspecialchars($emp_editar['id']); ?>">
        <div class="form-grid">
            <div>
                <label>Nombre Completo:</label>
                <input type="text" name="nombre_completo" value="<?php echo htmlspecialchars($emp_editar['nombre_completo']); ?>" required>
            </div>
            <div>
                <label>Puesto:</label>
                <input type="text" name="puesto" value="<?php echo htmlspecialchars($emp_editar['puesto']); ?>">
            </div>
            <div>
                <label>Turno:</label>
                <input type="text" name="turno" value="<?php echo htmlspecialchars($emp_editar['turno']); ?>">
            </div>
            <div>
                <label>Estado:</label>
                <select name="estado">
                    <option value="Activo" <?php echo ($emp_editar['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Inactivo" <?php echo ($emp_editar['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/empleados" style="margin-left: 15px; color: #cc0000;">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <h3>Lista de Empleados</h3>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Puesto</th><th>Turno</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                        <td><?php echo htmlspecialchars($row['turno']); ?></td>
                        <td><?php echo htmlspecialchars($row['estado']); ?></td>
                        <td>
                            <a href="panel.php?vista=catalogos/empleados&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                            <a href="panel.php?vista=catalogos/empleados&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">No hay empleados registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>