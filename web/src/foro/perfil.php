<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
$user = current_user();

$uname = $_GET['u'] ?? '';
// Usuario del perfil (sentencia preparada).
$stmt = mysqli_prepare(db(), "SELECT id, username FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $uname);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;

$threads = []; $replies = [];
if ($profile) {
    $uid = (int)$profile['id'];
    $stmt = mysqli_prepare(db(), "SELECT id, title, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $rr = mysqli_stmt_get_result($stmt);
    while ($rr && $row = mysqli_fetch_assoc($rr)) { $threads[] = $row; }

    $stmt = mysqli_prepare(db(), "SELECT r.body, r.created_at, r.post_id, p.title
                                  FROM replies r JOIN posts p ON p.id = r.post_id
                                  WHERE r.user_id = ? ORDER BY r.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $rr = mysqli_stmt_get_result($stmt);
    while ($rr && $row = mysqli_fetch_assoc($rr)) { $replies[] = $row; }
}

forum_head(($profile ? $profile['username'] : 'Perfil') . ' — Foro Shellyx');
forum_bar($user);
?>
<?php if ($profile): [$rl, $rc] = member_role($profile['username']); ?>
<?php forum_breadcrumb([['Inicio', 'index.php'], ['Miembros', 'miembros.php'], [$profile['username'], null]]); ?>
<div class="thread-head"><div class="wrap">
  <div class="profile-head">
    <?= avatar_html($profile['username'], 'uc-ava') ?>
    <div>
      <h1><?= e($profile['username']) ?></h1>
      <div class="th-meta"><span class="role <?= $rc ?>"><?= e($rl) ?></span><span class="sep">·</span>desde <?= member_since($profile['username']) ?><span class="sep">·</span><?= count($threads) ?> hilos<span class="sep">·</span><?= count($replies) ?> respuestas</div>
    </div>
  </div>
</div></div>

<section class="posts"><div class="wrap">
  <div class="fh-bhead"><h2>Hilos de <?= e($profile['username']) ?></h2></div>
  <?php if ($threads): ?>
  <ul class="threads simple">
    <?php foreach ($threads as $t): ?>
    <li class="thread">
      <?= avatar_html($profile['username'], 't-ava') ?>
      <div class="t-main">
        <a class="t-title" href="post.php?id=<?= (int)$t['id'] ?>"><?= render_title_with_tag($t['title']) ?></a>
        <div class="t-sub"><?= e(hace($t['created_at'])) ?></div>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?><div class="node-empty">No ha abierto hilos todavía.</div><?php endif; ?>

  <div class="fh-bhead" style="margin-top:20px"><h2>Respuestas de <?= e($profile['username']) ?></h2></div>
  <?php if ($replies): ?>
  <div class="fh-panel">
    <?php foreach ($replies as $rp): ?>
    <div class="fh-np-item">
      <?= avatar_html($profile['username']) ?>
      <div class="t">
        <a href="post.php?id=<?= (int)$rp['post_id'] ?>"><?= e(strip_tag_prefix($rp['title'])) ?></a>
        <span><?= e(hace($rp['created_at'])) ?> — <?= e(mb_strimwidth($rp['body'], 0, 90, '…', 'UTF-8')) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?><div class="node-empty">No ha respondido todavía.</div><?php endif; ?>
</div></section>
<?php else: ?>
<div class="notfound"><div class="wrap"><p>Usuario no encontrado. <a href="miembros.php">Ver miembros</a>.</p></div></div>
<?php endif; ?>
<?php forum_footer(); ?>
