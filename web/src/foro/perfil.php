<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Perfil de un usuario. Sin ?u= muestra el del usuario en sesión.
$user = current_user();
$name = trim($_GET['u'] ?? '');
if ($name === '') {
    require_login();
    $name = $user['username'];
}

$stmt = mysqli_prepare(db(), "SELECT id, username FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$profile = $res ? mysqli_fetch_assoc($res) : null;

$threads = $replies = [];
if ($profile) {
    $uid = (int)$profile['id'];

    $stmt = mysqli_prepare(db(), "SELECT p.id, p.title, p.category, p.created_at,
                                         (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS reply_count
                                  FROM posts p WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($r && $row = mysqli_fetch_assoc($r)) { $threads[] = $row; }

    $stmt = mysqli_prepare(db(), "SELECT r.body, r.created_at, p.id AS post_id, p.title
                                  FROM replies r JOIN posts p ON p.id = r.post_id
                                  WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($r && $row = mysqli_fetch_assoc($r)) { $replies[] = $row; }
}

$is_me = $profile && $user && (int)$user['id'] === (int)$profile['id'];
$tab = ($_GET['tab'] ?? '') === 'respuestas' ? 'respuestas' : 'hilos';

forum_head(($profile ? $profile['username'] : 'Perfil') . ' — Shellyx');
forum_bar($user, $is_me ? '' : 'miembros');
forum_breadcrumb([['Inicio', 'index.php'], ['Miembros', 'miembros.php'], [$profile['username'] ?? 'Perfil', null]]);
?>
<?php if (!$profile): ?>
<div class="notfound"><div class="wrap">
  <p>Ese miembro no existe. <a href="miembros.php">Ver la lista de miembros</a>.</p>
</div></div>
<?php else: [$roleLabel, $roleClass] = member_role($profile['username']); ?>
<section class="page"><div class="wrap">
  <div class="profile-card">
    <?= avatar_html($profile['username'], 'pf-ava') ?>
    <div class="pf-main">
      <h1><?= e($profile['username']) ?><?php if ($is_me): ?> <span class="pf-you">tú</span><?php endif; ?></h1>
      <div class="pf-meta"><span class="role <?= $roleClass ?>"><?= e($roleLabel) ?></span><span>Miembro desde <?= member_since($profile['username']) ?></span></div>
    </div>
    <dl class="pf-stats">
      <div><dt>Hilos</dt><dd><?= count($threads) ?></dd></div>
      <div><dt>Respuestas</dt><dd><?= count($replies) ?></dd></div>
      <div><dt>Mensajes</dt><dd><?= count($threads) + count($replies) ?></dd></div>
    </dl>
    <?php if ($is_me): ?><a class="btn btn-accent pf-action" href="new_post.php">Nueva publicación</a><?php endif; ?>
  </div>

  <nav class="tabs">
    <a href="<?= profile_url($profile['username']) ?>"<?= $tab === 'hilos' ? ' class="on"' : '' ?>>Hilos <span><?= count($threads) ?></span></a>
    <a href="<?= profile_url($profile['username']) ?>&amp;tab=respuestas"<?= $tab === 'respuestas' ? ' class="on"' : '' ?>>Respuestas <span><?= count($replies) ?></span></a>
  </nav>

  <?php if ($tab === 'hilos'): ?>
    <?php if ($threads): ?>
    <div class="node"><ul class="threads">
      <?php foreach ($threads as $t): ?>
      <li class="thread thread-compact">
        <div class="t-main">
          <a class="t-title" href="post.php?id=<?= (int)$t['id'] ?>"><?= render_title_with_tag($t['title']) ?></a>
          <div class="t-sub"><?= e(FORUM_CATEGORIES[$t['category']][0] ?? FORUM_CATEGORIES['general'][0]) ?> · <?= e(hace($t['created_at'])) ?></div>
        </div>
        <div class="t-count"><b><?= (int)$t['reply_count'] ?></b><span><?= $t['reply_count'] == 1 ? 'respuesta' : 'respuestas' ?></span></div>
      </li>
      <?php endforeach; ?>
    </ul></div>
    <?php else: ?>
    <div class="node node-empty"><?= $is_me ? 'Aún no has abierto ningún hilo.' : 'Este miembro aún no ha abierto ningún hilo.' ?></div>
    <?php endif; ?>
  <?php else: ?>
    <?php if ($replies): ?>
    <div class="feed">
      <?php foreach ($replies as $rp): ?>
      <article class="feed-item">
        <div class="fi-head">en <a href="post.php?id=<?= (int)$rp['post_id'] ?>"><?= e(strip_tag_prefix($rp['title'])) ?></a><span><?= e(hace($rp['created_at'])) ?></span></div>
        <p class="fi-text"><?= e(excerpt($rp['body'])) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="node node-empty"><?= $is_me ? 'Aún no has respondido en ningún hilo.' : 'Este miembro aún no ha respondido en ningún hilo.' ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div></section>
<?php endif; ?>
<?php forum_footer(); ?>
