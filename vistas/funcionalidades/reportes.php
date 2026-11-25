<?php
// Incluye la conexión a la base de datos usando BASE_PATH.
require_once BASE_PATH . '/includes/conexion.php';

/**
 * Obtiene el total de un conteo o suma según la tabla y condición especificada.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @param string $tabla Nombre de la tabla.
 * @param string $columna Columna a operar (ej. COUNT(*) o SUM(cantidad)).
 * @param string $condicion Condición opcional para la consulta (ej. "estado = 'Terminada'").
 * @return float|int Valor numérico obtenido.
 */
function obtenerTotalMetrica(mysqli $conn, string $tabla, string $columna, string $condicion = ""): float|int {
    $sql = "SELECT $columna as total FROM $tabla";
    if (!empty($condicion)) {
        $sql .= " WHERE $condicion";
    }
    
    $resultado = $conn->query($sql);
    if ($resultado && $fila = $resultado->fetch_assoc()) {
        return $fila['total'] ?? 0;
    }
    return 0;
}

/**
 * Obtiene las últimas 5 órdenes de producción registradas con su producto asociado.
 * 
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return mysqli_result Resultado con las últimas órdenes.
 */
function obtenerUltimasOrdenes(mysqli $conn): mysqli_result {
    $sql = "SELECT o.folio_orden, p.nombre as producto, o.cantidad_programada, o.estado 
              FROM produccion_ordenes o 
              LEFT JOIN cat_productos p ON o.id_producto = p.id 
              ORDER BY o.id DESC LIMIT 5";
    return $conn->query($sql);
}

// Obtiene todas las métricas utilizando la función centralizada.
$total_ordenes          = obtenerTotalMetrica($conn, 'produccion_ordenes', 'COUNT(*)');
$total_terminadas       = obtenerTotalMetrica($conn, 'produccion_ordenes', 'COUNT(*)', "estado = 'Terminada'");
$total_calidad_aprobada = obtenerTotalMetrica($conn, 'control_calidad', 'COUNT(*)', "resultado = 'Aprobado'");
$total_desperdicios     = obtenerTotalMetrica($conn, 'registro_desperdicios', 'SUM(cantidad)');
$total_despachos        = obtenerTotalMetrica($conn, 'despachos', 'COUNT(*)');

// Obtiene el listado de las últimas órdenes para la tabla.
$ultimas_ordenes = obtenerUltimasOrdenes($conn);
?>

<!-- Título de la sección de reportes -->
<h2>Generación de Reportes</h2>

<!-- Contenedor con las tarjetas de métricas del sistema -->
<div class="form-container" style="display: flex; gap: 15px; flex-wrap: wrap;">
    
    <!-- Métrica: Órdenes Totales -->
    <div style="border: 1px solid #000; padding: 20px; flex: 1; text-align: center;">
        <h3>Órdenes Totales</h3>
        <p style="font-size: 32px; font-weight: bold;"><?php echo $total_ordenes; ?></p>
    </div>
    
    <!-- Métrica: Órdenes Terminadas -->
    <div style="border: 1px solid #000; padding: 20px; flex: 1; text-align: center;">
        <h3>Terminadas</h3>
        <p style="font-size: 32px; font-weight: bold;"><?php echo $total_terminadas; ?></p>
    </div>
    
    <!-- Métrica: Calidad Aprobada -->
    <div style="border: 1px solid #000; padding: 20px; flex: 1; text-align: center;">
        <h3>Calidad Aprobada</h3>
        <p style="font-size: 32px; font-weight: bold;"><?php echo $total_calidad_aprobada; ?></p>
    </div>
    
    <!-- Métrica: Desperdicios Totales -->
    <div style="border: 1px solid #000; padding: 20px; flex: 1; text-align: center;">
        <h3>Desperdicios (kg)</h3>
        <p style="font-size: 32px; font-weight: bold;"><?php echo $total_desperdicios; ?></p>
    </div>
    
    <!-- Métrica: Despachos Realizados -->
    <div style="border: 1px solid #000; padding: 20px; flex: 1; text-align: center;">
        <h3>Despachos</h3>
        <p style="font-size: 32px; font-weight: bold;"><?php echo $total_despachos; ?></p>
    </div>
</div>

<!-- Contenedor de la tabla con las últimas órdenes -->
<div class="table-container">
    <h3>Últimas 5 Órdenes</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <!-- Recorre e imprime cada una de las últimas órdenes -->
            <?php while($row = $ultimas_ordenes->fetch_assoc()): ?>
                <tr>
                    <!-- Muestra el folio protegido contra XSS -->
                    <td><?php echo htmlspecialchars($row['folio_orden']); ?></td>
                    
                    <!-- Muestra el producto protegido contra XSS -->
                    <td><?php echo htmlspecialchars($row['producto']); ?></td>
                    
                    <!-- Muestra la cantidad programada -->
                    <td><?php echo $row['cantidad_programada']; ?></td>
                    
                    <!-- Muestra el estado actual -->
                    <td><?php echo htmlspecialchars($row['estado']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>