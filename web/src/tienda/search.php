<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';

// VULNERABILIDAD DELIBERADA: la consulta se construye concatenando la
// entrada del usuario -> SQL injection (UNION) en el parámetro ?q=.
$q = $_GET['q'] ?? '';
$sql = "SELECT id, name, price, description FROM products WHERE name LIKE '%$q%'";
$res = mysqli_query(db(), $sql);

sx_header('Buscar — Tienda Shellyx', 'tienda', null, 'Buscador del catálogo de Shellyx.');
?>
<section class="section">
  <div class="container">
    <div class="shop-head">
      <div>
        <span class="eyebrow">Buscador</span>
        <h1 style="margin:0">Buscar productos</h1>
        <p class="muted" style="margin:.4em 0 0">Filtra el catálogo por nombre.</p>
      </div>
      <form class="searchbar" method="get" action="search.php" role="search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar en el catálogo..." aria-label="Buscar productos" autofocus>
        <button class="btn btn--primary" type="submit"><?= sx_icon('search', 18) ?> Buscar</button>
      </form>
    </div>

    <?php if ($q !== ''): ?>
      <p class="muted mono" style="font-size:.85rem">&gt; resultados para "<span class="accent"><?= e($q) ?></span>"</p>
    <?php endif; ?>

    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Nombre</th><th>Precio</th><th>Descripción</th></tr>
        </thead>
        <tbody>
          <?php $n = 0; while ($res && $row = mysqli_fetch_assoc($res)): $n++; ?>
            <tr>
              <td><?= e((string)$row['name']) ?></td>
              <td><?= e((string)$row['price']) ?></td>
              <td><?= e((string)$row['description']) ?></td>
            </tr>
          <?php endwhile; ?>
          <?php if ($n === 0): ?>
            <tr><td colspan="3"><div class="empty">// Sin resultados. Prueba con otro término.</div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p style="margin-top:22px"><a class="btn btn--muted btn--sm" href="/tienda/">&larr; Volver al catálogo</a></p>
  </div>
</section>
<?php sx_footer(); ?>
