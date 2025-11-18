<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Возвращаем обратно в корзину
header('Location: cart.php');
exit;
?>
