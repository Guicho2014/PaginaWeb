<?php
require_once BASE_PATH . '/includes/conexion.php';
require_once BASE_PATH . '/includes/funciones.php';

$mensaje = '';

// ========================================
// GUARDAR NUEVA ORDEN DE PRODUCCIÓN
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $id_producto = intval($_POST['id_producto']);
    $cantidad_programada = floatval($_POST['cantidad_programada']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_fin_estimada = trim($_POST['fecha_fin_estimada']);
    $estado = trim($_POST['estado']);
    
    if ($id_producto > 0 && $cantidad_programada > 0 && !empty($fecha_inicio)) {
        $folio_orden = generarFolio($conn, 'produccion_ordenes', 'folio_orden', 'OP');
        
        $sql = "INSERT INTO produccion_ordenes (folio_orden, id_producto, cantidad_programada, fecha_inicio, fecha_fin_estimada, estado) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $fecha_fin = !empty($fecha_fin_estimada) ? $fecha_fin_estimada : null;
        $stmt->bind_param("sidiss", $folio_orden, $id_producto, $cantidad_programada, $fecha_inicio, $fecha_fin, $estado);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Orden de producción registrada correctamente con folio: <strong>$folio_orden</strong></div>";
        } else {
            $mensaje = "<div class='alert error'>Error al registrar la orden.</div>";
        }
        $stmt->close();
    } else {
        $mensaje = "<div class='alert error'>Por favor completa todos los campos obligatorios.</div>";
    }
}

// ========================================
// CAMBIAR ESTADO DE ORDEN
// ========================================
if (isset($_GET['cambiar_estado']) && isset($_GET['nuevo_estado'])) {
    $id_orden = intval($_GET['cambiar_estado']);
    $nuevo_estado = trim($_GET['nuevo_estado']);
    
    $estados_validos = ['Programada', 'En Proceso', 'Completada', 'Cancelada'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $sql = "UPDATE produccion_ordenes SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nuevo_estado, $id_orden);
        $stmt->execute();
        $stmt->close();
        
        header("Location: " . BASE_PATH . "/panel.php?vista=funcionalidades/programacion");
        exit();
    }
}

// ========================================
// ELIMINAR ORDEN
// ========================================
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    try {
        $sql = "DELETE FROM produccion_ordenes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        
        header("Location: " . BASE_PATH . "/panel.php?vista=funcionalidades/programacion");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: esta orden ya tiene registros asociados.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

// ========================================
// LISTAR ÓRDENES DE PRODUCCIÓN
// ========================================
$sql_lista = "SELECT 
                po.id,
                po.folio_orden,
                po.fecha_inicio,
                po.fecha_fin_estimada,
                po.cantidad_programada,
                po.estado,
                p.nombre as producto,
                u.abreviatura as unidad
              FROM produccion_ordenes po
              LEFT JOIN cat_productos p ON po.id_producto = p.id
              LEFT JOIN cat_unidades_medida u ON p.id_unidad = u.id
              ORDER BY po.fecha_inicio DESC";
$resultado = $conn->query($sql_lista);

$productos = $conn->query("SELECT id, nombre FROM cat_productos ORDER BY nombre");
?>

<h2>Programación de Producción</h2>
<p>Programa las órdenes de producción para la planta</p>

<?php echo $mensaje; ?>

<div class="form-container">
    <h3>Nueva Orden de Producción</h3>
    <form method="POST" action="">
        <div class="form-grid">
            <div>
                <label>Producto a Fabricar:</label>
                <select name="id_producto" required>
                    <option value="">Selecciona un producto...</option>
                    <?php while($p = $productos->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label>Cantidad Programada:</label>
                <input type="number" step="0.01" name="cantidad_programada" required min="0.01">
            </div>
            
            <div>
                <label>Fecha de Inicio:</label>
                <input type="date" name="fecha_inicio" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div>
                <label>Fecha Fin Estimada:</label>
                <input type="date" name="fecha_fin_estimada">
            </div>
            
            <div>
                <label>Estado Inicial:</label>
                <select name="estado">
                    <option value="Programada" selected>Programada</option>
                    <option value="En Proceso">En Proceso</option>
                </select>
            </div>
        </div>
        
        <button type="submit" name="guardar" class="btn-save">Programar Orden</button>
    </form>
</div>

<div class="table-container">
    <h3>Órdenes de Producción</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin Est.</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['folio_orden']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['producto']); ?></td>
                        <td><?php echo number_format($row['cantidad_programada'], 2); ?> <?php echo htmlspecialchars($row['unidad']); ?></td>
                        <td><?php echo !empty($row['fecha_inicio']) ? date('d/m/Y', strtotime($row['fecha_inicio'])) : '-'; ?></td>
                        <td><?php echo !empty($row['fecha_fin_estimada']) ? date('d/m/Y', strtotime($row['fecha_fin_estimada'])) : '-'; ?></td>
                        <td>
                            <select onchange="window.location.href='panel.php?vista=funcionalidades/programacion&cambiar_estado=<?php echo $row['id']; ?>&nuevo_estado='+this.value" style="padding: 5px;">
                                <option value="Programada" <?php echo ($row['estado'] == 'Programada') ? 'selected' : ''; ?>>Programada</option>
                                <option value="En Proceso" <?php echo ($row['estado'] == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="Completada" <?php echo ($row['estado'] == 'Completada') ? 'selected' : ''; ?>>Completada</option>
                                <option value="Cancelada" <?php echo ($row['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </td>
                        <td>
                            <a href="panel.php?vista=funcionalidades/programacion&eliminar=<?php echo $row['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('¿Seguro que deseas eliminar esta orden?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No hay órdenes de producción registradas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>