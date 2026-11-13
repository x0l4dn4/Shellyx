<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
$user = current_user();

// Actividad reciente: hilos y respuestas, fusionados y ordenados por fecha.
$items = [];
if ($r = mysqli_query(db(), "SELECT p.id, p.title, p.created_at, u.username AS author
                             FROM posts p JOIN users u ON u.id = p.user_id
                             ORDER BY p.created_at DESC LIMIT 20")) {
    while ($row = mysqli_fetch_assoc($r)) {
        $items[] = ['kind' => 'hilo', 'pid' => (int)$row['id'], 'title' => $row['title'], 'ts' => $row['created_at'], 'author' => $row['author']];
    }
}
if ($r = mysqli_query(db(), "SELECT r.post_id, r.created_at, u.username AS author, p.title
                             FROM replies r JOIN users u ON u.id = r.user_id JOIN posts p ON p.id = r.post_id
                             ORDER BY r.created_at DESC LIMIT 20")) {
    while ($row = mysqli_fetch_assoc($r)) {
        $items[] = ['kind' => 'respuesta', 'pid' => (int)$row['post_id'], 'title' => $row['title'], 'ts' => $row['created_at'], 'author' => $row['author']];
    }
}
usort($items, fn($a, $b) => strcmp($b['ts'], $a['ts']));
$items = array_slice($items, 0, 25);

forum_head('Novedades — Foro Shellyx');
forum_bar($user);
forum_breadcrumb([['Inicio', 'index.php'], ['Novedades', null]]);
?>
<section class="posts"><div class="wrap">
  <div class="fh-panel">
    <div class="fh-ph">Actividad reciente</div>
    <?php foreach ($items as $it): ?>
    <div class="fh-np-item">
      <?= avatar_html($it['author']) ?>
      <div class="t">
        <a href="post.php?id=<?= $it['pid'] ?>"><?= e(strip_tag_prefix($it['title'])) ?></a>
        <span><a href="perfil.php?u=<?= urlencode($it['author']) ?>"><?= e($it['author']) ?></a> <?= $it['kind'] === 'hilo' ? 'abrió el hilo' : 'respondió' ?> · <?= e(hace($it['ts'])) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$items): ?><div class="node-empty">Todavía no hay actividad.</div><?php endif; ?>
  </div>
</div></section>
<?php forum_footer(); ?>
