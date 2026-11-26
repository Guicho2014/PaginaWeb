<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';
$modo_edicion = false;
$cli_editar = ['id' => '', 'nombre_empresa' => '', 'contacto' => '', 'telefono' => '', 'direccion' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre_empresa = trim($_POST['nombre_empresa']);
    $contacto = trim($_POST['contacto']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;
    
    if (!empty($nombre_empresa)) {
        if ($id_editar > 0) {
            $sql = "UPDATE cat_clientes SET nombre_empresa=?, contacto=?, telefono=?, direccion=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nombre_empresa, $contacto, $telefono, $direccion, $id_editar);
            $accion = "actualizado";
        } else {
            $sql = "INSERT INTO cat_clientes (nombre_empresa, contacto, telefono, direccion) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nombre_empresa, $contacto, $telefono, $direccion);
            $accion = "agregado";
        }
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Cliente $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar.</div>";
        }
        $stmt->close();
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $sql = "DELETE FROM cat_clientes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/clientes");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: este cliente está en uso.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM cat_clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $cli_editar = $res->fetch_assoc();
        $modo_edicion = true;
    }
    $stmt->close();
}

$resultado = $conn->query("SELECT * FROM cat_clientes ORDER BY id DESC");
?>

<h2>Catálogo: Clientes</h2>
<?php echo $mensaje; ?>

<div class="form-container">
    <h3><?php echo $modo_edicion ? 'Editar Cliente' : 'Añadir Nuevo Cliente'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="id_editar" value="<?php echo htmlspecialchars($cli_editar['id']); ?>">
        <div class="form-grid">
            <div>
                <label>Empresa:</label>
                <input type="text" name="nombre_empresa" value="<?php echo htmlspecialchars($cli_editar['nombre_empresa']); ?>" required>
            </div>
            <div>
                <label>Contacto:</label>
                <input type="text" name="contacto" value="<?php echo htmlspecialchars($cli_editar['contacto']); ?>">
            </div>
            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($cli_editar['telefono']); ?>">
            </div>
            <div>
                <label>Dirección:</label>
                <input type="text" name="direccion" value="<?php echo htmlspecialchars($cli_editar['direccion']); ?>">
            </div>
        </div>
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/clientes" style="margin-left: 15px; color: #cc0000;">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <h3>Lista de Clientes</h3>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Empresa</th><th>Contacto</th><th>Teléfono</th><th>Dirección</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                        <td><?php echo htmlspecialchars($row['contacto']); ?></td>
                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                        <td>
                            <a href="panel.php?vista=catalogos/clientes&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                            <a href="panel.php?vista=catalogos/clientes&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">No hay clientes registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>