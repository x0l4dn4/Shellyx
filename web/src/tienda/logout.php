<?php
require_once __DIR__ . '/../lib/shop_auth.php';
shop_logout();
header('Location: /tienda/');
exit;
