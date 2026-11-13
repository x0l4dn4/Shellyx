<?php
// Presentación compartida del foro (estilo XenForo/XDA). Solo marcado y helpers
// de vista; la autenticación vive en lib/auth.php. Toda salida de datos de BD
// pasa por e().

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

// Renderiza el título con un chip de color si empieza por [TAG]. Escapa el resto.
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

// Tarjeta de usuario del post bit (cadena pura, sin include dentro).
function usercard_html(string $author, int $msgCount): string
{
    [$roleLabel, $roleClass] = member_role($author);
    $since = member_since($author);
    $h  = '<aside class="usercard">';
    $h .= avatar_html($author, 'uc-ava');
    $h .= '<a class="uc-name" href="perfil.php?u=' . urlencode($author) . '">' . e($author) . '</a>';
    $h .= '<span class="role ' . $roleClass . '">' . e($roleLabel) . '</span>';
    $h .= '<dl class="uc-stats"><div><dt>Mensajes</dt><dd>' . number_format($msgCount, 0, ',', '.') . '</dd></div>';
    $h .= '<div><dt>Desde</dt><dd>' . $since . '</dd></div></dl>';
    $h .= '</aside>';
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
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">' . "\n";
    echo '<link rel="stylesheet" href="/foro/assets/forum.css">' . "\n";
    echo "</head>\n<body>\n";
}

function forum_bar(?array $user): void
{
    echo '<header class="fbar"><div class="wrap">';
    echo '<a class="brand" href="../../index.php">' . BRAND_CHIP . 'Shellyx</a>';
    echo '<nav class="mainnav"><a href="index.php" class="on">Foros</a><a href="novedades.php">Novedades</a><a href="miembros.php">Miembros</a></nav>';
    echo '<form class="search" method="get" action="buscar.php">' . SEARCH_ICON . '<input type="search" name="q" placeholder="Buscar en el foro…" aria-label="Buscar"></form>';
    echo '<div class="session">';
    if ($user) {
        echo '<a class="btn btn-accent" href="new_post.php">Nueva publicación</a>';
        echo '<a class="me" href="perfil.php?u=' . urlencode($user['username']) . '">' . avatar_html($user['username'], 'me-ava') . '<span class="me-name">' . e($user['username']) . '</span></a>';
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
