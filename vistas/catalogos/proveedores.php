<?php
require_once BASE_PATH . '/includes/conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ''; // Guarda los mensajes de éxito o error
$modo_edicion = false; // Indica si se está editando un proveedor
$proveedor_editar = ['id' => '', 'nombre_empresa' => '', 'contacto' => '', 'telefono' => '', 'correo' => '']; // Guarda los datos del proveedor seleccionado

// Verifica si se envió el formulario para guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre_empresa']); // Obtiene el nombre de la empresa
    $contacto = trim($_POST['contacto']); // Obtiene el nombre de la persona de contacto
    $telefono = trim($_POST['telefono']); // Obtiene el teléfono del proveedor
    $correo = trim($_POST['correo']); // Obtiene el correo electrónico
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0; // Obtiene el ID del proveedor que se editará

    if (!empty($nombre)) { // Verifica que el nombre de la empresa no esté vacío

        if ($id_editar > 0) { // Si existe un ID, actualiza el proveedor
            $sql = "UPDATE cat_proveedores SET nombre_empresa=?, contacto=?, telefono=?, correo=? WHERE id=?";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("ssssi", $nombre, $contacto, $telefono, $correo, $id_editar); // Asigna los valores
            $accion = "actualizado"; // Indica que el proveedor fue actualizado

        } else { // Si no existe un ID, registra un proveedor nuevo
            $sql = "INSERT INTO cat_proveedores (nombre_empresa, contacto, telefono, correo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("ssss", $nombre, $contacto, $telefono, $correo); // Asigna los valores
            $accion = "agregado"; // Indica que el proveedor fue agregado
        }

        if ($stmt->execute()) { // Ejecuta la consulta
            $mensaje = "<div class='alert success'>Proveedor $accion correctamente.</div>"; // Mensaje de éxito
        } else {
            $mensaje = "<div class='alert error'>Error al guardar el proveedor.</div>"; // Mensaje de error
        }

        $stmt->close(); // Cierra la consulta preparada
    }
}

// Verifica si se solicitó eliminar un proveedor
if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']); // Obtiene el ID del proveedor que se eliminará

    try {
        $sql = "DELETE FROM cat_proveedores WHERE id = ?";
        $stmt = $conn->prepare($sql); // Prepara la consulta de eliminación
        $stmt->bind_param("i", $id_eliminar); // Asigna el ID del proveedor
        $stmt->execute(); // Ejecuta la eliminación
        $stmt->close(); // Cierra la consulta

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/proveedores"); // Regresa al catálogo
        exit();

    } catch (mysqli_sql_exception $e) { // Captura errores al eliminar

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: el proveedor ya tiene recepciones registradas.</div>"; // Error por registros relacionados
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>"; // Muestra otro error
        }
    }
}

// Verifica si se seleccionó un proveedor para editar
if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']); // Obtiene el ID del proveedor seleccionado
    $res = $conn->query("SELECT * FROM cat_proveedores WHERE id = $id_editar"); // Busca los datos del proveedor

    if ($res && $res->num_rows > 0) { // Verifica que exista el registro
        $proveedor_editar = $res->fetch_assoc(); // Guarda los datos del proveedor
        $modo_edicion = true; // Activa el modo de edición
    }
}

// Obtiene el texto que se utilizará para buscar proveedores
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_lista = "SELECT * FROM cat_proveedores"; // Consulta todos los proveedores registrados

if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre_empresa LIKE '%$buscar%'"; // Filtra los proveedores por nombre de empresa
}

$sql_lista .= " ORDER BY id DESC"; // Ordena los proveedores del más reciente al más antiguo
$resultado = $conn->query($sql_lista); // Ejecuta la consulta y guarda los resultados
?>

<!-- Título principal del catálogo -->
<h2>Catálogo: Proveedores</h2>

<?php echo $mensaje; // Muestra los mensajes del sistema ?>

<!-- Formulario para registrar o editar proveedores -->
<div class="form-container">

    <!-- Cambia el título dependiendo del modo -->
    <h3><?php echo $modo_edicion ? 'Editar Proveedor' : 'Añadir Nuevo Proveedor'; ?></h3>

    <form method="POST" action="">

        <!-- Guarda el ID del proveedor que se está editando -->
        <input type="hidden" name="id_editar" value="<?php echo $proveedor_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre de la Empresa:</label>
                <input type="text" name="nombre_empresa" value="<?php echo htmlspecialchars($proveedor_editar['nombre_empresa']); ?>" required>
                <!-- Campo para ingresar el nombre de la empresa -->
            </div>

            <div>
                <label>Persona de Contacto:</label>
                <input type="text" name="contacto" value="<?php echo htmlspecialchars($proveedor_editar['contacto']); ?>">
                <!-- Campo para ingresar el nombre del contacto -->
            </div>

            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($proveedor_editar['telefono']); ?>">
                <!-- Campo para ingresar el teléfono del proveedor -->
            </div>

            <div>
                <label>Correo:</label>
                <input type="email" name="correo" value="<?php echo htmlspecialchars($proveedor_editar['correo']); ?>">
                <!-- Campo para ingresar el correo electrónico -->
            </div>
        </div>

        <!-- Cambia el texto del botón según la acción -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <!-- Permite cancelar la edición -->
            <a href="panel.php?vista=catalogos/proveedores" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<!-- Contenedor de la tabla de proveedores -->
<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Proveedores</h3>

        <!-- Formulario para buscar proveedores -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/proveedores">

            <!-- Campo para escribir el nombre de la empresa que se desea buscar -->
            <input type="text" name="buscar" placeholder="Buscar empresa..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra los proveedores registrados -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Empresa</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Verifica si existen resultados -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada proveedor encontrado -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td> <!-- Muestra el ID -->
                        <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td> <!-- Muestra la empresa -->
                        <td><?php echo htmlspecialchars($row['contacto']); ?></td> <!-- Muestra el contacto -->
                        <td><?php echo htmlspecialchars($row['telefono']); ?></td> <!-- Muestra el teléfono -->
                        <td><?php echo htmlspecialchars($row['correo']); ?></td> <!-- Muestra el correo -->

                        <td>
                            <!-- Enlace para editar el proveedor seleccionado -->
                            <a href="panel.php?vista=catalogos/proveedores&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar el proveedor con confirmación -->
                            <a href="panel.php?vista=catalogos/proveedores&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no existen proveedores registrados -->
                <tr>
                    <td colspan="6" style="text-align: center;">No hay proveedores.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>