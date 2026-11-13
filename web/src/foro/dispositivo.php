<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
$user = current_user();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Producto (sentencia preparada).
$stmt = mysqli_prepare(db(), "SELECT id, name, price, description FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;

// Hilos del dispositivo (sentencia preparada).
$threads = [];
if ($product) {
    $stmt = mysqli_prepare(db(), "SELECT p.id, p.title, p.created_at, u.username AS author,
                                     (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS reply_count
                                  FROM posts p JOIN users u ON u.id = p.user_id
                                  WHERE p.product_id = ? ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $rr = mysqli_stmt_get_result($stmt);
    while ($rr && $row = mysqli_fetch_assoc($rr)) { $threads[] = $row; }
}

forum_head(($product ? $product['name'] : 'Dispositivo') . ' — Foro Shellyx');
forum_bar($user);
?>
<?php if ($product): ?>
<?php forum_breadcrumb([['Inicio', 'index.php'], ['Dispositivos', 'index.php'], [$product['name'], null]]); ?>
<div class="thread-head"><div class="wrap">
  <h1><?= e($product['name']) ?></h1>
  <div class="th-meta"><?= e($product['description']) ?><span class="sep">·</span><?= (int)$product['price'] ?> €<span class="sep">·</span><a href="/tienda/">Ver en la tienda</a></div>
</div></div>

<section class="posts"><div class="wrap">
  <div class="dev-actions">
    <span><?= count($threads) ?> <?= count($threads) == 1 ? 'hilo' : 'hilos' ?></span>
    <?php if ($user): ?>
      <a class="btn btn-accent" href="new_post.php?dev=<?= (int)$product['id'] ?>">Nueva publicación</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="login.php">Inicia sesión para publicar</a>
    <?php endif; ?>
  </div>
  <?php if ($threads): ?>
  <ul class="threads simple">
    <?php foreach ($threads as $t): ?>
    <li class="thread">
      <?= avatar_html($t['author'], 't-ava') ?>
      <div class="t-main">
        <a class="t-title" href="post.php?id=<?= (int)$t['id'] ?>"><?= render_title_with_tag($t['title']) ?></a>
        <div class="t-sub">iniciado por <a href="perfil.php?u=<?= urlencode($t['author']) ?>"><?= e($t['author']) ?></a> · <?= e(hace($t['created_at'])) ?></div>
      </div>
      <div class="t-count"><b><?= (int)$t['reply_count'] ?></b><span><?= $t['reply_count'] == 1 ? 'respuesta' : 'respuestas' ?></span></div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
  <div class="node-empty">Aún no hay hilos sobre este dispositivo. ¡Abre el primero!</div>
  <?php endif; ?>
</div></section>
<?php else: ?>
<div class="notfound"><div class="wrap"><p>Dispositivo no encontrado. <a href="index.php">Volver al foro</a>.</p></div></div>
<?php endif; ?>
<?php forum_footer(); ?>
