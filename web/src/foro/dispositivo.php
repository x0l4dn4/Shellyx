<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Subforo de un dispositivo: hilos asociados a un producto de la tienda.
$user = current_user();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare(db(), "SELECT id, name, price, description FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$device = $res ? mysqli_fetch_assoc($res) : null;

$threads = [];
if ($device) {
    $stmt = mysqli_prepare(db(), "SELECT p.id, p.title, p.created_at, u.username AS author,
            (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS reply_count,
            (SELECT MAX(r.created_at) FROM replies r WHERE r.post_id = p.id) AS last_reply
            FROM posts p JOIN users u ON u.id = p.user_id
            WHERE p.product_id = ? ORDER BY p.created_at DESC, p.id DESC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($r && $row = mysqli_fetch_assoc($r)) {
        $row['last_time'] = $row['last_reply'] ?: $row['created_at'];
        $threads[] = $row;
    }
    usort($threads, fn($a, $b) => strcmp($b['last_time'], $a['last_time']));
}

forum_head(($device ? $device['name'] : 'Dispositivo') . ' — Foro Shellyx');
forum_bar($user, 'foros');
?>
<?php if (!$device): ?>
<?php forum_breadcrumb([['Inicio', 'index.php'], ['Dispositivo', null]]); ?>
<div class="notfound"><div class="wrap">
  <p>Ese dispositivo no existe. <a href="index.php#dispositivos">Ver todos los dispositivos</a>.</p>
</div></div>
<?php else: ?>
<?php forum_breadcrumb([['Inicio', 'index.php'], ['Dispositivos', 'index.php#dispositivos'], [$device['name'], null]]); ?>
<section class="page"><div class="wrap">
  <div class="dev-head">
    <span class="dev-head__media"><?= device_icon_svg((int)$device['id']) ?></span>
    <div class="dev-head__main">
      <span class="eyebrow" style="margin:0">Subforo de dispositivo</span>
      <h1><?= e($device['name']) ?></h1>
      <p class="dev-head__desc"><?= e($device['description']) ?></p>
    </div>
    <div class="dev-head__actions">
      <a class="btn btn-ghost" href="/tienda/">Ver en la tienda</a>
      <a class="btn btn-accent" href="new_post.php?product=<?= (int)$device['id'] ?>">Nueva publicación</a>
    </div>
  </div>

  <?php if ($threads): ?>
  <div class="node"><ul class="threads">
    <?php foreach ($threads as $t): ?>
    <li class="thread">
      <?= avatar_html($t['author'], 't-ava') ?>
      <div class="t-main">
        <a class="t-title" href="post.php?id=<?= (int)$t['id'] ?>"><?= render_title_with_tag($t['title']) ?></a>
        <div class="t-sub">iniciado por <?= user_link($t['author']) ?> · <?= e(hace($t['created_at'])) ?></div>
      </div>
      <div class="t-count"><b><?= (int)$t['reply_count'] ?></b><span><?= $t['reply_count'] == 1 ? 'respuesta' : 'respuestas' ?></span></div>
    </li>
    <?php endforeach; ?>
  </ul></div>
  <?php else: ?>
  <div class="node node-empty">Todavía no hay hilos sobre el <?= e($device['name']) ?>. <?php if ($user): ?>¡Abre el primero!<?php else: ?><a href="login.php">Inicia sesión</a> para abrir el primero.<?php endif; ?></div>
  <?php endif; ?>
</div></section>
<?php endif; ?>
<?php forum_footer(); ?>
