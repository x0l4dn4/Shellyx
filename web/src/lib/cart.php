<?php
/**
 * Carrito de la tienda Shellyx (solo presentación/e-commerce de escaparate).
 *
 * El carrito vive en la SESIÓN PHP; no toca la base de datos. Los ids se castean
 * a int y los datos de producto se leen con sentencias preparadas. El pago es
 * simulado.
 */
require_once __DIR__ . '/db.php';

function sx_cart_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['sx_cart']) || !is_array($_SESSION['sx_cart'])) {
        $_SESSION['sx_cart'] = []; // [product_id => qty]
    }
}

/** @return array<int,int> mapa id=>cantidad */
function sx_cart(): array
{
    return $_SESSION['sx_cart'] ?? [];
}

function sx_cart_count(): int
{
    return array_sum(sx_cart());
}

function sx_cart_add(int $id, int $qty = 1): void
{
    if ($id <= 0 || $qty <= 0) return;
    sx_cart_start();
    $_SESSION['sx_cart'][$id] = ($_SESSION['sx_cart'][$id] ?? 0) + $qty;
    if ($_SESSION['sx_cart'][$id] > 99) $_SESSION['sx_cart'][$id] = 99;
}

function sx_cart_set(int $id, int $qty): void
{
    sx_cart_start();
    if ($qty <= 0) {
        unset($_SESSION['sx_cart'][$id]);
    } else {
        $_SESSION['sx_cart'][$id] = min($qty, 99);
    }
}

function sx_cart_remove(int $id): void
{
    sx_cart_start();
    unset($_SESSION['sx_cart'][$id]);
}

function sx_cart_clear(): void
{
    sx_cart_start();
    $_SESSION['sx_cart'] = [];
}

/**
 * Devuelve las líneas del carrito con datos de producto desde la BD.
 * @return array{items:array<int,array>, total:float, count:int}
 */
function sx_cart_items(): array
{
    sx_cart_start();
    $cart = sx_cart();
    $items = [];
    $total = 0.0;
    if (!empty($cart)) {
        $ids = array_map('intval', array_keys($cart));
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = mysqli_prepare(db(), "SELECT id, name, price, description FROM products WHERE id IN ($in)");
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && $row = mysqli_fetch_assoc($res)) {
            $id   = (int)$row['id'];
            $qty  = (int)($cart[$id] ?? 0);
            if ($qty <= 0) continue;
            $price = (float)$row['price'];
            $sub   = $price * $qty;
            $total += $sub;
            $items[] = [
                'id' => $id, 'name' => $row['name'], 'description' => $row['description'],
                'price' => $price, 'qty' => $qty, 'subtotal' => $sub,
            ];
        }
    }
    return ['items' => $items, 'total' => $total, 'count' => sx_cart_count()];
}

/** Formatea importe en euros estilo español: 1.234,56 €. */
function sx_eur(float $v): string
{
    return number_format($v, 2, ',', '.') . ' €';
}

/** Monedas aceptadas (pago simulado) con tasa EUR/unidad de ejemplo y dirección de muestra. */
function sx_coins(): array
{
    return [
        'xmr' => ['name' => 'Monero',   'ticker' => 'XMR', 'rate' => 150.0,   'dec' => 6,
                  'addr' => '4ShellyxXMRdemo9x7k2m4q8r0t2v4w6y8a1c3e5g7j9l1n3p5s7u9w1z3B5D7F9H1'],
        'btc' => ['name' => 'Bitcoin',  'ticker' => 'BTC', 'rate' => 60000.0, 'dec' => 8,
                  'addr' => 'bc1qshellyxbtcdemo0qk7p3m9x2v5t8r1w4y6a3c0e9g2j'],
        'eth' => ['name' => 'Ethereum', 'ticker' => 'ETH', 'rate' => 3000.0,  'dec' => 6,
                  'addr' => '0xShellyxETHdemo5A3c7E9g1J3l5N7p9R1t3V5x7Z9b1D3f5'],
    ];
}

/** Importe en cripto (simulado) para un total en EUR. */
function sx_crypto_amount(float $eur, array $coin): string
{
    if ($coin['rate'] <= 0) return '0';
    return number_format($eur / $coin['rate'], $coin['dec'], '.', '');
}
