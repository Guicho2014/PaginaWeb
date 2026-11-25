<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

/**
 * Procesa la actualización del estado de una orden de producción vía POST.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 */
function procesarActualizacionEstado(mysqli $conn): void {
    // Comprueba si se envió el formulario para actualizar el estado.
    if (isset($_POST['actualizar_estado'])) {
        
        // Obtiene y sanitiza el ID de la orden.
        $id_orden = intval($_POST['id_orden']);
        
        // Obtiene el nuevo estado seleccionado.
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Define la consulta SQL preparada para actualizar el estado.
        $sql = "UPDATE produccion_ordenes SET estado = ? WHERE id = ?";
        
        // Prepara y ejecuta la consulta de forma segura.
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nuevo_estado, $id_orden);
        $stmt->execute();
        $stmt->close();
        
        // Redirecciona para evitar reenvíos de formularios al recargar.
        header("Location: " . BASE_PATH . "/panel.php?vista=funcionalidades/seguimiento");
        exit();
    }
}

/**
 * Obtiene el listado completo de órdenes de producción con su producto asociado.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con las órdenes de producción.
 */
function obtenerOrdenesSeguimiento(mysqli $conn): mysqli_result {
    $sql = "SELECT o.id, o.folio_orden, p.nombre as producto, o.cantidad_programada, o.estado 
              FROM produccion_ordenes o 
              LEFT JOIN cat_productos p ON o.id_producto = p.id 
              ORDER BY o.id DESC";
    return $conn->query($sql);
}

// Ejecuta la función de actualización de estado si se envió el formulario.
procesarActualizacionEstado($conn);

// Obtiene el listado de órdenes para el seguimiento.
$ordenes = obtenerOrdenesSeguimiento($conn);
?>

<!-- Título de la sección de seguimiento de producción -->
<h2>Seguimiento de Producción</h2>

<!-- Contenedor de la tabla que muestra el estado de las órdenes -->
<div class="table-container">
    <h3>Estado de Órdenes</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Estado Actual</th>
                <th>Cambiar Estado</th>
            </tr>
        </thead>
        <tbody>
            <!-- Comprueba si existen órdenes registradas -->
            <?php if ($ordenes && $ordenes->num_rows > 0): ?>
                
                <!-- Recorre cada registro de orden de producción obtenido -->
                <?php while($row = $ordenes->fetch_assoc()): ?>
                    <tr>
                        <!-- Muestra el folio protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                        
                        <!-- Muestra el producto protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['producto']); ?></td>
                        
                        <!-- Muestra la cantidad programada -->
                        <td><?php echo $row['cantidad_programada']; ?></td>
                        
                        <!-- Muestra el estado actual -->
                        <td><strong><?php echo htmlspecialchars($row['estado']); ?></strong></td>
                        
                        <!-- Formulario en línea para cambiar el estado de la orden -->
                        <td>
                            <form method="POST" style="display: flex; gap: 5px;">
                                <!-- Campo oculto con el ID de la orden -->
                                <input type="hidden" name="id_orden" value="<?php echo $row['id']; ?>">
                                
                                <!-- Selector con las opciones de estado -->
                                <select name="nuevo_estado" style="padding: 3px;">
                                    <option value="Programada" <?php echo ($row['estado'] == 'Programada') ? 'selected' : ''; ?>>Programada</option>
                                    <option value="En Proceso" <?php echo ($row['estado'] == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                                    <option value="Terminada" <?php echo ($row['estado'] == 'Terminada') ? 'selected' : ''; ?>>Terminada</option>
                                    <option value="Cancelada" <?php echo ($row['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                                
                                <!-- Botón de envío para guardar el cambio -->
                                <button type="submit" name="actualizar_estado" class="btn-save" style="padding: 5px 10px; font-size: 12px;">OK</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <!-- Muestra una fila alternativa si la tabla está vacía -->
                <tr><td colspan="5" style="text-align: center;">No hay órdenes registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>