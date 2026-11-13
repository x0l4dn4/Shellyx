<?php
require_once __DIR__ . '/../lib/shop_auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (shop_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /tienda/carrito.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}

require_once __DIR__ . '/../lib/layout.php';
sx_header('Acceder — Shellyx', 'tienda', null, 'Accede a tu cuenta de la tienda Shellyx.');
?>
<section class="section">
  <div class="container" style="max-width:420px">
    <span class="eyebrow">Cuenta</span>
    <h1 style="margin:0 0 .3em">Accede a tu cuenta</h1>
    <p class="muted" style="margin:0 0 20px">Necesitas una cuenta para usar el carrito.</p>
    <?php if ($error !== ''): ?>
      <div class="panel panel--pad" style="padding:12px 16px;border-color:var(--amber);color:var(--amber);margin-bottom:16px"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php" class="panel panel--pad acct-form">
      <label>Usuario<input type="text" name="username" autofocus></label>
      <label>Contraseña<input type="password" name="password"></label>
      <button class="btn btn--primary btn--block" type="submit" style="margin-top:4px">Acceder</button>
    </form>
    <p class="muted center" style="margin-top:14px">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
  </div>
</section>
<?php sx_footer(); ?>
