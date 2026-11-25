<?php
require_once BASE_PATH . '/includes/conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ''; // Guarda los mensajes de éxito o error
$modo_edicion = false; // Indica si se está editando un cliente
$cliente_editar = ['id' => '', 'nombre_empresa' => '', 'contacto' => '', 'telefono' => '', 'direccion' => '']; // Guarda los datos del cliente seleccionado

// Verifica si se envió el formulario para guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre_empresa']); // Obtiene el nombre de la empresa
    $contacto = trim($_POST['contacto']); // Obtiene el nombre de la persona de contacto
    $telefono = trim($_POST['telefono']); // Obtiene el teléfono del cliente
    $direccion = trim($_POST['direccion']); // Obtiene la dirección del cliente
    $id_editar = isset($_POST['id_editar']) ? intval($_POST['id_editar']) : 0; // Obtiene el ID del cliente que se editará

    if (!empty($nombre)) { // Verifica que el nombre de la empresa no esté vacío

        if ($id_editar > 0) { // Si existe un ID, actualiza el cliente
            $sql = "UPDATE cat_clientes SET nombre_empresa=?, contacto=?, telefono=?, direccion=? WHERE id=?";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("ssssi", $nombre, $contacto, $telefono, $direccion, $id_editar); // Asigna los valores
            $accion = "actualizado"; // Indica que el cliente fue actualizado

        } else { // Si no existe un ID, registra un nuevo cliente
            $sql = "INSERT INTO cat_clientes (nombre_empresa, contacto, telefono, direccion) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql); // Prepara la consulta SQL
            $stmt->bind_param("ssss", $nombre, $contacto, $telefono, $direccion); // Asigna los valores
            $accion = "agregado"; // Indica que el cliente fue agregado
        }

        if ($stmt->execute()) { // Ejecuta la consulta
            $mensaje = "<div class='alert success'>Cliente $accion correctamente.</div>"; // Mensaje de éxito
        } else {
            $mensaje = "<div class='alert error'>Error al guardar el cliente.</div>"; // Mensaje de error
        }

        $stmt->close(); // Cierra la consulta preparada
    }
}

// Verifica si se solicitó eliminar un cliente
if (isset($_GET['eliminar'])) {

    $id_eliminar = intval($_GET['eliminar']); // Obtiene el ID del cliente que se eliminará

    try {
        $sql = "DELETE FROM cat_clientes WHERE id = ?";
        $stmt = $conn->prepare($sql); // Prepara la consulta de eliminación
        $stmt->bind_param("i", $id_eliminar); // Asigna el ID del cliente
        $stmt->execute(); // Ejecuta la eliminación
        $stmt->close(); // Cierra la consulta

        header("Location: " . BASE_PATH . "/panel.php?vista=catalogos/clientes"); // Regresa al catálogo
        exit();

    } catch (mysqli_sql_exception $e) { // Captura errores de eliminación

        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alert error'>No se puede eliminar: el cliente ya tiene despachos registrados.</div>"; // Error por registros relacionados
        } else {
            $mensaje = "<div class='alert error'>Error al eliminar: " . $e->getMessage() . "</div>"; // Muestra otro error
        }
    }
}

// Verifica si se seleccionó un cliente para editar
if (isset($_GET['editar'])) {

    $id_editar = intval($_GET['editar']); // Obtiene el ID del cliente seleccionado
    $res = $conn->query("SELECT * FROM cat_clientes WHERE id = $id_editar"); // Busca los datos del cliente

    if ($res && $res->num_rows > 0) { // Verifica que exista el registro
        $cliente_editar = $res->fetch_assoc(); // Guarda los datos del cliente
        $modo_edicion = true; // Activa el modo de edición
    }
}

// Obtiene el texto que se utilizará para buscar empresas
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_lista = "SELECT * FROM cat_clientes"; // Consulta todos los clientes

if (!empty($buscar)) {
    $sql_lista .= " WHERE nombre_empresa LIKE '%$buscar%'"; // Filtra los clientes por nombre de empresa
}

$sql_lista .= " ORDER BY id DESC"; // Ordena los clientes del más reciente al más antiguo
$resultado = $conn->query($sql_lista); // Ejecuta la consulta y guarda los resultados
?>

<!-- Título principal del catálogo -->
<h2>Catálogo: Clientes</h2>

<?php echo $mensaje; // Muestra los mensajes del sistema ?>

<!-- Formulario para registrar o editar clientes -->
<div class="form-container">

    <!-- Cambia el título dependiendo del modo -->
    <h3><?php echo $modo_edicion ? 'Editar Cliente' : 'Añadir Nuevo Cliente'; ?></h3>

    <form method="POST" action="">

        <!-- Guarda el ID del cliente que se está editando -->
        <input type="hidden" name="id_editar" value="<?php echo $cliente_editar['id']; ?>">

        <div class="form-grid">
            <div>
                <label>Nombre de la Empresa:</label>
                <input type="text" name="nombre_empresa" value="<?php echo htmlspecialchars($cliente_editar['nombre_empresa']); ?>" required>
                <!-- Campo para ingresar el nombre de la empresa -->
            </div>

            <div>
                <label>Persona de Contacto:</label>
                <input type="text" name="contacto" value="<?php echo htmlspecialchars($cliente_editar['contacto']); ?>">
                <!-- Campo para ingresar el contacto -->
            </div>

            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente_editar['telefono']); ?>">
                <!-- Campo para ingresar el teléfono -->
            </div>

            <div>
                <label>Dirección:</label>
                <input type="text" name="direccion" value="<?php echo htmlspecialchars($cliente_editar['direccion']); ?>">
                <!-- Campo para ingresar la dirección -->
            </div>
        </div>

        <!-- Cambia el texto del botón según la acción -->
        <button type="submit" name="guardar" class="btn-save">
            <?php echo $modo_edicion ? 'Actualizar' : 'Guardar'; ?>
        </button>

        <?php if($modo_edicion): ?>
            <!-- Permite cancelar la edición -->
            <a href="panel.php?vista=catalogos/clientes" style="margin-left: 15px; color: #cc0000; text-decoration: none; font-weight: bold;">Cancelar</a>
        <?php endif; ?>

    </form>
</div>

<!-- Contenedor de la tabla de clientes -->
<div class="table-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

        <h3>Lista de Clientes</h3>

        <!-- Formulario para buscar clientes por empresa -->
        <form method="GET" action="panel.php" style="display: flex; gap: 10px;">

            <input type="hidden" name="vista" value="catalogos/clientes">

            <!-- Campo donde se escribe el nombre de la empresa -->
            <input type="text" name="buscar" placeholder="Buscar empresa..." value="<?php echo htmlspecialchars($buscar); ?>" style="padding: 5px;">

            <button type="submit" class="btn-save" style="padding: 5px 10px;">Buscar</button>
        </form>
    </div>

    <!-- Tabla que muestra los clientes registrados -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Empresa</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <!-- Verifica si existen resultados -->
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <!-- Recorre cada cliente encontrado -->
                <?php while($row = $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo $row['id']; ?></td> <!-- Muestra el ID -->
                        <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td> <!-- Muestra la empresa -->
                        <td><?php echo htmlspecialchars($row['contacto']); ?></td> <!-- Muestra el contacto -->
                        <td><?php echo htmlspecialchars($row['telefono']); ?></td> <!-- Muestra el teléfono -->
                        <td><?php echo htmlspecialchars($row['direccion']); ?></td> <!-- Muestra la dirección -->

                        <td>
                            <!-- Enlace para editar el cliente seleccionado -->
                            <a href="panel.php?vista=catalogos/clientes&editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>

                            <!-- Enlace para eliminar el cliente con confirmación -->
                            <a href="panel.php?vista=catalogos/clientes&eliminar=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro?');">Eliminar</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- Mensaje cuando no existen clientes registrados -->
                <tr>
                    <td colspan="6" style="text-align: center;">No hay clientes.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>