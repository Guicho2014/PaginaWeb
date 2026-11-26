<?php
require_once BASE_PATH . '/includes/conexion.php';
require_once BASE_PATH . '/includes/funciones.php';

$mensaje = '';

// ========================================
// GUARDAR NUEVO DESPACHO
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $id_orden = intval($_POST['id_orden']);
    $id_cliente = intval($_POST['id_cliente']);
    $cantidad_despachada = floatval($_POST['cantidad_despachada']);
    $transportista = trim($_POST['transportista']);
    
    if ($id_orden > 0 && $id_cliente > 0 && $cantidad_despachada > 0) {
        $folio_despacho = generarFolio($conn, 'despachos', 'folio_despacho', 'DES');
        
        $sql = "INSERT INTO despachos (folio_despacho, id_orden, id_cliente, cantidad_despachada, transportista) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siids", $folio_despacho, $id_orden, $id_cliente, $cantidad_despachada, $transportista);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Despacho registrado correctamente con folio: <strong>$folio_despacho</strong></div>";
        } else {
            $mensaje = "<div class='alert error'>Error al registrar el despacho.</div>";
        }
        $stmt->close();
    } else {
        $mensaje = "<div class='alert error'>Por favor completa todos los campos obligatorios.</div>";
    }
}

// ========================================
// ELIMINAR DESPACHO
// ========================================
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    try {
        $sql = "DELETE FROM despachos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        
        header("Location: " . BASE_PATH . "/panel.php?vista=funcionalidades/despacho");
        exit();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alert error'>Error al eliminar.</div>";
    }
}

// ========================================
// LISTAR DESPACHOS
// ========================================
$sql_lista = "SELECT 
                d.id,
                d.folio_despacho,
                d.fecha_salida,
                d.cantidad_despachada,
                d.transportista,
                po.folio_orden,
                p.nombre as producto,
                u.abreviatura as unidad,
                c.nombre_empresa as cliente
              FROM despachos d
              LEFT JOIN produccion_ordenes po ON d.id_orden = po.id
              LEFT JOIN cat_productos p ON po.id_producto = p.id
              LEFT JOIN cat_unidades_medida u ON p.id_unidad = u.id
              LEFT JOIN cat_clientes c ON d.id_cliente = c.id
              ORDER BY d.fecha_salida DESC";
$resultado = $conn->query($sql_lista);

$ordenes = $conn->query("SELECT id, folio_orden FROM produccion_ordenes WHERE estado IN ('En Proceso', 'Completada') ORDER BY folio_orden DESC");
$clientes = $conn->query("SELECT id, nombre_empresa FROM cat_clientes ORDER BY nombre_empresa");
?>

<h2>Despacho de Productos</h2>
<p>Registra la salida de productos terminados hacia los clientes</p>

<?php echo $mensaje; ?>

<div class="form-container">
    <h3>Nuevo Despacho</h3>
    <form method="POST" action="">
        <div class="form-grid">
            <div>
                <label>Orden de Producción:</label>
                <select name="id_orden" required>
                    <option value="">Selecciona una orden...</option>
                    <?php while($o = $ordenes->fetch_assoc()): ?>
                        <option value="<?php echo $o['id']; ?>"><?php echo htmlspecialchars($o['folio_orden']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label>Cliente:</label>
                <select name="id_cliente" required>
                    <option value="">Selecciona un cliente...</option>
                    <?php while($c = $clientes->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre_empresa']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label>Cantidad Despachada:</label>
                <input type="number" step="0.01" name="cantidad_despachada" required min="0.01">
            </div>
            
            <div>
                <label>Transportista:</label>
                <input type="text" name="transportista" placeholder="Nombre del transportista o empresa">
            </div>
        </div>
        
        <button type="submit" name="guardar" class="btn-save">Registrar Despacho</button>
    </form>
</div>

<div class="table-container">
    <h3>Historial de Despachos</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Orden</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Cliente</th>
                <th>Transportista</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['folio_despacho']); ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_salida'])); ?></td>
                        <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                        <td><?php echo htmlspecialchars($row['producto']); ?></td>
                        <td><?php echo number_format($row['cantidad_despachada'], 2); ?> <?php echo htmlspecialchars($row['unidad']); ?></td>
                        <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($row['transportista'] ?? '-'); ?></td>
                        <td>
                            <a href="panel.php?vista=funcionalidades/despacho&eliminar=<?php echo $row['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('¿Seguro que deseas eliminar este despacho?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No hay despachos registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>