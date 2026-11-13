<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Vista de un hilo.
$user = current_user();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Carga del hilo.
$stmt = mysqli_prepare(db(), "SELECT p.id, p.title, p.body, p.embed_url, p.category, p.created_at, p.user_id, p.product_id,
                                     u.username AS author, pr.name AS device_name
                              FROM posts p JOIN users u ON u.id = p.user_id
                              LEFT JOIN products pr ON pr.id = p.product_id
                              WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$post = $res ? mysqli_fetch_assoc($res) : null;

// Publicar una respuesta. PRG (redirect tras POST) para evitar el reenvío del
// formulario al recargar.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user !== null && $post) {
    $body = trim($_POST['reply_body'] ?? '');
    if ($body !== '') {
        $pid = (int)$post['id'];
        $uid = (int)$user['id'];
        $stmt = mysqli_prepare(db(), "INSERT INTO replies (post_id, user_id, body) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iis", $pid, $uid, $body);
        mysqli_stmt_execute($stmt);
    }
    header('Location: post.php?id=' . (int)$post['id']);
    exit;
}

// Respuestas del hilo.
$replies = [];
if ($post) {
    $stmt = mysqli_prepare(db(), "SELECT r.body, r.created_at, r.user_id, u.username AS author
                                  FROM replies r JOIN users u ON u.id = r.user_id
                                  WHERE r.post_id = ? ORDER BY r.created_at ASC, r.id ASC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $rr = mysqli_stmt_get_result($stmt);
    while ($rr && $row = mysqli_fetch_assoc($rr)) { $replies[] = $row; }
}

forum_head(($post ? strip_tag_prefix($post['title']) : 'Publicación') . ' — Shellyx');
forum_bar($user);
?>
<?php if ($post): ?>
<?php
    if (!empty($post['product_id']) && !empty($post['device_name'])) {
        $crumb = [
            ['Inicio', 'index.php'],
            ['Dispositivos', 'index.php#dispositivos'],
            [$post['device_name'], device_url((int)$post['product_id'])],
            [strip_tag_prefix($post['title']), null],
        ];
    } else {
        $catName = FORUM_CATEGORIES[$post['category']][0] ?? FORUM_CATEGORIES['general'][0];
        $crumb = [
            ['Inicio', 'index.php'],
            [$catName, 'index.php#cat-' . ($post['category'] ?: 'general')],
            [strip_tag_prefix($post['title']), null],
        ];
    }
    forum_breadcrumb($crumb);
?>
<div class="thread-head"><div class="wrap">
  <h1><?= render_title_with_tag($post['title']) ?></h1>
  <div class="th-meta">iniciado por <?= user_link($post['author']) ?><span class="sep">·</span><?= e(hace($post['created_at'])) ?><span class="sep">·</span><?= count($replies) ?> <?= count($replies) == 1 ? 'respuesta' : 'respuestas' ?></div>
</div></div>

<section class="posts"><div class="wrap">

  <article class="post">
    <?= usercard_html($post['author']) ?>
    <div class="post-body">
      <div class="post-topmeta"><span><?= e(hace($post['created_at'])) ?></span><span class="num">#1</span></div>
      <div class="post-content">
        <p><?= nl2br(e($post['body'])) ?></p>
<?php
        // Recurso incrustado: se muestra como enlace externo.
        if (!empty($post['embed_url'])) {
            $embed  = (string) $post['embed_url'];
            $scheme = strtolower((string) parse_url($embed, PHP_URL_SCHEME));
            if (filter_var($embed, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)) {
                echo '<p class="embed-link"><a href="' . e($embed) . '" target="_blank" rel="noopener noreferrer nofollow">' . e($embed) . '</a></p>';
            } else {
                echo '<p class="embed-fail">Recurso no válido.</p>';
            }
        }
?>
      </div>
    </div>
  </article>

<?php $n = 2; foreach ($replies as $rep): ?>
  <article class="post">
    <?= usercard_html($rep['author']) ?>
    <div class="post-body">
      <div class="post-topmeta"><span><?= e(hace($rep['created_at'])) ?></span><span class="num">#<?= $n ?></span></div>
      <div class="post-content"><p><?= nl2br(e($rep['body'])) ?></p></div>
    </div>
  </article>
<?php $n++; endforeach; ?>

<?php if ($user !== null): ?>
  <form class="reply-form" method="post" action="post.php?id=<?= (int)$post['id'] ?>">
    <div class="rf-head"><?= avatar_html($user['username']) ?><span>Responder como <strong><?= e($user['username']) ?></strong></span></div>
    <div class="field"><textarea name="reply_body" placeholder="Escribe tu respuesta…" required></textarea></div>
    <div class="rf-actions"><button class="btn btn-accent" type="submit">Publicar respuesta</button></div>
  </form>
<?php else: ?>
  <div class="replybar">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--faint)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
    <p><strong>Inicia sesión para responder.</strong> La lectura es pública; publicar es solo para miembros.</p>
    <a class="btn btn-accent" href="login.php" style="margin-left:auto">Iniciar sesión</a>
  </div>
<?php endif; ?>

</div></section>
<?php else: ?>
<div class="notfound"><div class="wrap">
  <p>Publicación no encontrada. <a href="index.php">Volver al foro</a>.</p>
</div></div>
<?php endif; ?>
<?php forum_footer(); ?>
