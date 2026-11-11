<?php
require_once __DIR__ . '/../lib/db.php';
$res = mysqli_query(db(), "SELECT id, name, price, description FROM products");
?><!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Tienda - CTF</title></head>
<body>
<h1>Tienda</h1>
<p><a href="search.php">Buscar productos</a></p>
<table>
<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Descripción</th></tr>
<?php while ($res && $row = mysqli_fetch_assoc($res)): ?>
<tr>
  <td><?= htmlspecialchars($row['id']) ?></td>
  <td><?= htmlspecialchars($row['name']) ?></td>
  <td><?= htmlspecialchars($row['price']) ?></td>
  <td><?= htmlspecialchars($row['description']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
