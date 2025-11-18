<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart_items = $_SESSION['cart'] ?? [];

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Уменьшаем количество товаров в базе данных
foreach ($cart_items as $product_id => $item) {
    $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    $stmt->execute([$item['quantity'], $product_id]);
}

// Очищаем корзину
$_SESSION['cart'] = [];

?>

<!DOCTYPE html>
<html>
<head>
<title>Заказ оформлен</title>
<link rel="stylesheet" href="style.css">
<style>
.success-container {
    text-align: center;
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 50px auto;
    max-width: 600px;
}
.success-icon {
    font-size: 4em;
    color: #28a745;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<header>
<div class="container">
<nav>
<h1>Магазин</h1>
<div class="nav-links">
<a href="index.php">На главную</a>
<?php if (isset($_SESSION['user_id'])): ?>
<a href="logout.php">Выйти</a>
<?php endif; ?>
</div>
</nav>
</div>
</header>

<div class="container">
<div class="success-container">
<div class="success-icon">✓</div>
<h2>Заказ оформлен успешно!</h2>
<p>Спасибо за ваш заказ! Товары будут отправлены в ближайшее время.</p>
<p>Количество товаров на складе автоматически уменьшено.</p>

<div style="margin: 30px 0;">
<h3>Состав заказа:</h3>
<?php foreach ($cart_items as $item): ?>
<p><?= htmlspecialchars($item['name']) ?> - <?= $item['quantity'] ?> шт. × <?= number_format($item['price'], 0, ',', ' ') ?> руб.</p>
<?php endforeach; ?>
</div>

<a href="index.php" class="btn" style="background: #007bff; padding: 12px 30px;">Вернуться к покупкам</a>
</div>
</div>
</body>
</html>
