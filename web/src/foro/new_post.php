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
?><!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Nueva publicación - Foro CTF</title></head>
<body>
<h1>Nueva publicación</h1>
<form method="post" action="new_post.php">
  <p><input type="text" name="title" placeholder="Título"></p>
  <p><textarea name="body" placeholder="Contenido"></textarea></p>
  <p><input type="text" name="embed_url" placeholder="URL de recurso a incrustar (opcional)"></p>
  <button type="submit">Publicar</button>
</form>
<p><a href="index.php">Volver</a></p>
</body>
</html>
