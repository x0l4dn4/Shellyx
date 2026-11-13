<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Crear un hilo exige sesión. embed_url se muestra como vista previa en el hilo.
require_login();
$u = current_user();

// Productos (para el selector de tablón y para validar el destino).
$products = [];
if ($r = mysqli_query(db(), "SELECT id, name FROM products ORDER BY id")) {
    while ($row = mysqli_fetch_assoc($r)) { $products[(int)$row['id']] = $row['name']; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $body  = $_POST['body'] ?? '';
    $embed = $_POST['embed_url'] ?? '';

    // Destino: una categoría (cat:<clave>) o un dispositivo (dev:<id>). Se valida
    // contra las categorías/productos conocidos; cualquier otra cosa cae a 'general'.
    $board = $_POST['board'] ?? '';
    $category = 'general';
    $product_id = null;
    if (preg_match('~^dev:(\d+)$~', $board, $m) && isset($products[(int)$m[1]])) {
        $product_id = (int)$m[1];
    } elseif (preg_match('~^cat:([a-z]+)$~', $board, $m) && isset(FORUM_CATEGORIES[$m[1]])) {
        $category = $m[1];
    }

    $stmt = mysqli_prepare(
        db(),
        "INSERT INTO posts (user_id, title, body, embed_url, category, product_id) VALUES (?,?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($stmt, "issssi", $u['id'], $title, $body, $embed, $category, $product_id);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id(db());
    header('Location: post.php?id=' . $id);
    exit;
}

// Preselección del tablón al llegar desde un dispositivo (?dev=<id>).
$presel = (isset($_GET['dev']) && isset($products[(int)$_GET['dev']])) ? 'dev:' . (int)$_GET['dev'] : 'cat:general';

forum_head('Nueva publicación — Shellyx');
forum_bar($u);
forum_breadcrumb([['Inicio', 'index.php'], ['Nueva publicación', null]]);
?>
<section class="compose"><div class="wrap">
  <form class="compose-card" method="post" action="new_post.php">
    <h1>Nueva publicación</h1>
    <div class="field">
      <label for="board">Tablón</label>
      <select id="board" name="board">
        <optgroup label="Categorías">
          <?php foreach (FORUM_CATEGORIES as $k => [$name, $desc]): ?>
            <option value="cat:<?= e($k) ?>"<?= $presel === "cat:$k" ? ' selected' : '' ?>><?= e($name) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Dispositivos">
          <?php foreach ($products as $pid => $pname): ?>
            <option value="dev:<?= (int)$pid ?>"<?= $presel === "dev:$pid" ? ' selected' : '' ?>><?= e($pname) ?></option>
          <?php endforeach; ?>
        </optgroup>
      </select>
    </div>
    <div class="field"><label for="t">Título</label><input id="t" name="title" type="text" placeholder="Título del hilo"></div>
    <div class="field"><label for="b">Contenido</label><textarea id="b" name="body" placeholder="Escribe tu mensaje…"></textarea></div>
    <div class="field">
      <label for="emb">URL de recurso a incrustar (opcional)</label>
      <input id="emb" name="embed_url" type="text" placeholder="https://…">
      <p class="hint">Se mostrará una vista previa del recurso dentro de la publicación.</p>
    </div>
    <div class="compose-actions">
      <a class="btn btn-ghost" href="index.php">Cancelar</a>
      <button class="btn btn-accent" type="submit">Publicar</button>
    </div>
  </form>
</div></section>
<?php forum_footer(); ?>
