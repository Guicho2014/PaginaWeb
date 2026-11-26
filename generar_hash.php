<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash para 'admin123': " . $hash . "\n";
echo "Verificación: " . (password_verify($password, $hash) ? 'OK' : 'FALLÓ') . "\n";
?>
