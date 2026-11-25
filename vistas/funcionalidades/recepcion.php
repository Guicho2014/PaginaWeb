<?php
require_once BASE_PATH . '/includes/conexion.php';

$mensaje = '';

/**
 * Procesa la validación y aceptación de un pre-registro o aviso de donador vía GET.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarValidacionPreregistro(mysqli $conn): string {
    if (isset($_GET['validar_preregistro'])) {
        
        $id_pre = intval($_GET['validar_preregistro']);
        
        $res = $conn->query("SELECT * FROM pre_registros WHERE id = $id_pre AND estado = 'Pendiente'");
        
        if ($res && $res->num_rows > 0) {
            $pre = $res->fetch_assoc();
            
            $folio = "REC-" . date("YmdHis") . "-" . rand(100, 999);
            
            $id_empleado_recibe = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 1;
            
            $conn->begin_transaction();
            
            try {
                $sql = "INSERT INTO recepciones (folio, id_proveedor, id_materia_prima, cantidad, id_unidad, id_empleado_recibe) 
                        VALUES (?, NULL, ?, ?, 1, ?)";
                
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta.");
                }
                
                $stmt->bind_param("sidi", $folio, $pre['id_materia_prima'], $pre['cantidad_aproximada'], $id_empleado_recibe);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar la inserción.");
                }
                
                $stmt->close();
                
                $stmt2 = $conn->prepare("UPDATE pre_registros SET estado = 'Recibido' WHERE id = ?");
                
                if (!$stmt2) {
                    throw new Exception("Error al preparar la actualización.");
                }
                
                $stmt2->bind_param("i", $id_pre);
                
                if (!$stmt2->execute()) {
                    throw new Exception("Error al actualizar el estado.");
                }
                
                $stmt2->close();
                
                $conn->commit();
                
                return "<div class='alert success'>Recepción oficial generada con folio: $folio</div>";
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log($e->getMessage());
                return "<div class='alert error'>Error al generar la recepción.</div>";
            }
        }
    }
    
    return '';
}

/**
 * Procesa el rechazo de un pre-registro o aviso de donador vía GET.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarRechazoPreregistro(mysqli $conn): string {
    if (isset($_GET['rechazar_preregistro'])) {
        
        $id_pre = intval($_GET['rechazar_preregistro']);
        
        $sql = "UPDATE pre_registros SET estado = 'Rechazado' WHERE id = ? AND estado = 'Pendiente'";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            return "<div class='alert error'>Error al rechazar el aviso.</div>";
        }
        
        $stmt->bind_param("i", $id_pre);
        
        if ($stmt->execute()) {
            $stmt->close();
            return "<div class='alert success'>El aviso del donador ha sido rechazado.</div>";
        } else {
            $stmt->close();
            return "<div class='alert error'>Error al rechazar el aviso.</div>";
        }
    }
    
    return '';
}

/**
 * Actualiza el estado de un pre-registro específico.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @param int $id_pre ID del pre-registro.
 * @param string $nuevoEstado Nuevo estado a establecer.
 */
function actualizarEstadoPreRegistro(mysqli $conn, int $id_pre, string $nuevoEstado): void {
    $sql = "UPDATE pre_registros SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $nuevoEstado, $id_pre);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Procesa el formulario POST para registrar una entrada directa de recepción de proveedores.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarRecepcionDirecta(mysqli $conn): string {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recepcion_directa'])) {
        
        $folio = isset($_POST['folio']) ? trim($_POST['folio']) : '';
        $id_proveedor = isset($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : 0;
        $id_materia = isset($_POST['id_materia']) ? intval($_POST['id_materia']) : 0;
        $cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : 0;
        
        $id_unidad = 1;
        $id_empleado = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 1;

        if (empty($folio) || $id_proveedor <= 0 || $id_materia <= 0 || $cantidad <= 0) {
            return "<div class='alert error'>Datos inválidos. Revisa los campos.</div>";
        }

        $sql = "INSERT INTO recepciones (folio, id_proveedor, id_materia_prima, cantidad, id_unidad, id_empleado_recibe) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            return "<div class='alert error'>Error al registrar la recepción.</div>";
        }
        
        $stmt->bind_param("siidii", $folio, $id_proveedor, $id_materia, $cantidad, $id_unidad, $id_empleado);
        
        if ($stmt->execute()) {
            $stmt->close();
            return "<div class='alert success'>Recepción directa registrada correctamente.</div>";
        } else {
            $stmt->close();
            return "<div class='alert error'>Error al registrar la recepción.</div>";
        }
    }
    
    return '';
}

/**
 * Obtiene el catálogo completo de proveedores registrados.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result|null Resultado con los proveedores.
 */
function obtenerProveedores(mysqli $conn): ?mysqli_result {
    return $conn->query("SELECT id, nombre_empresa FROM cat_proveedores") ?: null;
}

/**
 * Obtiene el catálogo completo de materias primas registradas.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result|null Resultado con las materias primas.
 */
function obtenerMateriasPrimas(mysqli $conn): ?mysqli_result {
    return $conn->query("SELECT id, nombre FROM cat_materias_primas") ?: null;
}

/**
 * Obtiene el listado de pre-registros que se encuentran con estado 'Pendiente'.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result|null Resultado con los pre-registros pendientes.
 */
function obtenerPreRegistrosPendientes(mysqli $conn): ?mysqli_result {
    return $conn->query("SELECT * FROM pre_registros WHERE estado = 'Pendiente' ORDER BY id DESC") ?: null;
}

$mensaje_validacion = procesarValidacionPreregistro($conn);
if (!empty($mensaje_validacion)) {
    $mensaje = $mensaje_validacion;
}

$mensaje_rechazo = procesarRechazoPreregistro($conn);
if (!empty($mensaje_rechazo)) {
    $mensaje = $mensaje_rechazo;
}

$mensaje_directa = procesarRecepcionDirecta($conn);
if (!empty($mensaje_directa)) {
    $mensaje = $mensaje_directa;
}

$proveedores = obtenerProveedores($conn);
$materias = obtenerMateriasPrimas($conn);
$pre_registros = obtenerPreRegistrosPendientes($conn);
?>

<h2>Recepción de Materia Prima</h2>

<?php echo $mensaje; ?>

<div class="table-container">
    <h3>Avisos Pendientes de Donadores</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Donador</th>
                <th>Material</th>
                <th>Cantidad Aprox.</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pre_registros && $pre_registros->num_rows > 0): ?>
                
                <?php while($row = $pre_registros->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre_donador']); ?></td>
                        
                        <td>ID: <?php echo $row['id_materia_prima']; ?></td>
                        
                        <td><?php echo $row['cantidad_aproximada']; ?> kg</td>
                        
                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                        
                        <td>
                            <a href="panel.php?vista=funcionalidades/recepcion&validar_preregistro=<?php echo $row['id']; ?>" class="btn-save" style="text-decoration: none; padding: 5px 10px; font-size: 12px;">Recibir</a>
                            
                            <a href="panel.php?vista=funcionalidades/recepcion&rechazar_preregistro=<?php echo $row['id']; ?>" class="btn-delete" style="text-decoration: none; padding: 5px 10px; font-size: 12px; border: 1px solid #cc0000; margin-left: 5px;" onclick="return confirm('¿Seguro que deseas rechazar este aviso?');">Rechazar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">No hay avisos pendientes.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="form-container">
    <h3>Registrar Entrada Directa (Proveedores)</h3>
    
    <form method="POST" action="">
        <div class="form-grid">
            
            <div>
                <label>Folio de Recepción:</label>
                <input type="text" name="folio" placeholder="Ej. REC-001" required>
            </div>
            
            <div>
                <label>Proveedor:</label>
                <select name="id_proveedor" required>
                    <option value="">Selecciona...</option>
                    
                    <?php if ($proveedores): ?>
                        <?php while($row = $proveedores->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nombre_empresa']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label>Materia Prima:</label>
                <select name="id_materia" required>
                    <option value="">Selecciona...</option>
                    
                    <?php if ($materias): ?>
                        <?php while($row = $materias->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label>Cantidad (kg):</label>
                <input type="number" step="0.01" name="cantidad" required>
            </div>
        </div>
        
        <button type="submit" name="recepcion_directa" class="btn-save">Registrar Recepción Directa</button>
    </form>
</div>