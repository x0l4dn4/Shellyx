<?php
// Cuentas de la tienda (clientes). Sesión propia, independiente del foro: el foro
// sigue siendo solo por invitación y el registro de la tienda es abierto.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function shop_current(): ?array
{
    if (isset($_SESSION['cust_id'])) {
        return ['id' => $_SESSION['cust_id'], 'username' => $_SESSION['cust_name']];
    }
    return null;
}

// Login con sentencia preparada.
function shop_login(string $u, string $p): bool
{
    $stmt = mysqli_prepare(db(), "SELECT id, username FROM customers WHERE username = ? AND password_md5 = MD5(?)");
    mysqli_stmt_bind_param($stmt, "ss", $u, $p);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) {
        $_SESSION['cust_id']   = $row['id'];
        $_SESSION['cust_name'] = $row['username'];
        return true;
    }
    return false;
}

// Registro abierto. Devuelve [ok(bool), error(string)].
function shop_register(string $u, string $p): array
{
    $u = trim($u);
    if ($u === '' || mb_strlen($p) < 6) {
        return [false, 'El usuario no puede estar vacío y la contraseña debe tener al menos 6 caracteres.'];
    }
    $stmt = mysqli_prepare(db(), "SELECT id FROM customers WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $u);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        return [false, 'Ese usuario ya existe.'];
    }
    $stmt = mysqli_prepare(db(), "INSERT INTO customers (username, password_md5) VALUES (?, MD5(?))");
    mysqli_stmt_bind_param($stmt, "ss", $u, $p);
    mysqli_stmt_execute($stmt);
    $_SESSION['cust_id']   = mysqli_insert_id(db());
    $_SESSION['cust_name'] = $u;
    return [true, ''];
}

function shop_require_login(): void
{
    if (shop_current() === null) {
        header('Location: /tienda/login.php');
        exit;
    }
}

function shop_logout(): void
{
    unset($_SESSION['cust_id'], $_SESSION['cust_name']);
}
