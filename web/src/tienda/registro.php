<?php
require_once __DIR__ . '/../lib/shop_auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $error] = shop_register($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($ok) {
        header('Location: /tienda/carrito.php');
        exit;
    }
}

require_once __DIR__ . '/../lib/layout.php';
sx_header('Crear cuenta — Shellyx', 'tienda', null, 'Crea tu cuenta de la tienda Shellyx.');
?>
<section class="section">
  <div class="container" style="max-width:420px">
    <span class="eyebrow">Cuenta</span>
    <h1 style="margin:0 0 .3em">Crea tu cuenta</h1>
    <p class="muted" style="margin:0 0 20px">Regístrate para comprar. Sin correo: solo usuario y contraseña.</p>
    <?php if ($error !== ''): ?>
      <div class="panel panel--pad" style="padding:12px 16px;border-color:var(--amber);color:var(--amber);margin-bottom:16px"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="registro.php" class="panel panel--pad acct-form">
      <label>Usuario<input type="text" name="username" autofocus></label>
      <label>Contraseña<input type="password" name="password"></label>
      <button class="btn btn--primary btn--block" type="submit" style="margin-top:4px">Crear cuenta</button>
    </form>
    <p class="muted center" style="margin-top:14px">¿Ya tienes cuenta? <a href="login.php">Accede</a></p>
  </div>
</section>
<?php sx_footer(); ?>
