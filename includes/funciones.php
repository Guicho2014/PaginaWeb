<?php
/**
 * Genera un folio automático con formato: PREFIJO-AÑO-NÚMERO
 * Ejemplo: REC-2026-0001
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $tabla Nombre de la tabla
 * @param string $columna_folio Nombre de la columna del folio
 * @param string $prefijo Prefijo del folio (REC, OP, DES)
 * @return string Folio generado
 */
function generarFolio($conn, $tabla, $columna_folio, $prefijo) {
    $anio = date('Y');
    
    // Buscar el último folio del año actual
    $sql = "SELECT $columna_folio FROM $tabla 
            WHERE $columna_folio LIKE ? 
            ORDER BY $columna_folio DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $buscar = "$prefijo-$anio-%";
    $stmt->bind_param("s", $buscar);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $ultimo_folio = $fila[$columna_folio];
        // Extraer el número (últimos 4 dígitos)
        $numero = (int)substr($ultimo_folio, -4) + 1;
    } else {
        $numero = 1;
    }
    
    // Formatear con ceros a la izquierda (4 dígitos)
    return sprintf("%s-%s-%04d", $prefijo, $anio, $numero);
}
?>