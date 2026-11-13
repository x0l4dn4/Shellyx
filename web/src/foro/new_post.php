<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Crear un hilo.
require_login();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $body  = $_POST['body'] ?? '';
    $embed = $_POST['embed_url'] ?? '';
    // Inserta el hilo (category toma su DEFAULT 'general').
    $stmt = mysqli_prepare(
        db(),
        "INSERT INTO posts (user_id, title, body, embed_url) VALUES (?,?,?,?)"
    );
    mysqli_stmt_bind_param($stmt, "isss", $u['id'], $title, $body, $embed);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id(db());
    header('Location: post.php?id=' . $id);
    exit;
}

forum_head('Nueva publicación — Shellyx');
forum_bar($u);
forum_breadcrumb([['Inicio', 'index.php'], ['Nueva publicación', null]]);
?>
<section class="compose"><div class="wrap">
  <form class="compose-card" method="post" action="new_post.php">
    <h1>Nueva publicación</h1>
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
