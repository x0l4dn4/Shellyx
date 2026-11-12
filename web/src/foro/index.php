<?php
require_once __DIR__ . '/lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/layout.php';
$u = current_user();
$res = mysqli_query(db(), "SELECT id, title FROM posts ORDER BY id DESC");

sx_header('Foro — Shellyx', 'foro', $u, 'Foro de la comunidad Shellyx: soporte, módulos y retos.');
?>
<section class="section">
  <div class="container">
    <div class="forum-head">
      <div>
        <span class="eyebrow">Comunidad</span>
        <h1 style="margin:0">Foro Shellyx</h1>
        <p class="muted mono" style="margin:.4em 0 0;font-size:.88rem">conectado como <span class="accent"><?= e($u['username']) ?></span></p>
      </div>
      <a class="btn btn--primary" href="new_post.php"><?= sx_icon('bolt', 18) ?> Nueva publicación</a>
    </div>

    <?php if ($res && mysqli_num_rows($res) > 0): ?>
      <ul class="thread-list">
        <?php while ($row = mysqli_fetch_assoc($res)): ?>
          <li>
            <a class="thread" href="post.php?id=<?= e($row['id']) ?>">
              <span class="thread__hash">#<?= e($row['id']) ?></span>
              <span class="thread__title"><?= e($row['title']) ?></span>
              <span class="thread__arrow"><?= sx_icon('arrow', 16) ?></span>
            </a>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <div class="table-wrap"><div class="empty">// Aún no hay publicaciones. Abre la primera con “Nueva publicación”.</div></div>
    <?php endif; ?>
  </div>
</section>
<?php sx_footer(); ?>
