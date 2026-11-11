<?php
require_once __DIR__ . '/lib/auth.php';
require_login();
$u = current_user();
$res = mysqli_query(db(), "SELECT id, title FROM posts ORDER BY id DESC");
?><!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Foro CTF</title></head>
<body>
<h1>Foro</h1>
<p>Conectado como <?= htmlspecialchars($u['username']) ?> — <a href="logout.php">salir</a></p>
<p><a href="new_post.php">Nueva publicación</a></p>
<ul>
<?php while ($res && $row = mysqli_fetch_assoc($res)): ?>
  <li><a href="post.php?id=<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['title']) ?></a></li>
<?php endwhile; ?>
</ul>
</body>
</html>
