<?php
// El foro no permite auto-registro (acceso solo por invitación).
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

forum_head('Registro — Shellyx');
forum_bar(current_user());
?>
<section class="auth"><div class="wrap">
  <div class="auth-card">
    <h1>Registro</h1>
    <p class="sub">El foro de Shellyx es una comunidad cerrada.</p>
    <div class="invite-box">
      <span class="invite"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-4Z"/></svg>Solo invitación</span>
      Acceso solo por invitación.
    </div>
    <p class="auth-foot">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
  </div>
</div></section>
<?php forum_footer(); ?>
