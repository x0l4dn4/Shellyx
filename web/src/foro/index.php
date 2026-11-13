<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Índice del foro.
$user = current_user();

// Hilos del foro general (sin dispositivo) + autor.
$threads = [];
$res = mysqli_query(db(), "SELECT p.id, p.title, p.category, p.created_at, p.user_id, u.username AS author
                           FROM posts p JOIN users u ON u.id = p.user_id
                           WHERE p.product_id IS NULL");
while ($res && $row = mysqli_fetch_assoc($res)) {
    $row['reply_count'] = 0;
    $row['last_time']   = $row['created_at'];
    $row['last_user']   = $row['author'];
    $threads[(int)$row['id']] = $row;
}

// Respuestas: cuenta y "último mensaje" por hilo.
$res = mysqli_query(db(), "SELECT r.post_id, r.created_at, u.username
                           FROM replies r JOIN users u ON u.id = r.user_id
                           ORDER BY r.created_at ASC, r.id ASC");
while ($res && $row = mysqli_fetch_assoc($res)) {
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

// Dispositivos (subforos) con nº de hilos y mensajes.
$devices = [];
$res = mysqli_query(db(), "SELECT pr.id, pr.name,
        (SELECT COUNT(*) FROM posts p WHERE p.product_id = pr.id) AS hilos,
        (SELECT COUNT(*) FROM replies r JOIN posts p ON p.id = r.post_id WHERE p.product_id = pr.id) AS resp
        FROM products pr");
while ($res && $row = mysqli_fetch_assoc($res)) {
    $row['mensajes'] = (int)$row['hilos'] + (int)$row['resp'];
    $devices[(int)$row['id']] = $row;
}

// Actividad reciente para el panel "Nuevos posts".
$recent = [];
$res = mysqli_query(db(), "SELECT p.id AS post_id, p.title, p.created_at, u.username
                           FROM posts p JOIN users u ON u.id = p.user_id
                           UNION ALL
                           SELECT p.id, p.title, r.created_at, u.username
                           FROM replies r JOIN posts p ON p.id = r.post_id JOIN users u ON u.id = r.user_id
                           ORDER BY created_at DESC LIMIT 6");
while ($res && $row = mysqli_fetch_assoc($res)) { $recent[] = $row; }

// Estadísticas de comunidad.
$total_hilos = 0; $total_msgs = 0; $miembros = 0;
if ($r = mysqli_query(db(), "SELECT COUNT(*) c FROM posts"))   { $total_hilos = (int)(mysqli_fetch_assoc($r)['c'] ?? 0); }
if ($r = mysqli_query(db(), "SELECT COUNT(*) c FROM replies")) { $total_msgs  = $total_hilos + (int)(mysqli_fetch_assoc($r)['c'] ?? 0); }
if ($r = mysqli_query(db(), "SELECT COUNT(*) c FROM users"))   { $miembros = (int)(mysqli_fetch_assoc($r)['c'] ?? 0); }

forum_head('Foros — Shellyx');
forum_bar($user, 'foros');
?>
<div class="forum-shell"><div class="wrap wrap-wide">
<div class="xgrid">

  <!-- IZQUIERDA: temas -->
  <aside class="col-left">
    <div class="side-card">
      <div class="side-card__head">Temas</div>
      <nav class="topic-list">
        <?php foreach (FORUM_CATEGORIES as $key => [$name, $desc]): ?>
          <a href="#cat-<?= e($key) ?>"><span class="topic-ic ic-<?= e($key) ?>"><?= category_icon($key) ?></span><?= e($name) ?></a>
        <?php endforeach; ?>
        <a href="#dispositivos"><span class="topic-ic ic-dev"><?= device_icon_svg(1) ?></span>Dispositivos</a>
      </nav>
    </div>
  </aside>

  <!-- CENTRO: categorías + dispositivos -->
  <main class="col-main">
    <div class="board-head">
      <h1>Foros Shellyx</h1>
      <a class="board-head__link" href="novedades.php">Nuevos posts</a>
    </div>

    <?php foreach (FORUM_CATEGORIES as $key => [$name, $desc]):
        $list = $by_cat[$key] ?? [];
        $nh = count($list); $nm = $nh;
        foreach ($list as $t) { $nm += (int)$t['reply_count']; }
    ?>
      <section class="node" id="cat-<?= e($key) ?>">
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
              <div class="t-sub">iniciado por <?= user_link($t['author']) ?> · <?= e(hace($t['created_at'])) ?></div>
            </div>
            <div class="t-count"><b><?= (int)$t['reply_count'] ?></b><span><?= $t['reply_count'] == 1 ? 'respuesta' : 'respuestas' ?></span></div>
            <div class="t-last">
              <?= avatar_html($t['last_user']) ?>
              <div class="t-last-txt"><?= user_link($t['last_user']) ?><span><?= e(hace($t['last_time'])) ?></span></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="node-empty">Aún no hay hilos en esta categoría.</div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <!-- Subforos por dispositivo -->
    <section id="dispositivos" class="devices">
      <div class="devices__intro">
        <h2>Dispositivos</h2>
        <p>Un subforo por cada producto de la tienda: dudas, mods y experiencias.</p>
      </div>
      <?php foreach (DEVICE_GROUPS as $group => $ids): ?>
        <div class="dev-group">
          <div class="dev-group__head">
            <h3><?= e($group) ?></h3>
            <a class="dev-group__link" href="/tienda/">Ver en tienda</a>
          </div>
          <div class="dev-grid">
            <?php foreach ($ids as $pid): if (!isset($devices[$pid])) continue; $d = $devices[$pid]; ?>
              <a class="dev-card" href="<?= device_url($pid) ?>">
                <span class="dev-media"><?= device_icon_svg($pid) ?></span>
                <div class="dev-main">
                  <div class="dev-name"><?= e($d['name']) ?></div>
                  <div class="dev-stats"><?= (int)$d['hilos'] ?> hilos · <?= (int)$d['mensajes'] ?> mensajes</div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  </main>

  <!-- DERECHA: tienda + nuevos posts -->
  <aside class="col-right">
    <div class="promo">
      <h3>Explora nuestra tienda</h3>
      <p>Gadgets de hardware hacking, módulos y accesorios. Hardware abierto y pago en cripto.</p>
      <a class="btn btn-accent btn-block" href="/tienda/">Ir a la tienda</a>
      <div class="promo-stats">
        <div><b><?= number_format($miembros, 0, ',', '.') ?></b><span>Miembros</span></div>
        <div><b><?= number_format($total_msgs, 0, ',', '.') ?></b><span>Mensajes</span></div>
      </div>
    </div>

    <div class="side-card">
      <div class="side-card__head">Nuevos posts</div>
      <ul class="newposts">
        <?php foreach ($recent as $rp): ?>
          <li>
            <?= avatar_html($rp['username'], 't-ava') ?>
            <div class="np-main">
              <a class="np-title" href="post.php?id=<?= (int)$rp['post_id'] ?>"><?= e(strip_tag_prefix($rp['title'])) ?></a>
              <div class="np-sub"><?= user_link($rp['username']) ?> · <?= e(hace($rp['created_at'])) ?></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </aside>

</div>
</div></div>
<?php forum_footer(); ?>
