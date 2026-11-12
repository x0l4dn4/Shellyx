<?php
/**
 * Shellyx — capa de presentación compartida (tienda + foro + landing).
 *
 * Solo maquetación/estética. No altera ninguna lógica de la aplicación ni las
 * vulnerabilidades didácticas del laboratorio (SQLi en el buscador, RFI en el
 * post). Rutas absolutas desde el DocumentRoot, así sirve igual en /tienda,
 * /foro y la raíz.
 */

if (!function_exists('e')) {
    /** Escape HTML corto. */
    function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** Iconos SVG inline (sin dependencias externas). */
function sx_icon(string $name, int $size = 24): string
{
    $paths = [
        'terminal' => '<path d="M4 17l6-5-6-5"/><path d="M12 19h8"/>',
        'chip'     => '<rect x="7" y="7" width="10" height="10" rx="1"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
        'firmware' => '<path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/><path d="M14 4l-4 16"/>',
        'community'=> '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 2.7-5 6-5s6 2 6 5"/><path d="M16 4.5A3 3 0 0 1 16 11"/><path d="M18.5 20c0-2.2-1-3.9-2.6-4.7"/>',
        'shield'   => '<path d="M12 3l8 3v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/>',
        'crypto'   => '<circle cx="12" cy="12" r="9"/><path d="M9 8h4.5a2.5 2.5 0 0 1 0 5H9zm0 5h5a2.5 2.5 0 0 1 0 5H9zM11 6v2M11 18v2"/>',
        'antenna'  => '<path d="M12 12v9"/><circle cx="12" cy="9" r="2"/><path d="M7.8 13.2a6 6 0 0 1 0-8.4M16.2 4.8a6 6 0 0 1 0 8.4M5 15a9 9 0 0 1 0-12M19 3a9 9 0 0 1 0 12"/>',
        'board'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M3 9h4M3 15h4M11 8h7M11 12h7M11 16h4"/>',
        'wifi'     => '<path d="M2 8.5a15 15 0 0 1 20 0M5 12a10 10 0 0 1 14 0M8.5 15.5a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/>',
        'usb'      => '<circle cx="12" cy="19" r="2"/><path d="M12 17V5"/><path d="M12 5l-2.5 3h5z" fill="currentColor" stroke="none"/><path d="M9 12H6v3M15 10h3v5"/>',
        'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11l2-9H6"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'github'   => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.9a3.4 3.4 0 0 0-1-2.6c3-.3 6-1.5 6-6.6a5 5 0 0 0-1.4-3.5 4.6 4.6 0 0 0-.1-3.5s-1.1-.3-3.6 1.3a12 12 0 0 0-6.4 0C6 1.6 4.9 1.9 4.9 1.9a4.6 4.6 0 0 0-.1 3.5A5 5 0 0 0 3.4 9c0 5 3 6.3 6 6.6a3.4 3.4 0 0 0-1 2.6V22"/>',
        'bolt'     => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
        'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'cpu'      => '<rect x="7" y="7" width="10" height="10" rx="1"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/><rect x="10" y="10" width="4" height="4"/>',
    ];
    $p = $paths[$name] ?? $paths['terminal'];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';
}

/**
 * Cabecera: doctype + <head> + nav. $active: inicio|tienda|foro.
 * $user: array con 'username' si hay sesión del foro (muestra estado).
 */
function sx_header(string $title, string $active = '', ?array $user = null, string $desc = ''): void
{
    $desc = $desc !== '' ? $desc : 'Shellyx — herramientas de hardware hacking abiertas y accesibles. Gadgets programables, firmware a medida y comunidad.';
    $nav = [
        'inicio' => ['/',        'Inicio'],
        'tienda' => ['/tienda/', 'Tienda'],
        'foro'   => ['/foro/',   'Foro'],
    ];
    ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#05080a">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/shellyx.css">
</head>
<body>
<div class="labnote"><div class="container"><?= sx_icon('shield', 15) ?> Entorno de laboratorio Shellyx · uso local y controlado · no exponer a redes no confiables</div></div>
<nav class="nav">
  <div class="nav__inner">
    <a class="brand" href="/" aria-label="Shellyx inicio">
      <img src="/assets/img/logo.webp" alt="Shellyx" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
      <span class="brand__fallback" style="display:none">$<b>hellyx</b></span>
    </a>
    <div class="nav__links">
      <?php foreach ($nav as $k => $it): ?>
        <a href="<?= $it[0] ?>"<?= $active === $k ? ' class="is-active"' : '' ?>><?= e($it[1]) ?></a>
      <?php endforeach; ?>
      <a href="https://github.com" target="_blank" rel="noopener"><?= sx_icon('github', 16) ?></a>
      <?php if ($user): ?>
        <span class="nav__user">// <b><?= e($user['username']) ?></b></span>
        <a href="/foro/logout.php">Salir</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php
}

/** Pie de página común. */
function sx_footer(): void
{
    ?>
<footer class="footer">
  <div class="container">
    <div class="footer__inner">
      <div class="footer__brand">
        <div class="brand" style="margin-bottom:12px">
          <img src="/assets/img/logo.webp" alt="Shellyx" style="height:30px" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
          <span class="brand__fallback" style="display:none">$<b>hellyx</b></span>
        </div>
        <p>Herramientas de hardware hacking abiertas y accesibles. Por y para hackers.</p>
        <div class="pay" style="margin-top:14px">
          <span>XMR</span><span>BTC</span><span>ETH</span>
        </div>
      </div>
      <div>
        <h4>Producto</h4>
        <a href="/tienda/">Tienda</a>
        <a href="/tienda/search.php">Buscar</a>
        <a href="/#modulos">Módulos &amp; scripts</a>
        <a href="/#gama">Gamas</a>
      </div>
      <div>
        <h4>Comunidad</h4>
        <a href="/foro/">Foro</a>
        <a href="https://github.com" target="_blank" rel="noopener">GitHub</a>
        <a href="/#comunidad">Retos mensuales</a>
      </div>
      <div>
        <h4>Empresa</h4>
        <a href="/#empresa">Sobre Shellyx</a>
        <a href="/#privacidad">Privacidad</a>
        <a href="/#empresa">Uso ético</a>
      </div>
    </div>
    <div class="footer__bottom">
      <span>© <?= date('Y') ?> Shellyx · Ciberseguridad &amp; Hardware</span>
      <span>Privacidad y anonimato garantizados · pago en cripto</span>
    </div>
  </div>
</footer>
</body>
</html>
<?php
}
