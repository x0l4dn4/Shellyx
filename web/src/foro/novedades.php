<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// LECTURA PÚBLICA: actividad reciente del foro (hilos nuevos + respuestas).
$user = current_user();

$items = [];
$res = mysqli_query(db(), "SELECT 'hilo' AS kind, p.id AS post_id, p.title, p.body, p.category, p.created_at, u.username
                           FROM posts p JOIN users u ON u.id = p.user_id
                           UNION ALL
                           SELECT 'respuesta', p.id, p.title, r.body, p.category, r.created_at, u.username
                           FROM replies r JOIN posts p ON p.id = r.post_id JOIN users u ON u.id = r.user_id
                           ORDER BY created_at DESC
                           LIMIT 30");
while ($res && $row = mysqli_fetch_assoc($res)) { $items[] = $row; }

forum_head('Novedades — Shellyx');
forum_bar($user, 'novedades');
forum_breadcrumb([['Inicio', 'index.php'], ['Novedades', null]]);
?>
<section class="page"><div class="wrap">
  <div class="page-head">
    <h1>Novedades</h1>
    <p>Lo último que se ha movido en el foro: hilos nuevos y respuestas.</p>
  </div>
  <?php if ($items): ?>
  <div class="feed">
    <?php foreach ($items as $it): ?>
    <article class="feed-item">
      <?= avatar_html($it['username']) ?>
      <div class="fi-main">
        <div class="fi-head">
          <?= user_link($it['username']) ?>
          <?= $it['kind'] === 'hilo' ? 'abrió el hilo' : 'respondió en' ?>
          <a href="post.php?id=<?= (int)$it['post_id'] ?>"><?= e(strip_tag_prefix($it['title'])) ?></a>
          <span><?= e(FORUM_CATEGORIES[$it['category']][0] ?? FORUM_CATEGORIES['general'][0]) ?> · <?= e(hace($it['created_at'])) ?></span>
        </div>
        <p class="fi-text"><?= e(excerpt($it['body'])) ?></p>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="node node-empty">Todavía no hay actividad.</div>
  <?php endif; ?>
</div></section>
<?php forum_footer(); ?>
