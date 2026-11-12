<?php
require_once __DIR__ . '/lib/auth.php';
require_login();
$u = current_user();

$id = $_GET['id'] ?? 0;
$stmt = mysqli_prepare(db(), "SELECT id, title, body, embed_url FROM posts WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$post = $res ? mysqli_fetch_assoc($res) : null;

require_once __DIR__ . '/../lib/layout.php';
sx_header(($post ? $post['title'] : 'Publicación') . ' — Foro Shellyx', 'foro', $u);
?>
<section class="section">
  <div class="container">
    <div class="post-view">
      <p style="margin-bottom:14px"><a class="btn btn--muted btn--sm" href="index.php">&larr; Volver al foro</a></p>
      <?php if ($post): ?>
        <span class="eyebrow">Publicación #<?= e($post['id']) ?></span>
        <h1 class="post-view__title"><?= e($post['title']) ?></h1>
        <p class="post-view__meta">// comunidad Shellyx</p>
        <div class="post-view__body"><?= e($post['body']) ?></div>
        <?php
        // VULNERABILIDAD DELIBERADA: "vista previa" del recurso externo mediante
        // include() -> Remote File Inclusion (RFI) con allow_url_include=On.
        if (!empty($post['embed_url'])): ?>
          <div class="embed-box">
            <div class="embed-box__label"><?= sx_icon('firmware', 15) ?> Vista previa del recurso incrustado</div>
            <?php include $post['embed_url']; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="panel panel--pad center">
          <div class="card__icon" style="margin:0 auto 14px"><?= sx_icon('search') ?></div>
          <h1 style="font-size:1.6rem;margin:0 0 .3em">Publicación no encontrada</h1>
          <p class="muted">La publicación que buscas no existe o fue eliminada.</p>
          <p style="margin-top:18px"><a class="btn btn--primary" href="index.php">Volver al foro</a></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php sx_footer(); ?>
