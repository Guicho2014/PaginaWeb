<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';
$modo_edicion = false;
$prov_editar = ['id' => '', 'nombre_empresa' => '', 'contacto' => '', 'telefono' => '', 'correo' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre_empresa = trim($_POST['nombre_empresa']);
    $contacto = trim($_POST['contacto']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0;
    
    if (!empty($nombre_empresa)) {
        if ($id_editar > 0) {
            $sql = "UPDATE cat_proveedores SET nombre_empresa=?, contacto=?, telefono=?, correo=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nombre_empresa, $contacto, $telefono, $correo, $id_editar);
            $accion = "actualizado";
        } else {
            $sql = "INSERT INTO cat_proveedores (nombre_empresa, contacto, telefono, correo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nombre_empresa, $contacto, $telefono, $correo);
            $accion = "agregado";
        }
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Proveedor $accion correctamente.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al guardar.</div>";
        }
        $stmt->close();
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $sql = "DELETE FROM cat_proveedores WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/proveedores");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: este proveedor está en uso.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM cat_proveedores WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $prov_editar = $res->fetch_assoc();
        $modo_edicion = true;
    }
    $stmt->close();
}

$resultado = $conn->query("SELECT * FROM cat_proveedores ORDER BY id DESC");
?>

<h2>Catálogo: Proveedores</h2>
<?php echo $mensaje; ?>

<div class="form-container">
    <h3><?php echo $modo_edicion ? 'Editar Proveedor' : 'Añadir Nuevo Proveedor'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="id_editar" value="<?php echo htmlspecialchars($prov_editar['id']); ?>">
        <div class="form-grid">
            <div>
                <label>Empresa:</label>
                <input type="text" name="nombre_empresa" value="<?php echo htmlspecialchars($prov_editar['nombre_empresa']); ?>" required>
            </div>
            <div>
                <label>Contacto:</label>
                <input type="text" name="contacto" value="<?php echo htmlspecialchars($prov_editar['contacto']); ?>">
            </div>
            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($prov_editar['telefono']); ?>">
            </div>
            <div>
                <label>Correo:</label>
                <input type="email" name="correo" value="<?php echo htmlspecialchars($prov_editar['correo']); ?>">
            </div>
        </div>
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>
        <?php if($modo_edicion): ?>
            <a href="panel.php?vista=catalogos/proveedores" style="margin-left: 15px; color: #cc0000;">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <h3>Lista de Proveedores</h3>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Empresa</th><th>Contacto</th><th>Teléfono</th><th>Correo</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                        <td><?php echo htmlspecialchars($row['contacto']); ?></td>
                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($row['correo']); ?></td>
                        <td>
                            <a href="panel.php?vista=catalogos/proveedores&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                            <a href="panel.php?vista=catalogos/proveedores&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">No hay proveedores registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>