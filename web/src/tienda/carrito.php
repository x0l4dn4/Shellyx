<?php
require_once __DIR__ . '/../lib/cart.php';
sx_cart_start();

// Acciones del carrito (POST -> redirect -> GET, para no reenviar el formulario).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    switch ($action) {
        case 'add':    sx_cart_add($id, 1); break;
        case 'update': sx_cart_set($id, (int)($_POST['qty'] ?? 1)); break;
        case 'remove': sx_cart_remove($id); break;
        case 'clear':  sx_cart_clear(); break;
    }
    header('Location: carrito.php');
    exit;
}

$cart = sx_cart_items();

require_once __DIR__ . '/../lib/layout.php';
sx_header('Carrito — Shellyx', 'tienda', null, 'Tu carrito en la tienda Shellyx.');
?>
<section class="section">
  <div class="container">
    <div class="shop-head">
      <div>
        <span class="eyebrow">Tu carrito</span>
        <h1 style="margin:0">Carrito de compra</h1>
        <p class="muted" style="margin:.4em 0 0"><?= (int)$cart['count'] ?> artículo(s) · pago único en cripto</p>
      </div>
      <a class="btn btn--muted btn--sm" href="/tienda/">&larr; Seguir comprando</a>
    </div>

    <?php if (empty($cart['items'])): ?>
      <div class="panel panel--pad center">
        <div class="card__icon" style="margin:0 auto 14px"><?= sx_icon('cart') ?></div>
        <h2 style="font-size:1.4rem;margin:0 0 .3em">Tu carrito está vacío</h2>
        <p class="muted">Añade gadgets desde el catálogo para empezar.</p>
        <p style="margin-top:18px"><a class="btn btn--primary" href="/tienda/"><?= sx_icon('cart', 18) ?> Ir al catálogo</a></p>
      </div>
    <?php else: ?>
      <div class="cart-grid">
        <div class="cart-lines">
          <?php foreach ($cart['items'] as $it): ?>
            <div class="cart-line">
              <div class="cart-line__media"><?= sx_icon('chip', 30) ?></div>
              <div class="cart-line__info">
                <h3 class="cart-line__name"><?= e($it['name']) ?></h3>
                <p class="cart-line__desc"><?= e($it['description']) ?></p>
                <span class="cart-line__unit mono"><?= sx_eur($it['price']) ?> / ud.</span>
              </div>
              <form method="post" action="carrito.php" class="cart-line__qty">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                <input type="number" name="qty" min="0" max="99" value="<?= (int)$it['qty'] ?>" aria-label="Cantidad">
                <button class="btn btn--muted btn--sm" type="submit">Actualizar</button>
              </form>
              <div class="cart-line__sub mono"><?= sx_eur($it['subtotal']) ?></div>
              <form method="post" action="carrito.php" class="cart-line__rm">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                <button class="iconbtn" type="submit" aria-label="Quitar" title="Quitar">✕</button>
              </form>
            </div>
          <?php endforeach; ?>
          <div class="cart-lines__foot">
            <form method="post" action="carrito.php">
              <input type="hidden" name="action" value="clear">
              <button class="btn btn--sm" type="submit" style="color:var(--muted)">Vaciar carrito</button>
            </form>
          </div>
        </div>

        <aside class="cart-summary panel">
          <h3 style="margin:0 0 14px">Resumen</h3>
          <div class="sum-row"><span>Artículos</span><span class="mono"><?= (int)$cart['count'] ?></span></div>
          <div class="sum-row"><span>Subtotal</span><span class="mono"><?= sx_eur($cart['total']) ?></span></div>
          <div class="sum-row sum-row--muted"><span>Envío</span><span class="mono">Gratis</span></div>
          <div class="sum-row sum-row--total"><span>Total</span><span class="mono accent"><?= sx_eur($cart['total']) ?></span></div>
          <a class="btn btn--primary btn--block" href="checkout.php" style="margin-top:16px"><?= sx_icon('crypto', 18) ?> Finalizar compra</a>
          <p class="muted mono center" style="font-size:.74rem;margin:12px 0 0">Pago anónimo en XMR · BTC · ETH</p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php sx_footer(); ?>
