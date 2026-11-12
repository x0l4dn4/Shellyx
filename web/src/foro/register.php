<?php
// El foro no permite auto-registro (acceso solo por invitación).
require_once __DIR__ . '/../lib/layout.php';
sx_header('Registro — Foro Shellyx', 'foro', null, 'El acceso al foro de Shellyx es solo por invitación.');
?>
<section class="section">
  <div class="container narrow">
    <div class="panel panel--pad center">
      <div class="card__icon" style="margin:0 auto 16px"><?= sx_icon('shield') ?></div>
      <span class="eyebrow" style="margin:0">Registro</span>
      <h1 style="font-size:1.8rem;margin:.3em 0 .4em">Acceso solo por invitación</h1>
      <p class="muted">El foro de la comunidad Shellyx está reservado a miembros invitados. Si tienes credenciales, inicia sesión.</p>
      <div class="hero__cta" style="justify-content:center;margin-top:22px">
        <a class="btn btn--primary" href="login.php"><?= sx_icon('terminal', 18) ?> Iniciar sesión</a>
        <a class="btn btn--ghost" href="/">Volver al inicio</a>
      </div>
    </div>
  </div>
</section>
<?php sx_footer(); ?>
