<?php
// Sesión y autenticación del foro.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../lib/db.php';

// Login con sentencia preparada.
function attempt_login(string $u, string $p): bool
{
    $stmt = mysqli_prepare(
        db(),
        "SELECT id, username FROM users WHERE username = ? AND password_md5 = MD5(?)"
    );
    mysqli_stmt_bind_param($stmt, "ss", $u, $p);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($row) {
        $_SESSION['uid'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        return true;
    }
    return false;
}

function current_user(): ?array
{
    if (isset($_SESSION['uid'])) {
        return ['id' => $_SESSION['uid'], 'username' => $_SESSION['username']];
    }
    return null;
}

function require_login(): void
{
    if (current_user() === null) {
        header('Location: /foro/login.php');
        exit;
    }
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
