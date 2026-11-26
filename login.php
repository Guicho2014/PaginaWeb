<?php
session_start();
require_once 'includes/conexion.php';

$error = '';

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['usuario_id'])) {
    header("Location: panel.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    
    // Validación básica
    if (empty($usuario) || empty($password)) {
        $error = "Por favor completa todos los campos.";
    } else {
        $sql = "SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ? AND estado = 'Activo'";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows === 1) {
                $fila = $resultado->fetch_assoc();
                
                // VALIDACIÓN SEGURA CON password_verify
                if (password_verify($password, $fila['password'])) {
                    // Regenerar ID de sesión por seguridad
                    session_regenerate_id(true);
                    
                    $_SESSION['usuario_id'] = $fila['id'];
                    $_SESSION['usuario_nombre'] = $fila['usuario'];
                    $_SESSION['usuario_rol'] = $fila['rol'];
                    
                    header("Location: panel.php");
                    exit();
                } else {
                    $error = "Credenciales inválidas.";
                }
            } else {
                // Mensaje genérico para no revelar si el usuario existe o no
                $error = "Credenciales inválidas.";
            }
            $stmt->close();
        } else {
            $error = "Error en el sistema. Intenta más tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EcoPlanta</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h1>Acceso al Sistema</h1>
        
        <?php if(!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Usuario:</label>
                <input type="text" name="usuario" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-submit">Entrar</button>
        </form>
        
        <a href="index.php" class="link-back">← Volver al inicio</a>
    </div>
</body>
</html>