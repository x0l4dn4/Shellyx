<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
// El índice se ve sin sesión; publicar exige login (new_post.php).
$user = current_user();

// Contadores por categoría (hilos generales: product_id NULL).
$cat_threads = []; $cat_msgs = [];
if ($r = mysqli_query(db(), "SELECT category, COUNT(*) c FROM posts WHERE product_id IS NULL GROUP BY category"))
    while ($row = mysqli_fetch_assoc($r)) $cat_threads[$row['category']] = (int)$row['c'];
if ($r = mysqli_query(db(), "SELECT p.category, COUNT(*) c FROM replies rr JOIN posts p ON p.id=rr.post_id WHERE p.product_id IS NULL GROUP BY p.category"))
    while ($row = mysqli_fetch_assoc($r)) $cat_msgs[$row['category']] = (int)$row['c'];

// Productos y contadores por dispositivo.
$products = [];
if ($r = mysqli_query(db(), "SELECT id, name, price FROM products ORDER BY id"))
    while ($row = mysqli_fetch_assoc($r)) $products[(int)$row['id']] = $row;
$dev_threads = []; $dev_msgs = [];
if ($r = mysqli_query(db(), "SELECT product_id, COUNT(*) c FROM posts WHERE product_id IS NOT NULL GROUP BY product_id"))
    while ($row = mysqli_fetch_assoc($r)) $dev_threads[(int)$row['product_id']] = (int)$row['c'];
if ($r = mysqli_query(db(), "SELECT p.product_id, COUNT(*) c FROM replies rr JOIN posts p ON p.id=rr.post_id WHERE p.product_id IS NOT NULL GROUP BY p.product_id"))
    while ($row = mysqli_fetch_assoc($r)) $dev_msgs[(int)$row['product_id']] = (int)$row['c'];

// Actividad reciente.
$recent = [];
if ($r = mysqli_query(db(), "SELECT p.id, p.title, p.created_at, u.username FROM posts p JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 6"))
    while ($row = mysqli_fetch_assoc($r)) $recent[] = $row;

$total_hilos = array_sum($cat_threads) + array_sum($dev_threads);
$total_msgs  = $total_hilos + array_sum($cat_msgs) + array_sum($dev_msgs);

$groups = [
  'Gama de entrada'      => ['color' => 'var(--accent)', 'ids' => [1, 2]],
  'Gama avanzada'        => ['color' => '#a98bff',       'ids' => [3]],
  'Módulos y accesorios' => ['color' => 'var(--warn)',   'ids' => [4, 5, 6, 7]],
  'Packs'                => ['color' => 'var(--ok)',      'ids' => [8, 9]],
];
$cat_dot = ['firmware' => 'var(--accent)', 'payloads' => '#a98bff', 'soporte' => 'var(--warn)', 'general' => 'var(--muted)'];
$PROD_SLUG = [1 => 'shellyx-one', 2 => 'shellyx-mini', 3 => 'shellyx-pro', 4 => 'modulo-wifi-recon', 5 => 'modulo-subghz', 6 => 'antena-alta-ganancia', 7 => 'carcasa-translucida', 8 => 'kit-educativo', 9 => 'pack-firmwares'];

// Miniatura del dispositivo (placeholder; la foto real vive en la tienda).
function dev_thumb(int $id): string {
    $icons = [
        1 => '<rect x="6" y="3" width="12" height="18" rx="2"/><circle cx="12" cy="8" r="2.4"/><path d="M9 14h6M9 17h6"/>',
        2 => '<rect x="9" y="3" width="6" height="12" rx="1"/><path d="M12 15v6M9 8h6"/>',
        3 => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 4v2M15 4v2M9 18v2M15 18v2M4 9h2M4 15h2M18 9h2M18 15h2"/>',
        4 => '<path d="M5 12a10 10 0 0 1 14 0M8 15a6 6 0 0 1 8 0"/><circle cx="12" cy="18" r="1"/>',
        5 => '<path d="M12 11v9"/><circle cx="12" cy="8" r="2"/><path d="M7.8 12.2a6 6 0 0 1 0-8.4M16.2 3.8a6 6 0 0 1 0 8.4"/>',
        6 => '<path d="M12 11v9"/><circle cx="12" cy="8" r="2"/><path d="M7.8 12.2a6 6 0 0 1 0-8.4M16.2 3.8a6 6 0 0 1 0 8.4"/>',
        7 => '<rect x="6" y="3" width="12" height="18" rx="3"/><path d="M9 3v18"/>',
        8 => '<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        9 => '<rect x="7" y="7" width="10" height="10" rx="1"/><path d="M10 7V4M14 7V4M10 20v-3M14 20v-3M7 10H4M7 14H4M20 10h-3M20 14h-3"/>',
    ];
    $p = $icons[$id] ?? $icons[3];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

forum_head('Foros — Shellyx');
forum_bar($user);
?>
<main class="fh-wrap">
  <div class="fh-grid">

    <!-- Izquierda: Topics (categorías + dispositivos) -->
    <aside class="fh-panel fh-side">
      <div class="fh-ph">Topics</div>
      <ul class="fh-top">
        <?php foreach (FORUM_CATEGORIES as $key => [$name, $desc]): ?>
          <li><a href="categoria.php?c=<?= e($key) ?>"><span class="fh-dot" style="background:<?= $cat_dot[$key] ?? 'var(--muted)' ?>"></span><?= e($name) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="fh-subh">DISPOSITIVOS</div>
      <ul class="fh-top">
        <?php foreach ($products as $p): ?>
          <li><a href="dispositivo.php?id=<?= (int)$p['id'] ?>"><span class="fh-dot" style="background:var(--surface-3)"></span><?= e($p['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <!-- Centro: categorías + subforos de dispositivo -->
    <div>
      <div class="fh-bhead">
        <h1>Foros Shellyx</h1>
        <a class="btn btn-ghost" href="novedades.php">Nuevos posts</a>
      </div>

      <section class="fh-sec">
        <div class="fh-sech"><h2>Comunidad</h2></div>
        <div class="fh-cards">
          <?php foreach (FORUM_CATEGORIES as $key => [$name, $desc]):
              $t = $cat_threads[$key] ?? 0; $m = $t + ($cat_msgs[$key] ?? 0); ?>
          <article class="fh-card">
            <div class="fh-ico"><?= category_icon($key) ?></div>
            <div class="fh-b">
              <h3><a href="categoria.php?c=<?= e($key) ?>"><?= e($name) ?></a></h3>
              <p><?= e($desc) ?></p>
              <div class="fh-counts"><span><b><?= $t ?></b> hilos</span><span><b><?= $m ?></b> mensajes</span></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="fh-sec">
        <?php foreach ($groups as $gname => $g): ?>
          <div class="fh-sech"><h2>Dispositivos · <?= e($gname) ?></h2></div>
          <div class="fh-cards" style="margin-bottom:14px">
            <?php foreach ($g['ids'] as $pid): if (!isset($products[$pid])) continue; $p = $products[$pid];
                $t = $dev_threads[$pid] ?? 0; $m = $t + ($dev_msgs[$pid] ?? 0); ?>
            <article class="fh-card fh-dev" style="--g:<?= $g['color'] ?>">
              <a class="fh-thumb" href="dispositivo.php?id=<?= (int)$pid ?>"><?= dev_thumb($pid) ?><?php $slug = $PROD_SLUG[$pid] ?? ''; if ($slug !== ''): ?><img src="/assets/img/productos/<?= $slug ?>.jpg" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.remove()"><?php endif; ?></a>
              <div class="fh-b">
                <h3><a href="dispositivo.php?id=<?= (int)$pid ?>"><?= e($p['name']) ?></a><span class="fh-price"><?= e($p['price']) ?> €</span></h3>
                <div class="fh-counts"><span><b><?= $t ?></b> hilos</span><span><b><?= $m ?></b> mensajes</span></div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </section>
    </div>

    <!-- Derecha: Explora la tienda + Nuevos posts -->
    <aside>
      <div class="fh-explore">
        <h2>Explora nuestra tienda</h2>
        <p>Gadgets, módulos y firmware para montar tu propio arsenal de hardware hacking.</p>
        <a class="btn btn-accent" href="/tienda/">Ir a la tienda</a>
        <div class="fh-estats">
          <div><b><?= count($products) ?></b><span>Productos</span></div>
          <div><b><?= $total_hilos ?></b><span>Hilos</span></div>
          <div><b><?= $total_msgs ?></b><span>Mensajes</span></div>
        </div>
      </div>

      <div class="fh-panel">
        <div class="fh-ph">Nuevos posts</div>
        <?php foreach ($recent as $rp): ?>
          <div class="fh-np-item">
            <?= avatar_html($rp['username']) ?>
            <div class="t">
              <a href="post.php?id=<?= (int)$rp['id'] ?>"><?= e(strip_tag_prefix($rp['title'])) ?></a>
              <span>último: <a href="perfil.php?u=<?= urlencode($rp['username']) ?>"><?= e($rp['username']) ?></a> · <?= e(hace($rp['created_at'])) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>

  </div>
</main>
<?php forum_footer(); ?>
