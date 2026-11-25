<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

// Inicializa la variable para almacenar los mensajes de éxito o error.
$mensaje = '';

/**
 * Procesa el formulario POST para registrar una nueva inspección de control de calidad.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarInspeccion(mysqli $conn): string {
    // Comprueba si la petición es de tipo POST y se envió el botón de inspeccionar.
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inspeccionar'])) {
        
        // Obtiene y sanitiza el ID de la orden de producción.
        $id_orden = intval($_POST['id_orden']);
        
        // Obtiene y sanitiza el ID del empleado inspector.
        $id_inspector = intval($_POST['id_inspector']);
        
        // Obtiene el resultado de la inspección.
        $resultado = $_POST['resultado'];
        
        // Obtiene y limpia los espacios de las observaciones.
        $observaciones = trim($_POST['observaciones']);

        // Valida que los IDs sean mayores a cero.
        if ($id_orden > 0 && $id_inspector > 0) {
            
            // Define la consulta SQL preparada para insertar la inspección.
            $sql = "INSERT INTO control_calidad (id_orden, id_empleado_inspector, resultado, observaciones) VALUES (?, ?, ?, ?)";
            
            // Prepara la consulta en la base de datos.
            $stmt = $conn->prepare($sql);
            
            // Vincula los parámetros a la consulta preparada (2 enteros y 2 cadenas).
            $stmt->bind_param("iiss", $id_orden, $id_inspector, $resultado, $observaciones);
            
            // Ejecuta la consulta y verifica si fue exitosa.
            if ($stmt->execute()) {
                // Cierra la sentencia preparada.
                $stmt->close();
                
                // Retorna el mensaje de éxito.
                return "<div class='alert success'>Inspección de calidad registrada.</div>";
            } else {
                // Cierra la sentencia preparada en caso de error.
                $stmt->close();
                
                // Retorna el mensaje de error.
                return "<div class='alert error'>Error al registrar inspección.</div>";
            }
        }
    }
    
    // Retorna una cadena vacía si no se envió el formulario o faltan datos.
    return '';
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
 * Obtiene el catálogo completo de empleados registrados.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con los empleados.
 */
function obtenerEmpleadosInspectores(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, nombre_completo FROM cat_empleados");
}

/**
 * Obtiene el historial completo de inspecciones de calidad con sus relaciones mediante LEFT JOIN.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con el historial de inspecciones.
 */
function obtenerHistorialInspecciones(mysqli $conn): mysqli_result {
    $sql = "SELECT c.id, o.folio_orden, e.nombre_completo as inspector, c.resultado, c.observaciones, c.fecha_inspeccion 
              FROM control_calidad c 
              LEFT JOIN produccion_ordenes o ON c.id_orden = o.id 
              LEFT JOIN cat_empleados e ON c.id_empleado_inspector = e.id 
              ORDER BY c.id DESC";
    return $conn->query($sql);
}

// Ejecuta la función de procesamiento de inspección y guarda el resultado.
$mensaje = procesarInspeccion($conn);

// Obtiene las órdenes disponibles para llenar el selector.
$ordenes = obtenerOrdenesDisponibles($conn);

// Obtiene los empleados para llenar el selector de inspectores.
$empleados = obtenerEmpleadosInspectores($conn);

// Obtiene el historial de inspecciones para mostrar en la tabla.
$inspecciones = obtenerHistorialInspecciones($conn);
?>

<!-- Título de la sección de control de calidad -->
<h2>Control de Calidad</h2>

<!-- Imprime el mensaje de éxito o error generado -->
<?php echo $mensaje; ?>

<!-- Contenedor principal del formulario de registro de inspección -->
<div class="form-container">
    <h3>Registrar Inspección</h3>
    
    <!-- Formulario para enviar los datos por método POST -->
    <form method="POST" action="">
        <div class="form-grid">
            
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
            
            <!-- Campo de selección para el Inspector -->
            <div>
                <label>Inspector:</label>
                <select name="id_inspector" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre los empleados registrados -->
                    <?php while($row = $empleados->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre_completo']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para el Resultado -->
            <div>
                <label>Resultado:</label>
                <select name="resultado" required>
                    <option value="Aprobado">Aprobado</option>
                    <option value="Rechazado">Rechazado</option>
                    <option value="En Revisión">En Revisión</option>
                </select>
            </div>
            
            <!-- Campo de texto para Observaciones -->
            <div>
                <label>Observaciones:</label>
                <input type="text" name="observaciones">
            </div>
        </div>
        
        <!-- Botón para enviar el formulario de inspección -->
        <button type="submit" name="inspeccionar" class="btn-save">Registrar Inspección</button>
    </form>
</div>

<!-- Contenedor de la tabla que muestra el historial de inspecciones -->
<div class="table-container">
    <h3>Historial de Inspecciones</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Orden</th>
                <th>Inspector</th>
                <th>Resultado</th>
                <th>Observaciones</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <!-- Comprueba si existen registros en el historial de inspecciones -->
            <?php if ($inspecciones && $inspecciones->num_rows > 0): ?>
                
                <!-- Recorre cada registro de inspección obtenido -->
                <?php while($row = $inspecciones->fetch_assoc()): ?>
                    <tr>
                        <!-- Muestra el folio de la orden protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                        
                        <!-- Muestra el nombre del inspector protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['inspector']); ?></td>
                        
                        <!-- Muestra el resultado protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['resultado']); ?></td>
                        
                        <!-- Muestra las observaciones protegidas contra XSS -->
                        <td><?php echo htmlspecialchars($row['observaciones']); ?></td>
                        
                        <!-- Muestra la fecha de la inspección -->
                        <td><?php echo $row['fecha_inspeccion']; ?></td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <!-- Muestra una fila alternativa si la tabla está vacía -->
                <tr><td colspan="5" style="text-align: center;">No hay inspecciones registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>