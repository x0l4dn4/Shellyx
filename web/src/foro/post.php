<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// LECTURA PÚBLICA: ver un hilo no exige sesión. El vector RFI se crea en
// new_post.php (que SÍ exige login), no aquí.
$user = current_user();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Carga del hilo con sentencia preparada (la única SQLi del reto es la de la tienda).
$stmt = mysqli_prepare(db(), "SELECT p.id, p.title, p.body, p.embed_url, p.category, p.created_at, p.user_id, u.username AS author
                              FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$post = $res ? mysqli_fetch_assoc($res) : null;

// ESCRITURA GATEADA: publicar una respuesta exige sesión. INSERT con sentencia
// preparada (sin SQLi) y la salida se escapa con e() en el render (sin XSS): la
// única vuln del foro sigue siendo la RFI de embed_url. PRG (redirect tras POST)
// para evitar el reenvío del formulario al recargar.
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

// Respuestas del hilo (sentencia preparada).
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

// Nº de mensajes por usuario (para la tarjeta), indexado por nombre.
$counts = [];
$cq = mysqli_query(db(), "SELECT u.username, COUNT(*) c
                          FROM (SELECT user_id FROM posts UNION ALL SELECT user_id FROM replies) t
                          JOIN users u ON u.id = t.user_id GROUP BY u.username");
while ($cq && $row = mysqli_fetch_assoc($cq)) { $counts[$row['username']] = (int)$row['c']; }

forum_head(($post ? strip_tag_prefix($post['title']) : 'Publicación') . ' — Shellyx');
forum_bar($user);
?>
<?php if ($post): ?>
<?php
    $catName = FORUM_CATEGORIES[$post['category']][0] ?? FORUM_CATEGORIES['general'][0];
    forum_breadcrumb([
        ['Inicio', 'index.php'],
        [$catName, 'index.php'],
        [strip_tag_prefix($post['title']), null],
    ]);
?>
<div class="thread-head"><div class="wrap">
  <h1><?= render_title_with_tag($post['title']) ?></h1>
  <div class="th-meta">iniciado por <?= user_link($post['author']) ?><span class="sep">·</span><?= e(hace($post['created_at'])) ?><span class="sep">·</span><?= count($replies) ?> <?= count($replies) == 1 ? 'respuesta' : 'respuestas' ?></div>
</div></div>

<section class="posts"><div class="wrap">

  <article class="post">
    <?= usercard_html($post['author'], $counts[$post['author']] ?? 1) ?>
    <div class="post-body">
      <div class="post-topmeta"><span><?= e(hace($post['created_at'])) ?></span><span class="num">#1</span></div>
      <div class="post-content">
        <p><?= nl2br(e($post['body'])) ?></p>
<?php
        // VULNERABILIDAD DELIBERADA: "vista previa" del recurso externo mediante
        // include() -> Remote File Inclusion (RFI) con allow_url_include=On.
        // El '@' solo evita volcar warnings de PHP cuando el recurso no existe o
        // no es incluible (p. ej. "test"); la inclusión —y la RFI— no cambia.
        if (!empty($post['embed_url'])) {
            if ((@include $post['embed_url']) === false) {
                echo '<p class="embed-fail">No se pudo cargar la vista previa de «' . e($post['embed_url']) . '».</p>';
            }
        }
?>
      </div>
    </div>
  </article>

<?php $n = 2; foreach ($replies as $rep): ?>
  <article class="post">
    <?= usercard_html($rep['author'], $counts[$rep['author']] ?? 1) ?>
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
