<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Buscador del foro (lectura pública). Sentencias PREPARADAS: el único SQLi
// del laboratorio sigue siendo el buscador de la tienda.
$user = current_user();
$q = trim($_GET['q'] ?? '');

$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = mysqli_prepare(db(), "SELECT 'hilo' AS kind, p.id AS post_id, p.title, p.body, p.created_at, u.username
                                  FROM posts p JOIN users u ON u.id = p.user_id
                                  WHERE p.title LIKE ? OR p.body LIKE ?
                                  UNION ALL
                                  SELECT 'respuesta', p.id, p.title, r.body, r.created_at, u.username
                                  FROM replies r JOIN posts p ON p.id = r.post_id JOIN users u ON u.id = r.user_id
                                  WHERE r.body LIKE ?
                                  ORDER BY created_at DESC
                                  LIMIT 50");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && $row = mysqli_fetch_assoc($res)) { $results[] = $row; }
}

forum_head(($q !== '' ? "Buscar: $q" : 'Buscar') . ' — Shellyx');
forum_bar($user, '', $q);
forum_breadcrumb([['Inicio', 'index.php'], ['Buscar', null]]);
?>
<section class="page"><div class="wrap">
  <div class="page-head">
    <h1>Buscar en el foro</h1>
    <form class="search search-lg" method="get" action="buscar.php" role="search">
      <?= SEARCH_ICON ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Título, contenido o respuesta…" aria-label="Buscar en el foro" autofocus>
    </form>
    <?php if ($q !== ''): ?>
      <p><?= count($results) ?> <?= count($results) === 1 ? 'resultado' : 'resultados' ?> para «<strong><?= e($q) ?></strong>».</p>
    <?php endif; ?>
  </div>

  <?php if ($q !== ''): ?>
    <?php if ($results): ?>
    <div class="feed">
      <?php foreach ($results as $it): ?>
      <article class="feed-item">
        <?= avatar_html($it['username']) ?>
        <div class="fi-main">
          <div class="fi-head">
            <a href="post.php?id=<?= (int)$it['post_id'] ?>"><?= render_title_with_tag($it['title']) ?></a>
            <span><?= $it['kind'] === 'hilo' ? 'Hilo' : 'Respuesta' ?> de <?= user_link($it['username']) ?> · <?= e(hace($it['created_at'])) ?></span>
          </div>
          <p class="fi-text"><?= e(excerpt($it['body'])) ?></p>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="node node-empty">Sin resultados. Prueba con otro término.</div>
    <?php endif; ?>
  <?php endif; ?>
</div></section>
<?php forum_footer(); ?>
