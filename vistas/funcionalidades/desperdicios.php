<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

// Inicializa la variable para almacenar los mensajes de éxito o error.
$mensaje = '';

/**
 * Procesa el formulario POST para registrar un nuevo desperdicio.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarDesperdicio(mysqli $conn): string {
    // Comprueba si la petición es de tipo POST y se envió el botón de registrar.
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar'])) {
        
        // Obtiene y sanitiza el ID de la orden de producción.
        $id_orden = intval($_POST['id_orden']);
        
        // Obtiene y sanitiza el ID del tipo de desperdicio.
        $id_tipo = intval($_POST['id_tipo']);
        
        // Obtiene y sanitiza la cantidad como número decimal.
        $cantidad = floatval($_POST['cantidad']);

        // Valida que los IDs y la cantidad sean mayores a cero.
        if ($id_orden > 0 && $id_tipo > 0 && $cantidad > 0) {
            
            // Define la consulta SQL preparada para insertar el registro de desperdicio.
            $sql = "INSERT INTO registro_desperdicios (id_orden, id_tipo_desperdicio, cantidad) VALUES (?, ?, ?)";
            
            // Prepara la consulta en la base de datos.
            $stmt = $conn->prepare($sql);
            
            // Vincula los parámetros a la consulta preparada (2 enteros y 1 decimal).
            $stmt->bind_param("iid", $id_orden, $id_tipo, $cantidad);
            
            // Ejecuta la consulta y verifica si fue exitosa.
            if ($stmt->execute()) {
                // Cierra la sentencia preparada.
                $stmt->close();
                
                // Retorna el mensaje de éxito.
                return "<div class='alert success'>Desperdicio registrado correctamente.</div>";
            } else {
                // Cierra la sentencia preparada en caso de error.
                $stmt->close();
                
                // Retorna el mensaje de error.
                return "<div class='alert error'>Error al registrar.</div>";
            }
        }
    }
    
    // Retorna una cadena vacía si no se envió el formulario o faltan datos.
    return '';
}

/**
 * Obtiene el listado completo de las órdenes de producción.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con las órdenes.
 */
function obtenerOrdenesProduccion(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, folio_orden FROM produccion_ordenes");
}

/**
 * Obtiene el catálogo de los tipos de desperdicio registrados.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con los tipos de desperdicio.
 */
function obtenerTiposDesperdicio(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, nombre FROM cat_tipos_desperdicio");
}

/**
 * Obtiene el historial completo de registros de desperdicios con sus relaciones mediante LEFT JOIN.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con el historial de desperdicios.
 */
function obtenerHistorialDesperdicios(mysqli $conn): mysqli_result {
    $sql = "SELECT d.id, o.folio_orden, t.nombre as tipo, d.cantidad, d.fecha_registro 
              FROM registro_desperdicios d 
              LEFT JOIN produccion_ordenes o ON d.id_orden = o.id 
              LEFT JOIN cat_tipos_desperdicio t ON d.id_tipo_desperdicio = t.id 
              ORDER BY d.id DESC";
    return $conn->query($sql);
}

// Ejecuta la función de procesamiento de desperdicio y guarda el resultado.
$mensaje = procesarDesperdicio($conn);

// Obtiene las órdenes de producción para llenar el selector.
$ordenes = obtenerOrdenesProduccion($conn);

// Obtiene los tipos de desperdicio para llenar el selector.
$tipos = obtenerTiposDesperdicio($conn);

// Obtiene el historial de desperdicios para mostrar en la tabla.
$desperdicios = obtenerHistorialDesperdicios($conn);
?>

<!-- Título de la sección de control de desperdicios -->
<h2>Control de Desperdicios</h2>

<!-- Imprime el mensaje de éxito o error generado -->
<?php echo $mensaje; ?>

<!-- Contenedor principal del formulario de registro de desperdicios -->
<div class="form-container">
    <h3>Registrar Desperdicio</h3>
    
    <!-- Formulario para enviar los datos por método POST -->
    <form method="POST" action="">
        <div class="form-grid">
            
            <!-- Campo de selección para la Orden de Producción -->
            <div>
                <label>Orden de Producción:</label>
                <select name="id_orden" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre las órdenes de producción disponibles -->
                    <?php while($row = $ordenes->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['folio_orden']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para el Tipo de Desperdicio -->
            <div>
                <label>Tipo de Desperdicio:</label>
                <select name="id_tipo" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre los tipos de desperdicio disponibles -->
                    <?php while($row = $tipos->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo numérico para la Cantidad en kg -->
            <div>
                <label>Cantidad (kg):</label>
                <input type="number" step="0.01" name="cantidad" required>
            </div>
        </div>
        
        <!-- Botón para enviar el formulario de registro -->
        <button type="submit" name="registrar" class="btn-save">Registrar Desperdicio</button>
    </form>
</div>

<!-- Contenedor de la tabla que muestra el historial de desperdicios -->
<div class="table-container">
    <h3>Historial de Desperdicios</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Orden</th>
                <th>Tipo</th>
                <th>Cantidad (kg)</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <!-- Comprueba si existen registros en el historial de desperdicios -->
            <?php if ($desperdicios && $desperdicios->num_rows > 0): ?>
                
                <!-- Recorre cada registro de desperdicio obtenido -->
                <?php while($row = $desperdicios->fetch_assoc()): ?>
                    <tr>
                        <!-- Muestra el folio de la orden protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                        
                        <!-- Muestra el tipo de desperdicio protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                        
                        <!-- Muestra la cantidad en kilogramos -->
                        <td><?php echo $row['cantidad']; ?> kg</td>
                        
                        <!-- Muestra la fecha de registro -->
                        <td><?php echo $row['fecha_registro']; ?></td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <!-- Muestra una fila alternativa si la tabla está vacía -->
                <tr><td colspan="4" style="text-align: center;">No hay registros.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>