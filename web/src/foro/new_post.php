<?php
require_once __DIR__ . '/lib/auth.php';
require_login();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $body  = $_POST['body'] ?? '';
    $embed = $_POST['embed_url'] ?? '';
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

require_once __DIR__ . '/../lib/layout.php';
sx_header('Nueva publicación — Foro Shellyx', 'foro', $u);
?>
<section class="section">
  <div class="container narrow" style="max-width:720px">
    <div class="forum-head" style="margin-bottom:18px">
      <div>
        <span class="eyebrow">Foro</span>
        <h1 style="margin:0">Nueva publicación</h1>
      </div>
      <a class="btn btn--muted btn--sm" href="index.php">&larr; Volver</a>
    </div>

    <div class="panel panel--pad">
      <form method="post" action="new_post.php">
        <div class="field">
          <label for="title">Título</label>
          <input type="text" id="title" name="title" placeholder="Título de la publicación">
        </div>
        <div class="field">
          <label for="body">Contenido</label>
          <textarea id="body" name="body" placeholder="Comparte tu módulo, script o duda con la comunidad..."></textarea>
        </div>
        <div class="field">
          <label for="embed_url">URL de recurso a incrustar <span class="muted">(opcional)</span></label>
          <input type="text" id="embed_url" name="embed_url" placeholder="https://ejemplo.com/recurso.php">
          <small class="muted mono" style="font-size:.76rem">Se mostrará una vista previa del recurso dentro de la publicación.</small>
        </div>
        <button class="btn btn--primary" type="submit"><?= sx_icon('bolt', 18) ?> Publicar</button>
      </form>
    </div>
  </div>
</section>
<?php sx_footer(); ?>
