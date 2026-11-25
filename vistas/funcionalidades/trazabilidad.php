<?php
// Incluye el archivo de conexión a la base de datos utilizando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

// Obtiene y limpia el término de búsqueda enviado por GET, o inicializa vacío.
$buscar = isset($_GET['buscar_orden']) ? trim($_GET['buscar_orden']) : '';

/**
 * Busca órdenes de producción según el folio utilizando consultas preparadas y comodines.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @param string $termino Término de búsqueda para el folio de la orden.
 * @return mysqli_result|false Resultado de la consulta o falso en caso de error.
 */
function buscarOrdenesTrazabilidad(mysqli $conn, string $termino): mysqli_result|false {
    if (empty($termino)) {
        return false;
    }
    
    // Define la consulta SQL utilizando un patrón con comodines para LIKE de forma segura.
    $sql = "SELECT o.id, o.folio_orden, p.nombre as producto, o.estado, o.fecha_inicio 
              FROM produccion_ordenes o 
              LEFT JOIN cat_productos p ON o.id_producto = p.id 
              WHERE o.folio_orden LIKE ?";
              
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $parametro = "%" . $termino . "%";
        $stmt->bind_param("s", $parametro);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $stmt->close();
        return $resultado;
    }
    
    return false;
}

// Ejecuta la búsqueda si se proporcionó un término válido.
$resultados = !empty($buscar) ? buscarOrdenesTrazabilidad($conn, $buscar) : [];
?>

<!-- Título de la sección de trazabilidad de lotes -->
<h2>Trazabilidad de Lotes</h2>

<!-- Contenedor del formulario de búsqueda -->
<div class="form-container">
    <h3>Buscar Orden de Producción</h3>
    
    <!-- Formulario para enviar los datos por método GET -->
    <form method="GET" action="panel.php" style="display: flex; gap: 10px;">
        <!-- Campo oculto para mantener la vista actual dentro del panel -->
        <input type="hidden" name="vista" value="funcionalidades/trazabilidad">
        
        <!-- Campo de texto para el folio de búsqueda -->
        <input type="text" name="buscar_orden" placeholder="Ej. OP-001" value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 8px; flex: 1;">
        
        <!-- Botón de envío para realizar la búsqueda -->
        <button type="submit" class="btn-save">Buscar</button>
    </form>
</div>

<!-- Muestra los resultados solo si se ha realizado una búsqueda -->
<?php if (!empty($buscar)): ?>
    <div class="table-container">
        <h3>Resultados para "<?php echo htmlspecialchars($buscar); ?>"</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Producto</th>
                    <th>Estado</th>
                    <th>Fecha Inicio</th>
                </tr>
            </thead>
            <tbody>
                <!-- Comprueba si existen resultados válidos en la búsqueda -->
                <?php if ($resultados && $resultados->num_rows > 0): ?>
                    
                    <!-- Recorre cada registro de orden encontrado -->
                    <?php while($row = $resultados->fetch_assoc()): ?>
                        <tr>
                            <!-- Muestra el folio protegido contra XSS -->
                            <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                            
                            <!-- Muestra el producto protegido contra XSS -->
                            <td><?php echo htmlspecialchars($row['producto']); ?></td>
                            
                            <!-- Muestra el estado protegido contra XSS -->
                            <td><?php echo htmlspecialchars($row['estado']); ?></td>
                            
                            <!-- Muestra la fecha de inicio -->
                            <td><?php echo $row['fecha_inicio']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                    
                <?php else: ?>
                    <!-- Muestra una fila alternativa si no hay coincidencias -->
                    <tr><td colspan="4" style="text-align: center;">No se encontraron resultados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>