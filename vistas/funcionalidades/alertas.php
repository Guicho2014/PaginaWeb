<?php
// Incluye el archivo de conexión a la base de datos.
require_once BASE_PATH . '/includes/conexion.php';

// Crea un arreglo vacío para almacenar las alertas del sistema.
$alertas = [];

// Consulta cuántos avisos de donadores tienen estado Pendiente.
$pre_pendientes = $conn->query("SELECT COUNT(*) as total FROM pre_registros WHERE estado = 'Pendiente'")->fetch_assoc()['total'];

// Comprueba si existen avisos pendientes.
if ($pre_pendientes > 0) {

    // Agrega una alerta de recepción al arreglo.
    $alertas[] = [
        'tipo' => 'Recepción',
        'descripcion' => "Hay $pre_pendientes aviso(s) de donadores pendientes de validar.",
        'prioridad' => 'Alta'
    ];
}

// Consulta cuántas órdenes de producción están retrasadas.
$ordenes_retrasadas = $conn->query("SELECT COUNT(*) as total FROM produccion_ordenes WHERE estado = 'En Proceso' AND fecha_fin_estimada < CURDATE()")->fetch_assoc()['total'];

// Comprueba si existen órdenes que superaron su fecha estimada.
if ($ordenes_retrasadas > 0) {

    // Agrega una alerta de producción.
    $alertas[] = [
        'tipo' => 'Producción',
        'descripcion' => "Hay $ordenes_retrasadas orden(es) en proceso que ya pasaron su fecha estimada de fin.",
        'prioridad' => 'Alta'
    ];
}

// Consulta las órdenes terminadas que todavía no tienen inspección de calidad.
$calidad_pendiente = $conn->query("SELECT COUNT(*) as total FROM produccion_ordenes WHERE estado = 'Terminada' AND id NOT IN (SELECT id_orden FROM control_calidad)")->fetch_assoc()['total'];

// Comprueba si existen órdenes sin revisión de calidad.
if ($calidad_pendiente > 0) {

    // Agrega una alerta de calidad.
    $alertas[] = [
        'tipo' => 'Calidad',
        'descripcion' => "Hay $calidad_pendiente orden(es) terminadas sin inspección de calidad registrada.",
        'prioridad' => 'Media'
    ];
}

// Comprueba si el arreglo de alertas está vacío.
if (empty($alertas)) {

    // Agrega un mensaje indicando que no existen alertas pendientes.
    $alertas[] = [
        'tipo' => 'Sistema',
        'descripcion' => 'No hay alertas pendientes. Todo en orden.',
        'prioridad' => 'Baja'
    ];
}
?>

<!-- Título de la sección de alertas -->
<h2>Alertas de Producción</h2>

<div class="table-container">

    <!-- Tabla donde se muestran las alertas encontradas -->
    <table class="data-table">

        <!-- Encabezados de la tabla -->
        <thead>
            <tr>
                <th>Tipo de Alerta</th>
                <th>Descripción</th>
                <th>Prioridad</th>
            </tr>
        </thead>

        <tbody>

            <!-- Recorre todas las alertas almacenadas -->
            <?php foreach($alertas as $alerta): ?>

                <tr>

                    <!-- Muestra el tipo de alerta -->
                    <td><strong><?php echo $alerta['tipo']; ?></strong></td>

                    <!-- Muestra la descripción de la alerta -->
                    <td><?php echo $alerta['descripcion']; ?></td>

                    <td>

                        <!-- Muestra la prioridad y cambia el color según su nivel -->
                        <span style="padding: 3px 8px; border: 1px solid #000; font-size: 12px;

                            <?php
                            // Si la prioridad es alta, muestra un fondo rojo claro.
                            echo ($alerta['prioridad'] == 'Alta') ? 'background: #ffcccc;' : '';
                            ?>

                            <?php
                            // Si la prioridad es media, muestra un fondo amarillo claro.
                            echo ($alerta['prioridad'] == 'Media') ? 'background: #fff3cd;' : '';
                            ?>">

                            <!-- Imprime el nivel de prioridad -->
                            <?php echo $alerta['prioridad']; ?>

                        </span>
                    </td>
                </tr>

            <!-- Finaliza el recorrido de las alertas -->
            <?php endforeach; ?>

        </tbody>
    </table>
</div>