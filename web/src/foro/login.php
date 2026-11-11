<?php
require_once __DIR__ . '/lib/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if (attempt_login($u, $p)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos';
}
?><!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Iniciar sesión - Foro CTF</title></head>
<body>
<h1>Iniciar sesión</h1>
<?php if ($error !== ''): ?><p><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" action="login.php">
  <p><input type="text" name="username" placeholder="Usuario"></p>
  <p><input type="password" name="password" placeholder="Contraseña"></p>
  <button type="submit">Entrar</button>
</form>
<p><a href="register.php">Registrarse</a></p>
</body>
</html>
