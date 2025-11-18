<?php
require_once 'config.php';

// Проверяем, не забанен ли пользователь
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT banned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && $user['banned']) {
        session_destroy();
        header('Location: login.php?banned=1');
        exit;
    }
}

// Получаем товары
$stmt = $pdo->query("SELECT * FROM products WHERE quantity > 0");
$products = $stmt->fetchAll();

// Обработка добавления в корзину
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Находим товар в базе
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product && $quantity > 0 && $quantity <= $product['quantity']) {
        // Добавляем в корзину (в сессию)
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }

        // Редирект в зависимости от кнопки
        if (isset($_POST['action']) && $_POST['action'] == 'buy_now') {
            header('Location: order.php');
            exit;
        } else {
            header('Location: index.php?added=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Магазин</title>
<link rel="stylesheet" href="style.css">
<style>
.products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.product-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.product-card:hover {
    transform: translateY(-5px);
}
.product-image-container {
    width: 100%;
    height: 200px;
    margin-bottom: 15px;
    border-radius: 8px;
    overflow: hidden;
}
.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.no-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}
.product-info {
    flex-grow: 1;
    margin-bottom: 15px;
}
.product-title {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
}
.product-description {
    color: #666;
    margin-bottom: 10px;
    font-size: 0.9em;
}
.product-price {
    font-size: 1.3em;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 5px;
}
.product-stock {
    color: #6c757d;
    font-size: 0.9em;
    margin-bottom: 15px;
}
.product-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
}
.quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.quantity-input {
    width: 70px;
    padding: 8px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.btn-buy {
    background: #28a745;
    flex: 1;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 0.9em;
}
.btn-buy:hover {
    background: #218838;
}
.btn-cart {
    background: #007bff;
    flex: 1;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 0.9em;
}
.btn-cart:hover {
    background: #0056b3;
}
.success-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    text-align: center;
}
.empty-catalog {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    margin: 20px 0;
}
.cart-count {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    margin-left: 5px;
}
.banned-message {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    text-align: center;
}
</style>
</head>
<body>
<header>
<div class="container">
<nav>
<h1>Магазин</h1>
<div class="nav-links">
<?php if (isset($_SESSION['user_id'])): ?>
<span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
<a href="cart.php">
Корзина
<?php if (!empty($_SESSION['cart'])): ?>
<span class="cart-count"><?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?></span>
<?php endif; ?>
</a>
<a href="logout.php">Выйти</a>
<?php if ($_SESSION['role'] == 'admin'): ?>
<a href="admin.php">Админка</a>
<?php endif; ?>
<?php else: ?>
<a href="login.php">Войти</a>
<a href="register.php">Регистрация</a>
<?php endif; ?>
</div>
</nav>
</div>
</header>

<div class="container">
<?php if (isset($_GET['banned'])): ?>
<div class="banned-message">
Ваш аккаунт был заблокирован администратором!
</div>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
<div class="success-message">
Товар добавлен в корзину!
</div>
<?php endif; ?>

<h2>Товары</h2>

<?php if (empty($products)): ?>
<div class="empty-catalog">
<h3>Каталог товаров пуст</h3>
<p>Администратор еще не добавил товары в магазин</p>
<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
<a href="admin.php" class="btn">Добавить товары</a>
<?php endif; ?>
</div>
<?php else: ?>
<div class="products">
<?php foreach ($products as $product): ?>
<div class="product-card">
<div class="product-image-container">
<?php if (!empty($product['image'])): ?>
<img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
<?php else: ?>
<div class="no-image">
Нет изображения
</div>
<?php endif; ?>
</div>

<div class="product-info">
<div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
<div class="product-description"><?= htmlspecialchars($product['description']) ?></div>
<div class="product-price"><?= number_format($product['price'], 0, ',', ' ') ?> руб.</div>
<div class="product-stock">В наличии: <?= $product['quantity'] ?> шт.</div>
</div>

<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user'): ?>
<div class="quantity-selector">
<label>Количество:</label>
<input type="number"
class="quantity-input"
value="1"
min="1"
max="<?= $product['quantity'] ?>"
id="quantity_<?= $product['id'] ?>">
</div>
<div class="product-actions">
<form method="post" style="flex: 1;">
<input type="hidden" name="product_id" value="<?= $product['id'] ?>">
<input type="hidden" name="action" value="buy_now">
<input type="hidden" name="quantity" value="1" id="buy_quantity_<?= $product['id'] ?>">
<button type="submit" class="btn-buy" onclick="document.getElementById('buy_quantity_<?= $product['id'] ?>').value = document.getElementById('quantity_<?= $product['id'] ?>').value">
Купить сейчас
</button>
</form>
<form method="post" style="flex: 1;">
<input type="hidden" name="product_id" value="<?= $product['id'] ?>">
<input type="hidden" name="quantity" value="1" id="cart_quantity_<?= $product['id'] ?>">
<button type="submit" class="btn-cart" onclick="document.getElementById('cart_quantity_<?= $product['id'] ?>').value = document.getElementById('quantity_<?= $product['id'] ?>').value">
В корзину
</button>
</form>
</div>
<?php elseif (!isset($_SESSION['user_id'])): ?>
<div style="text-align: center; margin-top: 15px;">
<a href="login.php" class="btn">Войдите для покупок</a>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<script>
// Автоматическое обновление количества при вводе
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');

    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const max = parseInt(this.getAttribute('max'));
            const value = parseInt(this.value);

            if (value < 1) {
                this.value = 1;
            } else if (value > max) {
                this.value = max;
                alert('Максимальное количество: ' + max);
            }
        });

        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });
    });
});
</script>
</body>
</html>
