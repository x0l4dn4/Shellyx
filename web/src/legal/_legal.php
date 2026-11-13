<?php
// Marco común de las páginas legales de Shellyx (cabecera, índice lateral y pie).
require_once __DIR__ . '/../lib/layout.php';

const LEGAL_DOCS = [
    'aviso-legal' => 'Aviso legal',
    'privacidad'  => 'Política de privacidad',
    'cookies'     => 'Política de cookies',
    'terminos'    => 'Términos y condiciones',
    'faq'         => 'Preguntas frecuentes',
];

function legal_start(string $active, string $title, string $updated, string $desc = ''): void
{
    sx_header($title . ' — Shellyx', '', null, $desc !== '' ? $desc : $title . ' de Shellyx.');
    ?>
<section class="section">
  <div class="container legal-wrap">
    <aside class="legal-nav">
      <span class="eyebrow" style="margin-bottom:12px">Legal</span>
      <nav>
        <?php foreach (LEGAL_DOCS as $slug => $label): ?>
          <a href="/legal/<?= $slug ?>.php"<?= $active === $slug ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </aside>
    <article class="legal">
      <span class="eyebrow">Shellyx · Documentación legal</span>
      <h1><?= e($title) ?></h1>
      <p class="legal-updated">Última actualización: <?= e($updated) ?></p>
      <div class="legal-note">Plantilla profesional lista para usar. Antes de publicarla, completa los campos entre corchetes <code>[…]</code> con los datos reales de la empresa y revísala con asesoría legal para adaptarla a tu actividad concreta.</div>
<?php
}

function legal_end(): void
{
    ?>
    </article>
  </div>
</section>
<?php
    sx_footer();
}
