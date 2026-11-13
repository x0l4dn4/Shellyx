<?php
// Presentación compartida del foro (estilo XenForo/XDA): marcado y helpers de
// vista. La autenticación vive en lib/auth.php.

// Categorías (nodos). La clave es el valor de posts.category.
const FORUM_CATEGORIES = [
    'firmware' => ['Firmware y Desarrollo', 'Releases de firmware, builds personalizadas y changelogs.'],
    'payloads' => ['Payloads y Módulos',    'Scripts, payloads y módulos para los cacharros.'],
    'soporte'  => ['Soporte y Bricks',      'Se te quedó tieso el cacharro, pide ayuda aquí.'],
    'general'  => ['General y Presentaciones','Unboxings, batallitas y presentaciones de la comunidad.'],
];

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Agrupación de los dispositivos de la tienda en el índice del foro (subforos).
// La clave es el título del grupo; el valor, los product_id que contiene.
const DEVICE_GROUPS = [
    'Dispositivos Shellyx' => [1, 2, 3, 8],
    'Módulos y accesorios' => [4, 5, 6, 7, 9],
];

// Glifo SVG para la "miniatura" de cada dispositivo (varía por product_id).
function device_icon_svg(int $id): string
{
    $paths = [
        1 => '<rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M10 7V4M14 7V4M10 20v-3M14 20v-3M7 10H4M7 14H4M20 10h-3M20 14h-3"/>',
        2 => '<circle cx="12" cy="19" r="2"/><path d="M12 17V5"/><path d="M9 12H6v3M15 10h3v5"/>',
        3 => '<rect x="7" y="7" width="10" height="10" rx="1"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/><rect x="10.5" y="10.5" width="3" height="3"/>',
        4 => '<path d="M2 8.5a15 15 0 0 1 20 0M5 12a10 10 0 0 1 14 0M8.5 15.5a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/>',
        5 => '<path d="M12 12v9"/><circle cx="12" cy="9" r="2"/><path d="M7.8 13.2a6 6 0 0 1 0-8.4M16.2 4.8a6 6 0 0 1 0 8.4"/>',
        6 => '<path d="M12 21V9M8 9l4-6 4 6"/><path d="M6 21h12"/>',
        7 => '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M9.5 6.5h5"/>',
        8 => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M3 9h4M3 15h4M11 8h7M11 12h7"/>',
        9 => '<path d="M4 7h16M4 12h16M4 17h10"/>',
    ];
    $p = $paths[$id] ?? $paths[1];
    return '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

// URL del subforo de un dispositivo.
function device_url(int $id): string
{
    return 'dispositivo.php?id=' . $id;
}

function avatar_initials(string $username): string
{
    $letters = preg_replace('/[^\p{L}\p{N}]/u', '', $username);
    $base = ($letters !== '') ? $letters : $username;
    $base = ($base !== '') ? $base : '?';
    return mb_strtoupper(mb_substr($base, 0, 2, 'UTF-8'), 'UTF-8');
}

function avatar_color(string $username): string
{
    $hue = crc32($username) % 360;
    return "hsl({$hue} 45% 40%)";
}

function avatar_html(string $username, string $sizeClass = ''): string
{
    $cls = trim('ava ' . $sizeClass);
    $style = 'background:' . avatar_color($username);
    return '<span class="' . e($cls) . '" style="' . e($style) . '" aria-hidden="true">'
        . e(avatar_initials($username)) . '</span>';
}

function member_role(string $username): array
{
    if ($username === 'admin')     return ['Administrador', 'role-admin'];
    if ($username === 'moderador') return ['Moderador', 'role-mod'];
    return ['Miembro', 'role-member'];
}

function member_since(string $username): int
{
    return 2021 + (crc32('since:' . $username) % 4); // 2021..2024, estable por usuario
}

// Estadísticas públicas de un usuario (posts, respuestas, mensajes). Cacheadas
// en una sola consulta por petición.
function user_stats(string $username): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $q = mysqli_query(db(), "SELECT u.username,
                (SELECT COUNT(*) FROM posts p   WHERE p.user_id = u.id) AS posts,
                (SELECT COUNT(*) FROM replies r WHERE r.user_id = u.id) AS replies
                FROM users u");
        while ($q && $row = mysqli_fetch_assoc($q)) {
            $p = (int)$row['posts']; $r = (int)$row['replies'];
            $cache[$row['username']] = ['posts' => $p, 'replies' => $r, 'msgs' => $p + $r];
        }
    }
    return $cache[$username] ?? ['posts' => 0, 'replies' => 0, 'msgs' => 0];
}

// Reputación pública derivada de la actividad.
function user_reputation(string $username): int
{
    $s = user_stats($username);
    $rep = $s['posts'] * 10 + $s['replies'] * 3;
    if ($username === 'admin')         $rep += 400;
    elseif ($username === 'moderador') $rep += 250;
    return $rep;
}

// Rango dentro del foro: [etiqueta, clase] según rol y reputación.
function user_rank(string $username, ?int $rep = null): array
{
    if ($username === 'admin')     return ['Administrador', 'rk-admin'];
    if ($username === 'moderador') return ['Moderador', 'rk-mod'];
    $rep = $rep ?? user_reputation($username);
    if ($rep >= 250) return ['Leyenda',  'rk-legend'];
    if ($rep >= 120) return ['Experto',  'rk-expert'];
    if ($rep >= 60)  return ['Veterano', 'rk-veteran'];
    if ($rep >= 20)  return ['Miembro',  'rk-member'];
    return ['Novato', 'rk-rookie'];
}

// Fecha relativa en español. Envejece con naturalidad a lo largo de la vida del lab.
function hace(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) return '';
    $d = time() - $ts;
    if ($d < 0) $d = 0;
    if ($d < 60)       return 'hace un momento';
    if ($d < 3600)     { $m = intdiv($d, 60);    return "hace {$m} min"; }
    if ($d < 86400)    { $h = intdiv($d, 3600);  return "hace {$h} h"; }
    if ($d < 2592000)  { $x = intdiv($d, 86400);    return $x === 1 ? 'hace 1 día'  : "hace {$x} días"; }
    if ($d < 31536000) { $x = intdiv($d, 2592000);  return $x === 1 ? 'hace 1 mes'  : "hace {$x} meses"; }
    $x = intdiv($d, 31536000);                       return $x === 1 ? 'hace 1 año'  : "hace {$x} años";
}

// Quita un prefijo [TAG] del título (para <title> y breadcrumb).
function strip_tag_prefix(string $title): string
{
    return trim(preg_replace('/^\s*\[[^\]]{1,16}\]\s*/u', '', $title));
}

// Renderiza el título con un chip de color si empieza por [TAG].
function render_title_with_tag(string $title): string
{
    if (preg_match('/^\s*\[([^\]]{1,16})\]\s*(.*)$/u', $title, $m)) {
        $tag = trim($m[1]);
        $rest = $m[2];
        $u = mb_strtoupper($tag, 'UTF-8');
        $cls = 'tag';
        if (in_array($u, ['FIRMWARE', 'FW', 'RELEASE'], true))      $cls .= ' tag-fw';
        elseif (in_array($u, ['BRICK', 'SOS', 'AYUDA'], true))      $cls .= ' tag-brick';
        elseif (in_array($u, ['RESUELTO', 'SOLVED'], true))         $cls .= ' tag-ok';
        $label = mb_strtoupper(mb_substr($tag, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($tag, 1, null, 'UTF-8'), 'UTF-8');
        return '<span class="' . $cls . '">' . e($label) . '</span>' . e($rest);
    }
    return e($title);
}

function category_icon(string $key): string
{
    $icons = [
        'firmware' => '<path d="M4 7h16M4 12h16M4 17h10"/>',
        'payloads' => '<path d="m8 6-5 6 5 6M16 6l5 6-5 6"/>',
        'soporte'  => '<path d="M12 2a9 9 0 0 0-9 9v4a3 3 0 0 0 3 3h1v-7H5v-.5a7 7 0 0 1 14 0V15h-2v7h1a3 3 0 0 0 3-3v-4a9 9 0 0 0-9-9Z"/>',
        'general'  => '<path d="M7 8h10M7 12h6"/><path d="M4 4h16v12H8l-4 4V4Z"/>',
    ];
    $p = $icons[$key] ?? $icons['general'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

// Extracto de texto plano en una línea.
function excerpt(string $text, int $len = 180): string
{
    $t = trim(preg_replace('/\s+/u', ' ', $text));
    return mb_strlen($t, 'UTF-8') > $len ? rtrim(mb_substr($t, 0, $len, 'UTF-8')) . '…' : $t;
}

// URL del perfil público de un usuario.
function profile_url(string $username): string
{
    return 'perfil.php?u=' . e(rawurlencode($username));
}

// Enlace con el nombre de usuario hacia su perfil. El color/brillo del nombre
// depende del rango (rk-*), por reputación.
function user_link(string $username, string $class = ''): string
{
    [, $rankClass] = user_rank($username);
    $cls = trim('uname ' . $rankClass . ' ' . $class);
    return '<a class="' . e($cls) . '" href="' . profile_url($username) . '">' . e($username) . '</a>';
}

// Tarjeta de usuario del post bit (cadena pura, sin include dentro).
function usercard_html(string $author): string
{
    $rep = user_reputation($author);
    [$rankLabel, $rankClass] = user_rank($author, $rep);
    $s = user_stats($author);
    $since = member_since($author);
    $h  = '<aside class="usercard">';
    $h .= avatar_html($author, 'uc-ava');
    $h .= '<a class="uc-name ' . $rankClass . '" href="' . profile_url($author) . '">' . e($author) . '</a>';
    $h .= '<span class="rank ' . $rankClass . '">' . e($rankLabel) . '</span>';
    $h .= '<dl class="uc-stats">';
    $h .= '<div><dt>Mensajes</dt><dd>' . number_format($s['msgs'], 0, ',', '.') . '</dd></div>';
    $h .= '<div><dt>Reputación</dt><dd>' . number_format($rep, 0, ',', '.') . '</dd></div>';
    $h .= '<div><dt>Desde</dt><dd>' . $since . '</dd></div>';
    $h .= '</dl></aside>';
    return $h;
}

const BRAND_CHIP = '<span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M10 7V4M14 7V4M10 20v-3M14 20v-3M7 10H4M7 14H4M20 10h-3M20 14h-3"/></svg></span>';
const SEARCH_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>';

function forum_head(string $title): void
{
    echo "<!DOCTYPE html>\n<html lang=\"es\">\n<head>\n";
    echo '<meta charset="utf-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    echo '<title>' . e($title) . '</title>' . "\n";
    echo '<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">' . "\n";
    echo '<meta name="theme-color" content="#14171b">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">' . "\n";
    echo '<link rel="stylesheet" href="/foro/assets/forum.css">' . "\n";
    echo "</head>\n<body>\n";
    // Luz tenue verde que sigue al puntero (capa de fondo, detrás del contenido).
    echo '<div id="cursorglow" aria-hidden="true"></div>' . "\n";
    echo "<script>(function(){if(window.matchMedia&&matchMedia('(pointer:coarse)').matches)return;"
       . "var r=document.documentElement,raf=0;addEventListener('pointermove',function(e){"
       . "if(raf)return;raf=requestAnimationFrame(function(){"
       . "r.style.setProperty('--cx',e.clientX+'px');r.style.setProperty('--cy',e.clientY+'px');raf=0;});"
       . "},{passive:true});})();</script>\n";
}

// $active: foros|novedades|miembros|'' ; $q: término actual del buscador.
function forum_bar(?array $user, string $active = 'foros', string $q = ''): void
{
    $nav = [
        'foros'     => ['index.php',     'Foros'],
        'novedades' => ['novedades.php', 'Novedades'],
        'miembros'  => ['miembros.php',  'Miembros'],
    ];
    echo '<header class="fbar"><div class="wrap">';
    echo '<a class="brand" href="../../index.php" aria-label="Shellyx inicio">'
       . '<img class="brand-logo" src="/assets/img/logo.webp" alt="Shellyx" '
       . 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\'">'
       . '<span class="brand-fallback" style="display:none">' . BRAND_CHIP . 'Shellyx</span></a>';
    echo '<nav class="mainnav">';
    foreach ($nav as $k => [$href, $label]) {
        echo '<a href="' . $href . '"' . ($active === $k ? ' class="on"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
    echo '<form class="search" method="get" action="buscar.php" role="search">' . SEARCH_ICON
        . '<input type="search" name="q" value="' . e($q) . '" placeholder="Buscar en el foro…" aria-label="Buscar en el foro"></form>';
    echo '<div class="session">';
    if ($user) {
        echo '<a class="btn btn-accent" href="new_post.php">Nueva publicación</a>';
        echo '<a class="me" href="perfil.php" title="Mi perfil">' . avatar_html($user['username'], 'me-ava') . '<span class="me-name">' . e($user['username']) . '</span></a>';
        echo '<a class="btn btn-ghost" href="logout.php">Salir</a>';
    } else {
        echo '<a class="btn btn-ghost" href="register.php">Registrarse</a>';
        echo '<a class="btn btn-accent" href="login.php">Iniciar sesión</a>';
    }
    echo '</div></div></header>';
}

// $items: lista de [label, href|null]. El último se muestra como posición actual.
function forum_breadcrumb(array $items): void
{
    echo '<div class="crumbs"><div class="wrap">';
    $last = count($items) - 1;
    foreach ($items as $i => [$label, $href]) {
        if ($i > 0) echo '<span class="sep">›</span>';
        if ($href !== null && $i !== $last) {
            echo '<a href="' . e($href) . '">' . e($label) . '</a>';
        } else {
            echo '<span class="here">' . e($label) . '</span>';
        }
    }
    echo '</div></div>';
}

function forum_footer(): void
{
    echo '<footer class="foot"><div class="wrap">';
    echo '<a class="foot-brand" href="index.php">Shellyx</a><span class="spacer"></span>';
    echo '</div></footer>';
    echo "\n</body>\n</html>";
}
