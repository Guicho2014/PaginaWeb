<?php
require_once BASE_PATH . '/includes/conexion.php';

// Estadísticas de catálogos
$total_materias_primas = $conn->query("SELECT COUNT(*) as total FROM cat_materias_primas")->fetch_assoc()['total'];
$total_productos = $conn->query("SELECT COUNT(*) as total FROM cat_productos")->fetch_assoc()['total'];
$total_empleados = $conn->query("SELECT COUNT(*) as total FROM cat_empleados WHERE estado = 'Activo'")->fetch_assoc()['total'];
$total_proveedores = $conn->query("SELECT COUNT(*) as total FROM cat_proveedores")->fetch_assoc()['total'];
$total_clientes = $conn->query("SELECT COUNT(*) as total FROM cat_clientes")->fetch_assoc()['total'];

// Estadísticas de maquinaria
$maquinaria_stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'Operativa' THEN 1 ELSE 0 END) as operativas,
        SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) en_mantenimiento
    FROM cat_maquinaria
")->fetch_assoc();

// Estadísticas de procesos
$total_pre_registros = $conn->query("SELECT COUNT(*) as total FROM pre_registros")->fetch_assoc()['total'];
$total_recepciones = $conn->query("SELECT COUNT(*) as total FROM recepciones")->fetch_assoc()['total'];
$total_ordenes = $conn->query("SELECT COUNT(*) as total FROM produccion_ordenes")->fetch_assoc()['total'];
$total_despachos = $conn->query("SELECT COUNT(*) as total FROM despachos")->fetch_assoc()['total'];

// Últimos pre-registros
$ultimos_pre_registros = $conn->query("
    SELECT pr.*, mp.nombre as material_nombre 
    FROM pre_registros pr 
    LEFT JOIN cat_materias_primas mp ON pr.id_materia_prima = mp.id 
    ORDER BY pr.fecha_pre_registro DESC 
    LIMIT 5
");
?>

<h2>Bienvenido al Sistema EcoPlanta</h2>
<p>Resumen general del sistema</p>

<!-- Catálogos -->
<div class="form-container">
    <h3>📦 Catálogos</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Total Registrado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Materias Primas</td>
                <td><?php echo $total_materias_primas; ?></td>
            </tr>
            <tr>
                <td>Productos</td>
                <td><?php echo $total_productos; ?></td>
            </tr>
            <tr>
                <td>Empleados Activos</td>
                <td><?php echo $total_empleados; ?></td>
            </tr>
            <tr>
                <td>Proveedores</td>
                <td><?php echo $total_proveedores; ?></td>
            </tr>
            <tr>
                <td>Clientes</td>
                <td><?php echo $total_clientes; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Maquinaria -->
<div class="form-container">
    <h3>🏭 Estado de Maquinaria</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Estado</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Operativas</td>
                <td><?php echo $maquinaria_stats['operativas'] ?? 0; ?></td>
            </tr>
            <tr>
                <td>En Mantenimiento</td>
                <td><?php echo $maquinaria_stats['en_mantenimiento'] ?? 0; ?></td>
            </tr>
            <tr>
                <td>Total</td>
                <td><?php echo $maquinaria_stats['total'] ?? 0; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Procesos -->
<div class="form-container">
    <h3>⚙️ Procesos y Operaciones</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Proceso</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pre-Registros de Donadores</td>
                <td><?php echo $total_pre_registros; ?></td>
            </tr>
            <tr>
                <td>Recepciones de Material</td>
                <td><?php echo $total_recepciones; ?></td>
            </tr>
            <tr>
                <td>Órdenes de Producción</td>
                <td><?php echo $total_ordenes; ?></td>
            </tr>
            <tr>
                <td>Despachos Realizados</td>
                <td><?php echo $total_despachos; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Últimos Pre-Registros -->
<div class="form-container">
    <h3>🕐 Últimos Pre-Registros</h3>
    <?php if ($ultimos_pre_registros && $ultimos_pre_registros->num_rows > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Donador</th>
                    <th>Material</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($pr = $ultimos_pre_registros->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pr['nombre_donador']); ?></td>
                        <td><?php echo htmlspecialchars($pr['material_nombre'] ?? 'Sin material'); ?></td>
                        <td><?php echo $pr['cantidad_aproximada']; ?> kg</td>
                        <td><?php echo htmlspecialchars($pr['estado']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; padding: 20px; color: #999;">No hay pre-registros recientes</p>
    <?php endif; ?>
</div>

<!-- Acciones Rápidas -->
<div class="form-container">
    <h3>🚀 Acciones Rápidas</h3>
    <p>
        <a href="?vista=funcionalidades/recepcion" class="btn-save" style="margin-right: 10px;">Nueva Recepción</a>
        <a href="?vista=funcionalidades/programacion" class="btn-save" style="margin-right: 10px;">Programar Producción</a>
        <a href="?vista=funcionalidades/despacho" class="btn-save">Registrar Despacho</a>
    </p>
</div>