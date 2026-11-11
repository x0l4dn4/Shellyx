<?php
require_once __DIR__ . '/../lib/db.php';

// VULNERABILIDAD DELIBERADA: la consulta se construye concatenando la
// entrada del usuario -> SQL injection (UNION) en el parámetro ?q=.
$q = $_GET['q'] ?? '';
$sql = "SELECT id, name, price, description FROM products WHERE name LIKE '%$q%'";
$res = mysqli_query(db(), $sql);
?><!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Buscar - Tienda CTF</title></head>
<body>
<h1>Buscar productos</h1>
<form method="get" action="search.php">
  <input type="text" name="q" value="<?= htmlspecialchars($q) ?>">
  <button type="submit">Buscar</button>
</form>
<table>
<tr><th>Nombre</th><th>Precio</th><th>Descripción</th></tr>
<?php while ($res && $row = mysqli_fetch_assoc($res)): ?>
<tr>
  <td><?= htmlspecialchars((string)$row['name']) ?></td>
  <td><?= htmlspecialchars((string)$row['price']) ?></td>
  <td><?= htmlspecialchars((string)$row['description']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
