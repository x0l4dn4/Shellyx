<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$res = mysqli_query(db(), "SELECT id, name, price, description FROM products");

// Iconos rotatorios para dar variedad visual al catálogo (solo estética).
$icons = ['chip', 'antenna', 'wifi', 'usb', 'board', 'cpu', 'firmware', 'terminal'];
$i = 0;

sx_header('Tienda — Shellyx', 'tienda', null, 'Catálogo de gadgets de hardware hacking de Shellyx: dispositivos programables, accesorios y módulos.');
?>
<section class="section">
  <div class="container">
    <div class="shop-head">
      <div>
        <span class="eyebrow">Catálogo</span>
        <h1 style="margin:0">Gadgets &amp; accesorios</h1>
        <p class="muted" style="margin:.4em 0 0">Hardware abierto y programable. Gama de entrada hasta 100€ · pago único en cripto.</p>
      </div>
      <form class="searchbar" method="get" action="search.php" role="search">
        <input type="search" name="q" placeholder="Buscar en el catálogo..." aria-label="Buscar productos">
        <button class="btn btn--primary" type="submit"><?= sx_icon('search', 18) ?> Buscar</button>
      </form>
    </div>

    <?php if ($res && mysqli_num_rows($res) > 0): ?>
      <div class="products">
        <?php while ($row = mysqli_fetch_assoc($res)): $ic = $icons[$i++ % count($icons)]; ?>
          <article class="product">
            <div class="product__media">
              <span class="product__sku">SKU-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?></span>
              <?= sx_icon($ic, 58) ?>
            </div>
            <div class="product__body">
              <h3 class="product__name"><?= e($row['name']) ?></h3>
              <p class="product__desc"><?= e($row['description']) ?></p>
              <div class="product__foot">
                <span class="product__price"><?= e($row['price']) ?><small> €</small></span>
                <a class="btn btn--ghost btn--sm" href="search.php?q=<?= urlencode((string)$row['name']) ?>">Ver</a>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="table-wrap"><div class="empty">// El catálogo está vacío por ahora. Vuelve pronto.</div></div>
    <?php endif; ?>
  </div>
</section>
<?php sx_footer(); ?>
