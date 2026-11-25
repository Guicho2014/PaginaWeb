<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

// Inicializa la variable para almacenar los mensajes de éxito o error.
$mensaje = '';

/**
 * Procesa el formulario POST para registrar una nueva asignación de producción.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return string Mensaje HTML con el resultado de la operación.
 */
function procesarAsignacion(mysqli $conn): string {
    // Comprueba si la petición es de tipo POST y se envió el botón de asignar.
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar'])) {
        
        // Obtiene y sanitiza el ID de la orden de producción.
        $id_orden = intval($_POST['id_orden']);
        
        // Obtiene y sanitiza el ID del área de producción.
        $id_area = intval($_POST['id_area']);
        
        // Obtiene y sanitiza el ID de la maquinaria.
        $id_maquinaria = intval($_POST['id_maquinaria']);
        
        // Obtiene y sanitiza el ID del empleado.
        $id_empleado = intval($_POST['id_empleado']);
        
        // Obtiene y limpia los espacios del turno asignado.
        $turno = trim($_POST['turno_asignado']);

        // Valida que todos los IDs seleccionados sean mayores a cero.
        if ($id_orden > 0 && $id_area > 0 && $id_maquinaria > 0 && $id_empleado > 0) {
            
            // Define la consulta SQL preparada para insertar la asignación.
            $sql = "INSERT INTO produccion_asignaciones (id_orden, id_area, id_maquinaria, id_empleado, turno_asignado) VALUES (?, ?, ?, ?, ?)";
            
            // Prepara la consulta en la base de datos.
            $stmt = $conn->prepare($sql);
            
            // Vincula los parámetros a la consulta preparada (4 enteros y 1 cadena).
            $stmt->bind_param("iiiis", $id_orden, $id_area, $id_maquinaria, $id_empleado, $turno);
            
            // Ejecuta la consulta y verifica si fue exitosa.
            if ($stmt->execute()) {
                
                // Actualiza el estado de la orden de producción a 'En Proceso'.
                actualizarEstadoOrdenEnProceso($conn, $id_orden);
                
                // Cierra la sentencia preparada.
                $stmt->close();
                
                // Retorna el mensaje de éxito.
                return "<div class='alert success'>Asignación registrada y orden puesta en proceso.</div>";
            } else {
                // Cierra la sentencia preparada en caso de error.
                $stmt->close();
                
                // Retorna el mensaje de error.
                return "<div class='alert error'>Error al asignar.</div>";
            }
        }
    }
    
    // Retorna una cadena vacía si no se envió el formulario o faltan datos.
    return '';
}

/**
 * Actualiza el estado de una orden de producción a 'En Proceso'.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @param int $id_orden ID de la orden de producción.
 */
function actualizarEstadoOrdenEnProceso(mysqli $conn, int $id_orden): void {
    // Ejecuta directamente la actualización del estado de la orden.
    $conn->query("UPDATE produccion_ordenes SET estado = 'En Proceso' WHERE id = $id_orden");
}

/**
 * Obtiene las órdenes de producción que tienen estado 'Programada'.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con las órdenes programadas.
 */
function obtenerOrdenesProgramadas(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, folio_orden FROM produccion_ordenes WHERE estado = 'Programada'");
}

/**
 * Obtiene el catálogo completo de áreas de producción.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con las áreas.
 */
function obtenerAreasProduccion(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, nombre FROM cat_areas_produccion");
}

/**
 * Obtiene el catálogo de maquinaria que se encuentra operativa.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con la maquinaria operativa.
 */
function obtenerMaquinariaOperativa(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, nombre FROM cat_maquinaria WHERE estado = 'Operativa'");
}

/**
 * Obtiene el catálogo completo de empleados registrados.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con los empleados.
 */
function obtenerEmpleados(mysqli $conn): mysqli_result {
    return $conn->query("SELECT id, nombre_completo FROM cat_empleados");
}

/**
 * Obtiene el historial completo de asignaciones con sus relaciones mediante LEFT JOIN.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con el historial de asignaciones.
 */
function obtenerHistorialAsignaciones(mysqli $conn): mysqli_result {
    $sql = "SELECT a.id, o.folio_orden, ar.nombre as area, m.nombre as maquina, e.nombre_completo as empleado, a.turno_asignado, a.fecha_asignacion 
              FROM produccion_asignaciones a 
              LEFT JOIN produccion_ordenes o ON a.id_orden = o.id 
              LEFT JOIN cat_areas_produccion ar ON a.id_area = ar.id 
              LEFT JOIN cat_maquinaria m ON a.id_maquinaria = m.id 
              LEFT JOIN cat_empleados e ON a.id_empleado = e.id 
              ORDER BY a.id DESC";
    return $conn->query($sql);
}

// Ejecuta la función de procesamiento de asignación y guarda el resultado.
$mensaje = procesarAsignacion($conn);

// Obtiene las órdenes programadas para llenar el selector.
$ordenes = obtenerOrdenesProgramadas($conn);

// Obtiene las áreas de producción para llenar el selector.
$areas = obtenerAreasProduccion($conn);

// Obtiene la maquinaria operativa para llenar el selector.
$maquinaria = obtenerMaquinariaOperativa($conn);

// Obtiene los empleados para llenar el selector.
$empleados = obtenerEmpleados($conn);

// Obtiene el historial de asignaciones para mostrar en la tabla.
$asignaciones = obtenerHistorialAsignaciones($conn);
?>

<!-- Título de la sección de asignación de producción -->
<h2>Asignación de Producción</h2>

<!-- Imprime el mensaje de éxito o error generado -->
<?php echo $mensaje; ?>

<!-- Contenedor principal del formulario de nueva asignación -->
<div class="form-container">
    <h3>Nueva Asignación</h3>
    
    <!-- Formulario para enviar los datos por método POST -->
    <form method="POST" action="">
        <div class="form-grid">
            
            <!-- Campo de selección para la Orden de Producción -->
            <div>
                <label>Orden de Producción:</label>
                <select name="id_orden" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre las órdenes programadas disponibles -->
                    <?php while($row = $ordenes->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['folio_orden']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para el Área -->
            <div>
                <label>Área:</label>
                <select name="id_area" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre las áreas de producción disponibles -->
                    <?php while($row = $areas->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para la Maquinaria -->
            <div>
                <label>Maquinaria:</label>
                <select name="id_maquinaria" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre la maquinaria operativa disponible -->
                    <?php while($row = $maquinaria->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para el Empleado -->
            <div>
                <label>Empleado:</label>
                <select name="id_empleado" required>
                    <option value="">Selecciona...</option>
                    
                    <!-- Recorre los empleados registrados -->
                    <?php while($row = $empleados->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre_completo']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campo de selección para el Turno -->
            <div>
                <label>Turno:</label>
                <select name="turno_asignado">
                    <option value="Matutino">Matutino</option>
                    <option value="Vespertino">Vespertino</option>
                    <option value="Nocturno">Nocturno</option>
                </select>
            </div>
        </div>
        
        <!-- Botón para enviar el formulario de asignación -->
        <button type="submit" name="asignar" class="btn-save">Asignar Orden</button>
    </form>
</div>

<!-- Contenedor de la tabla que muestra el historial de asignaciones -->
<div class="table-container">
    <h3>Historial de Asignaciones</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Orden</th>
                <th>Área</th>
                <th>Máquina</th>
                <th>Empleado</th>
                <th>Turno</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <!-- Comprueba si existen registros en el historial de asignaciones -->
            <?php if ($asignaciones && $asignaciones->num_rows > 0): ?>
                
                <!-- Recorre cada registro de asignación obtenido -->
                <?php while($row = $asignaciones->fetch_assoc()): ?>
                    <tr>
                        <!-- Muestra el folio de la orden protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                        
                        <!-- Muestra el nombre del área protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['area']); ?></td>
                        
                        <!-- Muestra el nombre de la máquina protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['maquina']); ?></td>
                        
                        <!-- Muestra el nombre del empleado protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['empleado']); ?></td>
                        
                        <!-- Muestra el turno asignado protegido contra XSS -->
                        <td><?php echo htmlspecialchars($row['turno_asignado']); ?></td>
                        
                        <!-- Muestra la fecha de asignación -->
                        <td><?php echo $row['fecha_asignacion']; ?></td>
                    </tr>
                <?php endwhile; ?>
                
            <?php else: ?>
                <!-- Muestra una fila alternativa si la tabla está vacía -->
                <tr><td colspan="6" style="text-align: center;">No hay asignaciones.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>