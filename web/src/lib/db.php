<?php
// Conexión mysqli compartida por la tienda y el foro.

function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = getenv('DB_HOST') ?: 'db';
    $user = getenv('DB_USER') ?: 'appuser';
    $pass = getenv('DB_PASS') ?: 'apppass';
    $name = getenv('DB_NAME') ?: 'appdb';

    // Devolver false en vez de lanzar excepción; gestionamos el reintento.
    mysqli_report(MYSQLI_REPORT_OFF);

    for ($i = 0; $i < 10; $i++) {
        $c = @mysqli_connect($host, $user, $pass, $name);
        if ($c instanceof mysqli) {
            $conn = $c;
            // UTF-8 extremo a extremo (acentos del foro).
            mysqli_set_charset($conn, 'utf8mb4');
            return $conn;
        }
        usleep(500000); // 0.5 s
    }

    // Último intento (si falla, el error aflora de forma visible).
    $conn = mysqli_connect($host, $user, $pass, $name);
    if ($conn instanceof mysqli) {
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}
