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

require_once __DIR__ . '/../lib/layout.php';
sx_header('Iniciar sesión — Foro Shellyx', 'foro', null, 'Acceso al foro de la comunidad Shellyx.');
?>
<section class="section">
  <div class="container narrow">
    <div class="panel panel--pad">
      <div class="center" style="margin-bottom:22px">
        <div class="card__icon" style="margin:0 auto 14px"><?= sx_icon('lock') ?></div>
        <span class="eyebrow" style="margin:0">Acceso al foro</span>
        <h1 style="font-size:1.8rem;margin:.3em 0 0">Iniciar sesión</h1>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert--err"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <div class="field">
          <label for="username">Usuario</label>
          <input type="text" id="username" name="username" placeholder="usuario" autocomplete="username" autofocus>
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password">
        </div>
        <button class="btn btn--primary btn--block" type="submit"><?= sx_icon('terminal', 18) ?> Entrar</button>
      </form>

      <p class="center muted" style="margin:18px 0 0;font-size:.9rem">¿No tienes cuenta? <a href="register.php">Registrarse</a></p>
    </div>
    <p class="center muted mono" style="margin-top:16px;font-size:.78rem">Privacidad garantizada · solo usuario y hash, sin correo</p>
  </div>
</section>
<?php sx_footer(); ?>
