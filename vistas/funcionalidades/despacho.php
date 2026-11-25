<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

// Inicializa la variable para almacenar los mensajes de éxito o error.
$mensaje = '';

/**
 * Procesa el formulario POST para registrar un nuevo despacho de productos.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarDespacho(mysqli $conn): string {
    // Comprueba si la petición es de tipo POST y se envió el botón de despachar.
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['despachar'])) {
        
        // Obtiene y limpia los espacios del folio de despacho.
        $folio = trim($_POST['folio_despacho']);
        
        // Obtiene y sanitiza el ID de la orden de producción.
        $id_orden = intval($_POST['id_orden']);
        
        // Obtiene y sanitiza el ID del cliente.
        $id_cliente = intval($_POST['id_cliente']);
        
        // Obtiene y sanitiza la cantidad despachada como número decimal.
        $cantidad = floatval($_POST['cantidad']);
        
        // Obtiene y limpia los espacios del nombre del transportista.
        $transportista = trim($_POST['transportista']);

        // Valida que el folio no esté vacío y que los IDs sean mayores a cero.
        if (!empty($folio) && $id_orden > 0 && $id_cliente > 0) {
            
            // Define la consulta SQL preparada para insertar el despacho.
            $sql = "INSERT INTO despachos (folio_despacho, id_orden, id_cliente, cantidad_despachada, transportista) VALUES (?, ?, ?, ?, ?)";
            
            // Prepara la consulta en la base de datos.
            $stmt = $conn->prepare($sql);
            
            // Vincula los parámetros a la consulta preparada (cadena, 2 enteros, decimal y cadena).
            $stmt->bind_param("siids", $folio, $id_orden, $id_cliente, $cantidad, $transportista);
            
            // Ejecuta la consulta y verifica si fue exitosa.
            if ($stmt->execute()) {
                
                // Actualiza el estado de la orden de producción a 'Terminada'.
                actualizarEstadoOrdenTerminada($conn, $id_orden);
                
                // Cierra la sentencia preparada.
                $stmt->close();
                
                // Retorna el mensaje de éxito.
                return "<div class='alert success'>Despacho registrado. La orden se ha marcado como Terminada.</div>";
            } else {
                // Cierra la sentencia preparada en caso de error.
                $stmt->close();
                
                // Retorna el mensaje de error.
                return "<div class='alert error'>Error al registrar despacho.</div>";
            }
        }
    }
    
    // Retorna una cadena vacía si no se envió el formulario o faltan datos.
    return '';
}

/**
 * Actualiza el estado de una orden de producción a 'Terminada'.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @param int $id_orden ID de la orden de producción.
 */
function actualizarEstadoOrdenTerminada(mysqli $conn, int $id_orden): void {
    // Ejecuta directamente la actualización del estado de la orden.
    $conn->query("UPDATE produccion_ordenes SET estado = 'Terminada' WHERE id = $id_orden");
}

/**
 * Obtiene las órdenes de producción que no están canceladas.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con las órdenes disponibles.
 */
function obtenerOrdenesDisponibles(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, folio_orden FROM produccion_ordenes WHERE estado != 'Cancelada'");
}

/**
 * Obtiene el catálogo completo de clientes registrados.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con los clientes.
 */
function obtenerClientes(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, nombre_empresa FROM cat_clientes");
}

/**
 * Obtiene el historial completo de despachos con sus relaciones mediante LEFT JOIN.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con el historial de despachos.
 */
function obtenerHistorialDespachos(mysqli $conn): mysqli_result {
    $sql = "SELECT d.id, d.folio_despacho, o.folio_orden, c.nombre_empresa as cliente, d.cantidad_despachada, d.transportista, d.fecha_salida 
                           FROM despachos d 
                           LEFT JOIN produccion_ordenes o ON d.id_orden = o.id 
                           LEFT JOIN cat_clientes c ON d.id_cliente = c.id 
                           ORDER BY d.id DESC";
    return $conn->query($sql);
}

// Ejecuta la función de procesamiento de despacho y guarda el resultado.
$mensaje = procesarDespacho($conn);

// Obtiene las órdenes disponibles para llenar el selector.
$ordenes = obtenerOrdenesDisponibles($conn);

// Obtiene los clientes para llenar el selector.
$clientes = obtenerClientes($conn);

// Obtiene el historial de despachos para mostrar en la tabla.
$despachos = obtenerHistorialDespachos($conn);
?>

<!-- Título de la sección de despacho de productos -->
<h2>Despacho de Productos</h2>

<!-- Imprime el mensaje de éxito o error generado -->
<?php echo $mensaje; ?>

<!-- Contenedor principal del formulario de registro de salida -->
<div class="form-container">
    <h3>Registrar Salida</h3>
    
    <!-- Formulario para enviar los datos por método POST -->
    <form method="POST" action="">
        <div class="form-grid">
            
            <!-- Campo de texto para el Folio de Despacho -->
            <div>
                <label>Folio de Despacho:</label>
                <input type="text" name="folio_despacho" placeholder="Ej. DES-001" required>
            </div>
            
            <!-- Campo de selección para la Orden de Producción -->
            <div>
                <label>Orden de Producción:</label>
                <select name="id_orden" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre las órdenes disponibles -->
                    <?php while($row = $ordenes->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['folio_orden']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para el Cliente -->
            <div>
                <label>Cliente:</label>
                <select name="id_cliente" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre los clientes registrados -->
                    <?php while($row = $clientes->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre_empresa']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo numérico para la Cantidad Despachada -->
            <div>
                <label>Cantidad Despachada:</label>
                <input type="number" step="0.01" name="cantidad" required>
            </div>
            
            <!-- Campo de texto para el Transportista -->
            <div>
                <label>Transportista:</label>
                <input type="text" name="transportista">
            </div>
        </div>
        
        <!-- Botón para enviar el formulario de despacho -->
        <button type="submit" name="despachar" class="btn-save">Registrar Despacho</button>
    </form>
</div>

<!-- Contenedor de la tabla que muestra el historial de despachos -->
<div class="table-container">
    <h3>Historial de Despachos</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Orden</th>
                <th>Cliente</th>
                <th>Cantidad</th>
                <th>Transportista</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <!-- Comprueba si existen registros en el historial de despachos -->
            <?php if ($despachos && $despachos->num_rows > 0): ?>
                
                <!-- Recorre cada registro de despacho obtenido -->
                <?php while($row = $despachos->fetch_assoc()): ?>
                    <tr>
                        <!-- Muestra el folio de despacho protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['folio_despacho']); ?></td>
                        
                        <!-- Muestra el folio de la orden protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                        
                        <!-- Muestra el nombre del cliente protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                        
                        <!-- Muestra la cantidad despachada -->
                        <td><?php echo $row['cantidad_despachada']; ?></td>
                        
                        <!-- Muestra el transportista protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['transportista']); ?></td>
                        
                        <!-- Muestra la fecha de salida -->
                        <td><?php echo $row['fecha_salida']; ?></td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <!-- Muestra una fila alternativa si la tabla está vacía -->
                <tr><td colspan="6" style="text-align: center;">No hay despachos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>