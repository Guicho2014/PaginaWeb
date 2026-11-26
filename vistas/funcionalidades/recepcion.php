<?php
require_once BASE_PATH . '/includes/conexion.php';
require_once BASE_PATH . '/includes/funciones.php';

$mensaje = '';

// ========================================
// GUARDAR NUEVA RECEPCIÓN
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $id_proveedor = intval($_POST['id_proveedor']);
    $id_materia_prima = intval($_POST['id_materia_prima']);
    $cantidad = floatval($_POST['cantidad']);
    $id_unidad = intval($_POST['id_unidad']);
    $id_empleado_recibe = intval($_POST['id_empleado_recibe']);
    
    if ($id_proveedor > 0 && $id_materia_prima > 0 && $cantidad > 0 && $id_unidad > 0 && $id_empleado_recibe > 0) {
        // Generar folio automático
        $folio = generarFolio($conn, 'recepciones', 'folio', 'REC');
        
        $sql = "INSERT INTO recepciones (folio, id_proveedor, id_materia_prima, cantidad, id_unidad, id_empleado_recibe) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siidii", $folio, $id_proveedor, $id_materia_prima, $cantidad, $id_unidad, $id_empleado_recibe);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'>Recepción registrada correctamente con folio: <strong>$folio</strong></div>";
        } else {
            $mensaje = "<div class='alert error'>Error al registrar la recepción.</div>";
        }
        $stmt->close();
    } else {
        $mensaje = "<div class='alert error'>Por favor completa todos los campos obligatorios.</div>";
    }
}

// ========================================
// ELIMINAR RECEPCIÓN
// ========================================
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    try {
        $sql = "DELETE FROM recepciones WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $stmt->close();
        
        header("Location: " . BASE_PATH . "/panel.php?vista=funcionalidades/recepcion");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: esta recepción ya tiene registros asociados.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar.</div>";
        }
    }
}

// ========================================
// LISTAR RECEPCIONES
// ========================================
$sql_lista = "SELECT 
                r.id,
                r.folio,
                r.fecha_recepcion,
                r.cantidad,
                p.nombre_empresa as proveedor,
                mp.nombre as materia_prima,
                u.abreviatura as unidad,
                e.nombre_completo as empleado
              FROM recepciones r
              LEFT JOIN cat_proveedores p ON r.id_proveedor = p.id
              LEFT JOIN cat_materias_primas mp ON r.id_materia_prima = mp.id
              LEFT JOIN cat_unidades_medida u ON r.id_unidad = u.id
              LEFT JOIN cat_empleados e ON r.id_empleado_recibe = e.id
              ORDER BY r.fecha_recepcion DESC";
$resultado = $conn->query($sql_lista);

// Obtener datos para los selects
$proveedores = $conn->query("SELECT id, nombre_empresa FROM cat_proveedores ORDER BY nombre_empresa");
$materias_primas = $conn->query("SELECT id, nombre FROM cat_materias_primas ORDER BY nombre");
$unidades = $conn->query("SELECT id, nombre, abreviatura FROM cat_unidades_medida ORDER BY nombre");
$empleados = $conn->query("SELECT id, nombre_completo FROM cat_empleados WHERE estado = 'Activo' ORDER BY nombre_completo");
?>

<h2>📥 Recepción de Materiales</h2>
<p>Registra la entrada de materiales a la planta procesadora</p>

<?php echo $mensaje; ?>

<!-- Formulario de Registro -->
<div class="form-container">
    <h3>Nueva Recepción</h3>
    <form method="POST" action="">
        <div class="form-grid">
            <div>
                <label>Proveedor:</label>
                <select name="id_proveedor" required>
                    <option value="">Selecciona un proveedor...</option>
                    <?php while($p = $proveedores->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_empresa']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label>Materia Prima:</label>
                <select name="id_materia_prima" required>
                    <option value="">Selecciona un material...</option>
                    <?php while($mp = $materias_primas->fetch_assoc()): ?>
                        <option value="<?php echo $mp['id']; ?>"><?php echo htmlspecialchars($mp['nombre']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label>Cantidad:</label>
                <input type="number" step="0.01" name="cantidad" required min="0.01">
            </div>
            
            <div>
                <label>Unidad de Medida:</label>
                <select name="id_unidad" required>
                    <option value="">Selecciona una unidad...</option>
                    <?php while($u = $unidades->fetch_assoc()): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre'] . ' (' . $u['abreviatura'] . ')'); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label>Empleado que Recibe:</label>
                <select name="id_empleado_recibe" required>
                    <option value="">Selecciona un empleado...</option>
                    <?php while($e = $empleados->fetch_assoc()): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <button type="submit" name="guardar" class="btn-save">Registrar Recepción</button>
    </form>
</div>

<!-- Tabla de Recepciones -->
<div class="table-container">
    <h3>Historial de Recepciones</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Material</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th>Recibió</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['folio']); ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_recepcion'])); ?></td>
                        <td><?php echo htmlspecialchars($row['proveedor']); ?></td>
                        <td><?php echo htmlspecialchars($row['materia_prima']); ?></td>
                        <td><?php echo number_format($row['cantidad'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['unidad']); ?></td>
                        <td><?php echo htmlspecialchars($row['empleado']); ?></td>
                        <td>
                            <a href="panel.php?vista=funcionalidades/recepcion&eliminar=<?php echo $row['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('¿Seguro que deseas eliminar esta recepción?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No hay recepciones registradas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>