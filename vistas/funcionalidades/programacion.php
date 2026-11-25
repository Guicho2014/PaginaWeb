<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

// Inicializa la variable para almacenar los mensajes de éxito o error.
$mensaje = '';

/**
 * Procesa el formulario POST para programar una nueva orden de producción.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarProgramacionOrden(mysqli $conn): string {
    // Comprueba si la petición es de tipo POST y se envió el botón de programar.
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['programar'])) {
        
        // Obtiene y limpia los espacios del folio de orden.
        $folio = isset($_POST['folio_orden']) ? trim($_POST['folio_orden']) : '';
        
        // Obtiene y sanitiza el ID del producto.
        $id_producto = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;
        
        // Obtiene y sanitiza la cantidad programada como número decimal.
        $cantidad = isset($_POST['cantidad_programada']) ? floatval($_POST['cantidad_programada']) : 0;
        
        // Obtiene la fecha de inicio.
        $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
        
        // Obtiene la fecha estimada de fin.
        $fecha_fin = isset($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : '';

        // Validamos que las fechas tengan el formato correcto (Y-m-d)
        $fecha_inicio_valida = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
        $fecha_fin_valida = DateTime::createFromFormat('Y-m-d', $fecha_fin);

        // Valida que el folio no esté vacío, y que el producto y la cantidad sean mayores a cero.
        if (!empty($folio) && $id_producto > 0 && $cantidad > 0 && $fecha_inicio_valida && $fecha_fin_valida) {
            
            // Define la consulta SQL preparada para insertar la orden con estado 'Programada'.
            $sql = "INSERT INTO produccion_ordenes (folio_orden, id_producto, cantidad_programada, fecha_inicio, fecha_fin_estimada, estado) 
                    VALUES (?, ?, ?, ?, ?, 'Programada')";
            
            // Prepara la consulta en la base de datos.
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                return "<div class='alert error'>Error interno al preparar la consulta.</div>";
            }
            
            // Vincula los parámetros a la consulta preparada (cadena, entero, decimal, cadena y cadena).
            $stmt->bind_param("sidss", $folio, $id_producto, $cantidad, $fecha_inicio, $fecha_fin);
            
            try {
                // Ejecuta la consulta y verifica si fue exitosa.
                if ($stmt->execute()) {
                    $stmt->close();
                    // Retorna el mensaje de éxito.
                    return "<div class='alert success'>Orden de producción <strong>$folio</strong> programada correctamente.</div>";
                }
            } catch (mysqli_sql_exception $e) {
                $stmt->close();
                
                // Maneja el error de duplicidad (código 1062) u otros errores.
                if ($e->getCode() == 1062) {
                    return "<div class='alert error'>Error: El folio de orden <strong>$folio</strong> ya existe. Usa uno diferente.</div>";
                } elseif ($e->getCode() == 1452) {
                    return "<div class='alert error'>Error: El producto seleccionado no es válido.</div>";
                } else {
                    error_log($e->getMessage());
                    return "<div class='alert error'>Error al programar la orden.</div>";
                }
            }
        } else {
            return "<div class='alert error'>Datos inválidos. Revisa los campos y las fechas.</div>";
        }
    }
    
    // Retorna una cadena vacía si no se envió el formulario o faltan datos.
    return '';
}

/**
 * Procesa la eliminación de una orden de producción mediante el parámetro GET 'eliminar'.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML en caso de que ocurra un error controlado.
 */
function procesarEliminacionOrden(mysqli $conn): string {
    // Comprueba si se recibió el parámetro GET 'eliminar'.
    if (isset($_GET['eliminar'])) {
        
        // Obtiene y sanitiza el ID de la orden a eliminar.
        $id_eliminar = intval($_GET['eliminar']);
        
        try {
            // Define la consulta SQL preparada para eliminar la orden.
            $sql = "DELETE FROM produccion_ordenes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta.");
            }
            
            $stmt->bind_param("i", $id_eliminar);
            $stmt->execute();
            $stmt->close();
            
            // Redirecciona para limpiar la URL tras una eliminación exitosa.
            header("Location: " . BASE_PATH . "/panel.php?vista=funcionalidades/programacion");
            exit();
            
        } catch (mysqli_sql_exception $e) {
            // Captura errores de restricción de clave foránea (código 1451).
            if ($e->getCode() == 1451) { 
                return "<div class='alert error'>No se puede eliminar: esta orden ya tiene asignaciones o registros de calidad.</div>";
            } else {
                error_log($e->getMessage());
                return "<div class='alert error'>Error al eliminar el registro.</div>";
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return "<div class='alert error'>Error interno.</div>";
        }
    }
    
    // Retorna una cadena vacía si no hay solicitud de eliminación.
    return '';
}

/**
 * Obtiene el catálogo completo de productos registrados.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result|null Resultado con los productos.
 */
function obtenerCatalogoProductos(mysqli $conn): ?mysqli_result {
    return $conn->query("SELECT id, nombre FROM cat_productos") ?: null;
}

/**
 * Obtiene el listado completo de órdenes de producción con la información de su producto asociado mediante LEFT JOIN.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result|null Resultado con las órdenes de producción.
 */
function obtenerHistorialOrdenesProduccion(mysqli $conn): ?mysqli_result {
    $sql = "SELECT o.id, o.folio_orden, p.nombre as producto, o.cantidad_programada, o.fecha_inicio, o.fecha_fin_estimada, o.estado 
              FROM produccion_ordenes o 
              LEFT JOIN cat_productos p ON o.id_producto = p.id 
              ORDER BY o.id DESC";
    return $conn->query($sql) ?: null;
}

// Ejecuta la función de programación y guarda el resultado si aplica.
$mensaje = procesarProgramacionOrden($conn);

// Ejecuta la función de eliminación (si hay solicitud GET) y captura posibles errores de restricción.
$mensaje_eliminacion = procesarEliminacionOrden($conn);
if (!empty($mensaje_eliminacion)) {
    $mensaje = $mensaje_eliminacion;
}

// Obtiene el catálogo de productos para llenar el selector.
$productos = obtenerCatalogoProductos($conn);

// Obtiene el listado de órdenes de producción para mostrar en la tabla.
$ordenes = obtenerHistorialOrdenesProduccion($conn);
?>

<!-- Título de la sección de programación de producción -->
<h2>Programación de Producción</h2>

<!-- Imprime el mensaje de éxito o error generado -->
<?php echo $mensaje; ?>

<!-- Contenedor principal del formulario de nueva orden de producción -->
<div class="form-container">
    <h3>Nueva Orden de Producción</h3>
    
    <!-- Formulario para enviar los datos por método POST -->
    <form method="POST" action="">
        <div class="form-grid">
            
            <!-- Campo de texto para el Folio de Orden -->
            <div>
                <label>Folio de Orden:</label>
                <input type="text" name="folio_orden" placeholder="Ej. OP-001" required>
            </div>
            
            <!-- Campo de selección para el Producto a Fabricar -->
            <div>
                <label>Producto a Fabricar:</label>
                <select name="id_producto" required>
                    <option value="">Selecciona un producto...</option>
                    
                    <!-- Recorre los productos disponibles -->
                    <?php if ($productos): ?>
                        <?php while($row = $productos->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <!-- Campo numérico para la Cantidad Programada -->
            <div>
                <label>Cantidad Programada:</label>
                <input type="number" step="0.01" name="cantidad_programada" required>
            </div>
            
            <!-- Campo de fecha para la Fecha de Inicio -->
            <div>
                <label>Fecha de Inicio:</label>
                <input type="date" name="fecha_inicio" required>
            </div>
            
            <!-- Campo de fecha para la Fecha Estimada de Fin -->
            <div>
                <label>Fecha Estimada de Fin:</label>
                <input type="date" name="fecha_fin_estimada" required>
            </div>
        </div>
        
        <!-- Botón para enviar el formulario de programación -->
        <button type="submit" name="programar" class="btn-save">Programar Orden</button>
    </form>
</div>

<!-- Contenedor de la tabla que muestra las órdenes de producción -->
<div class="table-container">
    <h3>Órdenes de Producción</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Inicio</th>
                <th>Fin Estimado</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Comprueba si existen órdenes registradas -->
            <?php if ($ordenes && $ordenes->num_rows > 0): ?>
                
                <!-- Recorre cada registro de orden de producción obtenido -->
                <?php while($row = $ordenes->fetch_assoc()): ?>
                    <tr>
                        <!-- Muestra el folio de orden protegido contra XSS -->
                        <td><strong><?php echo htmlspecialchars($row['folio_orden']); ?></strong></td>
                        
                        <!-- Muestra el nombre del producto protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['producto'] ?? 'Sin producto'); ?></td>
                        
                        <!-- Muestra la cantidad programada -->
                        <td><?php echo $row['cantidad_programada']; ?></td>
                        
                        <!-- Muestra la fecha de inicio -->
                        <td><?php echo htmlspecialchars($row['fecha_inicio']); ?></td>
                        
                        <!-- Muestra la fecha estimada de fin -->
                        <td><?php echo htmlspecialchars($row['fecha_fin_estimada']); ?></td>
                        
                        <!-- Muestra el estado de la orden -->
                        <td><span style="padding: 3px 8px; border: 1px solid #000; font-size: 12px;"><?php echo htmlspecialchars($row['estado']); ?></span></td>
                        
                        <!-- Botón de acción para eliminar la orden -->
                        <td>
                            <a href="panel.php?vista=funcionalidades/programacion&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro que deseas eliminar esta orden?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <!-- Muestra una fila alternativa si la tabla está vacía -->
                <tr><td colspan="7" style="text-align: center;">No hay órdenes de producción programadas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>