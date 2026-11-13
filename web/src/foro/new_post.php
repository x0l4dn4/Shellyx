<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Crear un hilo.
require_login();
$u = current_user();

// Dispositivo (subforo) opcional: se puede pasar por GET al abrir el formulario
// o por POST al enviarlo. Se valida contra la tabla products.
function sx_valid_product(?string $raw): ?int
{
    if ($raw === null || $raw === '') return null;
    $pid = (int)$raw;
    if ($pid <= 0) return null;
    $stmt = mysqli_prepare(db(), "SELECT id FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return ($res && mysqli_fetch_assoc($res)) ? $pid : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = $_POST['title'] ?? '';
    $body    = $_POST['body'] ?? '';
    $embed   = $_POST['embed_url'] ?? '';
    $product = sx_valid_product($_POST['product_id'] ?? null);
    // Inserta el hilo (category toma su DEFAULT 'general').
    $stmt = mysqli_prepare(
        db(),
        "INSERT INTO posts (user_id, title, body, embed_url, product_id) VALUES (?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($stmt, "isssi", $u['id'], $title, $body, $embed, $product);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id(db());
    header('Location: post.php?id=' . $id);
    exit;
}

// Subforo de dispositivo de destino (si se abrió desde uno).
$product = sx_valid_product($_GET['product'] ?? null);
$deviceName = '';
if ($product !== null) {
    $stmt = mysqli_prepare(db(), "SELECT name FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $deviceName = ($res && $row = mysqli_fetch_assoc($res)) ? $row['name'] : '';
}

forum_head('Nueva publicación — Shellyx');
forum_bar($u);
$crumb = [['Inicio', 'index.php']];
if ($deviceName !== '') { $crumb[] = ['Dispositivos', 'index.php#dispositivos']; $crumb[] = [$deviceName, device_url($product)]; }
$crumb[] = ['Nueva publicación', null];
forum_breadcrumb($crumb);
?>
<section class="compose"><div class="wrap">
  <form class="compose-card" method="post" action="new_post.php">
    <h1>Nueva publicación</h1>
    <?php if ($deviceName !== ''): ?>
      <div class="compose-dev">Publicando en el subforo de <strong><?= e($deviceName) ?></strong></div>
      <input type="hidden" name="product_id" value="<?= (int)$product ?>">
    <?php endif; ?>
    <div class="field"><label for="t">Título</label><input id="t" name="title" type="text" placeholder="Título del hilo"></div>
    <div class="field"><label for="b">Contenido</label><textarea id="b" name="body" placeholder="Escribe tu mensaje…"></textarea></div>
    <div class="field">
      <label for="emb">URL de recurso a incrustar (opcional)</label>
      <input id="emb" name="embed_url" type="text" placeholder="https://…">
      <p class="hint">Se mostrará como enlace dentro de la publicación.</p>
    </div>
    <div class="compose-actions">
      <a class="btn btn-ghost" href="<?= $deviceName !== '' ? e(device_url($product)) : 'index.php' ?>">Cancelar</a>
      <button class="btn btn-accent" type="submit">Publicar</button>
    </div>
  </form>
</div></section>
<?php forum_footer(); ?>
