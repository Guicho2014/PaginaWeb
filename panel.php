<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

define('BASE_PATH', __DIR__);

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$vista = isset($_GET['vista']) ? $_GET['vista'] : 'inicio';

$vistas_permitidas = [
    'inicio', 
    'funcionalidades/recepcion', 
    'funcionalidades/programacion',
    'funcionalidades/asignacion',
    'funcionalidades/seguimiento',
    'funcionalidades/calidad',
    'funcionalidades/trazabilidad',
    'funcionalidades/desperdicios',
    'funcionalidades/despacho',
    'funcionalidades/reportes',
    'funcionalidades/alertas',
    'catalogos/materiasPrimas',
    'catalogos/productos',
    'catalogos/proveedores',
    'catalogos/clientes',
    'catalogos/empleados',
    'catalogos/maquinaria',
    'catalogos/areas',
    'catalogos/unidades',
    'catalogos/tiposDesperdicio',
    'catalogos/mantenimiento'
]; 

if (!in_array($vista, $vistas_permitidas)) {
    $vista = 'inicio';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control | EcoPlanta</title>
    <link rel="stylesheet" href="assets/css/panel.css">
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>♻ EcoPlanta</h2>
            <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
        </div>

        <nav class="sidebar-nav">
            <h3>Procesos</h3>
            <a href="?vista=inicio" class="<?php echo ($vista == 'inicio') ? 'active' : ''; ?>">Inicio</a>
            <a href="?vista=funcionalidades/recepcion" class="<?php echo ($vista == 'funcionalidades/recepcion') ? 'active' : ''; ?>">Recepción</a>
            <a href="?vista=funcionalidades/programacion" class="<?php echo ($vista == 'funcionalidades/programacion') ? 'active' : ''; ?>">Programación</a>
            <a href="?vista=funcionalidades/asignacion" class="<?php echo ($vista == 'funcionalidades/asignacion') ? 'active' : ''; ?>">Asignación</a>
            <a href="?vista=funcionalidades/seguimiento" class="<?php echo ($vista == 'funcionalidades/seguimiento') ? 'active' : ''; ?>">Seguimiento</a>
            <a href="?vista=funcionalidades/calidad" class="<?php echo ($vista == 'funcionalidades/calidad') ? 'active' : ''; ?>">Calidad</a>
            <a href="?vista=funcionalidades/trazabilidad" class="<?php echo ($vista == 'funcionalidades/trazabilidad') ? 'active' : ''; ?>">Trazabilidad</a>
            <a href="?vista=funcionalidades/desperdicios" class="<?php echo ($vista == 'funcionalidades/desperdicios') ? 'active' : ''; ?>">Desperdicios</a>
            <a href="?vista=funcionalidades/despacho" class="<?php echo ($vista == 'funcionalidades/despacho') ? 'active' : ''; ?>">Despacho</a>
            <a href="?vista=funcionalidades/reportes" class="<?php echo ($vista == 'funcionalidades/reportes') ? 'active' : ''; ?>">Reportes</a>
            <a href="?vista=funcionalidades/alertas" class="<?php echo ($vista == 'funcionalidades/alertas') ? 'active' : ''; ?>">Alertas</a>

            <h3>Catálogos</h3>
            <a href="?vista=catalogos/materiasPrimas" class="<?php echo ($vista == 'catalogos/materiasPrimas') ? 'active' : ''; ?>">Materias Primas</a>
            <a href="?vista=catalogos/productos" class="<?php echo ($vista == 'catalogos/productos') ? 'active' : ''; ?>">Productos</a>
            <a href="?vista=catalogos/proveedores" class="<?php echo ($vista == 'catalogos/proveedores') ? 'active' : ''; ?>">Proveedores</a>
            <a href="?vista=catalogos/clientes" class="<?php echo ($vista == 'catalogos/clientes') ? 'active' : ''; ?>">Clientes</a>
            <a href="?vista=catalogos/empleados" class="<?php echo ($vista == 'catalogos/empleados') ? 'active' : ''; ?>">Empleados</a>
            <a href="?vista=catalogos/maquinaria" class="<?php echo ($vista == 'catalogos/maquinaria') ? 'active' : ''; ?>">Maquinaria</a>
            <a href="?vista=catalogos/areas" class="<?php echo ($vista == 'catalogos/areas') ? 'active' : ''; ?>">Áreas</a>
            <a href="?vista=catalogos/unidades" class="<?php echo ($vista == 'catalogos/unidades') ? 'active' : ''; ?>">Unidades</a>
            <a href="?vista=catalogos/tiposDesperdicio" class="<?php echo ($vista == 'catalogos/tiposDesperdicio') ? 'active' : ''; ?>">Tipos Desperdicio</a>
            <a href="?vista=catalogos/mantenimiento" class="<?php echo ($vista == 'catalogos/mantenimiento') ? 'active' : ''; ?>">Mantenimiento</a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="main-content">
        <?php
        $ruta_archivo = 'vistas/' . $vista . '.php';
        
        if (file_exists($ruta_archivo)) {
            include($ruta_archivo);
        } else {
            echo "<h2>Error 404</h2><p>La vista solicitada no existe.</p>";
        }
        ?>
    </main>

</body>
</html>