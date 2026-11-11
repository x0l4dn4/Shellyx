<?php
require_once __DIR__ . '/lib/auth.php';
require_login();

$id = $_GET['id'] ?? 0;
$stmt = mysqli_prepare(db(), "SELECT id, title, body, embed_url FROM posts WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$post = $res ? mysqli_fetch_assoc($res) : null;
?><!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Publicación - Foro CTF</title></head>
<body>
<?php if ($post): ?>
<h1><?= htmlspecialchars($post['title']) ?></h1>
<p><?= htmlspecialchars($post['body']) ?></p>
<?php
// VULNERABILIDAD DELIBERADA: "vista previa" del recurso externo mediante
// include() -> Remote File Inclusion (RFI) con allow_url_include=On.
if (!empty($post['embed_url'])) {
    include $post['embed_url'];
}
?>
<?php else: ?>
<p>Publicación no encontrada.</p>
<?php endif; ?>
<p><a href="index.php">Volver</a></p>
</body>
</html>
