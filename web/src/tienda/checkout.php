<?php
require_once __DIR__ . '/../lib/cart.php';
require_once __DIR__ . '/../lib/shop_auth.php';
shop_require_login();
sx_cart_start();

$coins = sx_coins();

// Confirmar pago (SIMULADO): genera un pedido, vacía el carrito y guarda un
// "flash" en sesión para mostrar la confirmación tras el redirect.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $cart = sx_cart_items();
    $coinKey = strtolower($_POST['coin'] ?? 'xmr');
    if (!isset($coins[$coinKey])) $coinKey = 'xmr';
    if (!empty($cart['items'])) {
        $coin = $coins[$coinKey];
        $_SESSION['sx_last_order'] = [
            'id'     => 'SLX-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'total'  => $cart['total'],
            'count'  => $cart['count'],
            'coin'   => $coin['ticker'],
            'amount' => sx_crypto_amount($cart['total'], $coin),
        ];
        sx_cart_clear();
    }
    header('Location: checkout.php?ok=1');
    exit;
}

// Vista de confirmación (tras el pago simulado).
$order = null;
if (isset($_GET['ok']) && !empty($_SESSION['sx_last_order'])) {
    $order = $_SESSION['sx_last_order'];
    unset($_SESSION['sx_last_order']);
}

$cart = sx_cart_items();
$coinKey = strtolower($_GET['coin'] ?? 'xmr');
if (!isset($coins[$coinKey])) $coinKey = 'xmr';
$coin = $coins[$coinKey];

require_once __DIR__ . '/../lib/layout.php';
sx_header('Checkout — Shellyx', 'tienda', null, 'Pago anónimo en cripto en la tienda Shellyx.');
?>
<section class="section">
  <div class="container" style="max-width:860px">

    <?php if ($order): ?>
      <!-- ===== Pedido confirmado ===== -->
      <div class="panel panel--pad center">
        <div class="card__icon" style="margin:0 auto 16px"><?= sx_icon('shield') ?></div>
        <span class="eyebrow" style="margin:0">Pago recibido</span>
        <h1 style="font-size:1.9rem;margin:.3em 0 .3em">Pedido confirmado</h1>
        <p class="muted">Gracias por tu compra. Tu pedido se ha registrado correctamente.</p>
        <div class="order-box mono">
          <div class="sum-row"><span>Pedido</span><span class="accent"><?= e($order['id']) ?></span></div>
          <div class="sum-row"><span>Artículos</span><span><?= (int)$order['count'] ?></span></div>
          <div class="sum-row"><span>Total</span><span><?= sx_eur($order['total']) ?></span></div>
          <div class="sum-row sum-row--total"><span>Pagado en</span><span class="accent"><?= e($order['amount']) ?> <?= e($order['coin']) ?></span></div>
        </div>
        <p class="muted mono" style="font-size:.76rem;margin:16px 0 0">Transacción simulada · no se ha movido dinero real · no guardamos datos personales</p>
        <div class="hero__cta" style="justify-content:center;margin-top:22px">
          <a class="btn btn--primary" href="/tienda/"><?= sx_icon('cart', 18) ?> Seguir comprando</a>
          <a class="btn btn--ghost" href="/">Volver al inicio</a>
        </div>
      </div>

    <?php elseif (empty($cart['items'])): ?>
      <!-- ===== Carrito vacío ===== -->
      <div class="panel panel--pad center">
        <div class="card__icon" style="margin:0 auto 14px"><?= sx_icon('cart') ?></div>
        <h1 style="font-size:1.5rem;margin:0 0 .3em">No hay nada que pagar</h1>
        <p class="muted">Tu carrito está vacío.</p>
        <p style="margin-top:18px"><a class="btn btn--primary" href="/tienda/">Ir al catálogo</a></p>
      </div>

    <?php else: ?>
      <!-- ===== Checkout ===== -->
      <div class="shop-head" style="margin-bottom:22px">
        <div>
          <span class="eyebrow">Checkout</span>
          <h1 style="margin:0">Pago anónimo en cripto</h1>
          <p class="muted" style="margin:.4em 0 0">Sin correo, sin datos bancarios. Solo paga y listo.</p>
        </div>
        <a class="btn btn--muted btn--sm" href="carrito.php">&larr; Volver al carrito</a>
      </div>

      <div class="cart-grid">
        <div class="panel">
          <h3 style="margin:0 0 14px">Elige moneda</h3>
          <div class="coin-tabs">
            <?php foreach ($coins as $k => $c): ?>
              <a class="coin-tab<?= $k === $coinKey ? ' is-active' : '' ?>" href="checkout.php?coin=<?= $k ?>">
                <?= sx_icon('crypto', 18) ?> <b><?= e($c['ticker']) ?></b> <span class="muted"><?= e($c['name']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="pay-box">
            <div class="pay-box__amount">
              <span class="muted mono" style="font-size:.8rem">Importe a enviar</span>
              <div class="mono accent" style="font-size:1.5rem"><?= e(sx_crypto_amount($cart['total'], $coin)) ?> <?= e($coin['ticker']) ?></div>
              <span class="muted mono" style="font-size:.78rem">≈ <?= sx_eur($cart['total']) ?> · tasa de ejemplo</span>
            </div>
            <div class="pay-box__addr">
              <span class="muted mono" style="font-size:.76rem;letter-spacing:.08em">DIRECCIÓN <?= e($coin['ticker']) ?> (DE EJEMPLO)</span>
              <code class="addr"><?= e($coin['addr']) ?></code>
            </div>
          </div>

          <form method="post" action="checkout.php" style="margin-top:16px">
            <input type="hidden" name="action" value="pay">
            <input type="hidden" name="coin" value="<?= e($coinKey) ?>">
            <button class="btn btn--primary btn--block" type="submit"><?= sx_icon('lock', 18) ?> He realizado el pago</button>
          </form>
          <p class="muted mono center" style="font-size:.74rem;margin:12px 0 0">Pago simulado para la demo · no se procesa ninguna transacción real</p>
        </div>

        <aside class="cart-summary panel">
          <h3 style="margin:0 0 14px">Tu pedido</h3>
          <?php foreach ($cart['items'] as $it): ?>
            <div class="sum-row">
              <span><?= e($it['name']) ?> <span class="muted">× <?= (int)$it['qty'] ?></span></span>
              <span class="mono"><?= sx_eur($it['subtotal']) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="sum-row sum-row--total"><span>Total</span><span class="mono accent"><?= sx_eur($cart['total']) ?></span></div>
        </aside>
      </div>
    <?php endif; ?>

  </div>
</section>
<?php sx_footer(); ?>
