<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// El índice se ve sin sesión; publicar exige login (new_post.php).
$user = current_user();

// Hilos + autor.
$threads = [];
$res = mysqli_query(db(), "SELECT p.id, p.title, p.category, p.created_at, p.user_id, u.username AS author
                           FROM posts p JOIN users u ON u.id = p.user_id");
while ($res && $row = mysqli_fetch_assoc($res)) {
    $row['reply_count'] = 0;
    $row['last_time']   = $row['created_at'];
    $row['last_user']   = $row['author'];
    $threads[(int)$row['id']] = $row;
}

// Respuestas (ordenadas ASC): cuenta y "último mensaje" por hilo.
$total_replies = 0;
$res = mysqli_query(db(), "SELECT r.post_id, r.created_at, u.username
                           FROM replies r JOIN users u ON u.id = r.user_id
                           ORDER BY r.created_at ASC, r.id ASC");
while ($res && $row = mysqli_fetch_assoc($res)) {
    $total_replies++;
    $pid = (int)$row['post_id'];
    if (!isset($threads[$pid])) continue;
    $threads[$pid]['reply_count']++;
    $threads[$pid]['last_time'] = $row['created_at'];
    $threads[$pid]['last_user'] = $row['username'];
}

// Agrupar por categoría y ordenar por última actividad (desc).
$by_cat = [];
foreach ($threads as $t) {
    $cat = isset(FORUM_CATEGORIES[$t['category']]) ? $t['category'] : 'general';
    $by_cat[$cat][] = $t;
}
foreach ($by_cat as &$list) {
    usort($list, fn($a, $b) => strcmp($b['last_time'], $a['last_time']));
}
unset($list);

// Estadísticas de comunidad.
$total_hilos = count($threads);
$total_msgs  = $total_hilos + $total_replies;
$miembros = 0; $ultimo = '';
if ($r = mysqli_query(db(), "SELECT COUNT(*) c FROM users"))            { $miembros = (int)(mysqli_fetch_assoc($r)['c'] ?? 0); }
if ($r = mysqli_query(db(), "SELECT username FROM users ORDER BY id DESC LIMIT 1")) { $ultimo = mysqli_fetch_assoc($r)['username'] ?? ''; }

forum_head('Foros — Shellyx');
forum_bar($user);
forum_breadcrumb([['Inicio', null]]);
?>
<section class="stats"><div class="wrap">
  <div class="stat"><b><?= number_format($total_hilos, 0, ',', '.') ?></b><span>Hilos</span></div>
  <div class="stat"><b><?= number_format($total_msgs, 0, ',', '.') ?></b><span>Mensajes</span></div>
  <div class="stat"><b><?= number_format($miembros, 0, ',', '.') ?></b><span>Miembros</span></div>
  <?php if ($ultimo !== ''): ?><div class="stat who"><b><?= e($ultimo) ?></b><span>Último registrado</span></div><?php endif; ?>
</div></section>

<main class="board"><div class="wrap">
<?php foreach (FORUM_CATEGORIES as $key => [$name, $desc]):
    $list = $by_cat[$key] ?? [];
    $nh = count($list);
    $nm = $nh;
    foreach ($list as $t) { $nm += (int)$t['reply_count']; }
?>
  <section class="node">
    <div class="node-head">
      <div class="node-ico"><?= category_icon($key) ?></div>
      <div><h2><?= e($name) ?></h2><p><?= e($desc) ?></p></div>
      <div class="ns"><?= $nh ?> hilos · <?= $nm ?> mensajes</div>
    </div>
    <?php if ($list): ?>
    <ul class="threads">
      <?php foreach ($list as $t): ?>
      <li class="thread">
        <?= avatar_html($t['author'], 't-ava') ?>
        <div class="t-main">
          <a class="t-title" href="post.php?id=<?= (int)$t['id'] ?>"><?= render_title_with_tag($t['title']) ?></a>
          <div class="t-sub">iniciado por <a href="post.php?id=<?= (int)$t['id'] ?>"><?= e($t['author']) ?></a> · <?= e(hace($t['created_at'])) ?></div>
        </div>
        <div class="t-count"><b><?= (int)$t['reply_count'] ?></b><span><?= $t['reply_count'] == 1 ? 'respuesta' : 'respuestas' ?></span></div>
        <div class="t-last">
          <?= avatar_html($t['last_user']) ?>
          <div class="t-last-txt"><a href="post.php?id=<?= (int)$t['id'] ?>"><?= e($t['last_user']) ?></a><span><?= e(hace($t['last_time'])) ?></span></div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <div class="node-empty">Aún no hay hilos en esta categoría.</div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
</div></main>
<?php forum_footer(); ?>
