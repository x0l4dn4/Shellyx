<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
$user = current_user();

$q = trim($_GET['q'] ?? '');

// Búsqueda por título/cuerpo de hilos y cuerpo de respuestas (sentencia preparada).
$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = mysqli_prepare(db(), "SELECT DISTINCT p.id, p.title, p.created_at, u.username AS author
                                  FROM posts p JOIN users u ON u.id = p.user_id
                                  LEFT JOIN replies r ON r.post_id = p.id
                                  WHERE p.title LIKE ? OR p.body LIKE ? OR r.body LIKE ?
                                  ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rr = mysqli_stmt_get_result($stmt);
    while ($rr && $row = mysqli_fetch_assoc($rr)) { $results[] = $row; }
}

forum_head('Buscar — Foro Shellyx');
forum_bar($user);
forum_breadcrumb([['Inicio', 'index.php'], ['Buscar', null]]);
?>
<section class="posts"><div class="wrap">
  <form class="search-page" method="get" action="buscar.php">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar hilos y respuestas…" aria-label="Buscar" autofocus>
    <button class="btn btn-accent" type="submit">Buscar</button>
  </form>
  <?php if ($q !== ''): ?>
    <div class="dev-actions"><span><?= count($results) ?> resultado<?= count($results) == 1 ? '' : 's' ?> para &ldquo;<?= e($q) ?>&rdquo;</span></div>
    <?php if ($results): ?>
    <ul class="threads simple">
      <?php foreach ($results as $t): ?>
      <li class="thread">
        <?= avatar_html($t['author'], 't-ava') ?>
        <div class="t-main">
          <a class="t-title" href="post.php?id=<?= (int)$t['id'] ?>"><?= render_title_with_tag($t['title']) ?></a>
          <div class="t-sub">iniciado por <a href="perfil.php?u=<?= urlencode($t['author']) ?>"><?= e($t['author']) ?></a> · <?= e(hace($t['created_at'])) ?></div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?><div class="node-empty">Sin resultados.</div><?php endif; ?>
  <?php endif; ?>
</div></section>
<?php forum_footer(); ?>
