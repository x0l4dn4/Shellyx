<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

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

forum_head('Iniciar sesión — Shellyx');
forum_bar(current_user());
?>
<section class="auth"><div class="wrap">
  <form class="auth-card" method="post" action="login.php">
    <h1>Iniciar sesión</h1>
    <p class="sub">Accede para publicar hilos en Shellyx.</p>
    <?php if ($error !== ''): ?><div class="auth-error"><?= e($error) ?></div><?php endif; ?>
    <div class="field"><label for="u">Usuario</label><input id="u" name="username" type="text" placeholder="tu_usuario"></div>
    <div class="field"><label for="p">Contraseña</label><input id="p" name="password" type="password" placeholder="••••••••"></div>
    <button class="btn btn-accent btn-block" type="submit">Entrar</button>
    <p class="auth-foot">¿No tienes cuenta?<br>
      <a href="register.php"><span class="invite"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-4Z"/></svg>El acceso es solo por invitación</span></a>
    </p>
  </form>
</div></section>
<?php forum_footer(); ?>
