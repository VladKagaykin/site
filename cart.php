<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Обработка изменения количества через AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_update'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }

    // Пересчитываем общую сумму
    $total = 0;
    foreach ($_SESSION['cart'] as $id => $item) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product) {
            $total += $product['price'] * $item['quantity'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'total' => $total]);
    exit;
}

$cart_items = $_SESSION['cart'] ?? [];
$total = 0;

// Подгружаем актуальные данные о товарах
foreach ($cart_items as $product_id => $item) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        $cart_items[$product_id]['current_price'] = $product['price'];
        $cart_items[$product_id]['current_quantity'] = $product['quantity'];
        $total += $product['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Корзина</title>
<link rel="stylesheet" href="style.css">
<style>
.cart-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.cart-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}
.cart-item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 5px;
    margin-right: 15px;
}
.cart-item-info {
    flex-grow: 1;
}
.cart-item-name {
    font-weight: bold;
    margin-bottom: 5px;
}
.cart-item-price {
    color: #28a745;
    font-weight: bold;
}
.cart-quantity {
    width: 70px;
    padding: 8px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.cart-total {
    text-align: right;
    font-size: 1.3em;
    font-weight: bold;
    margin: 20px 0;
    padding-top: 20px;
    border-top: 2px solid #eee;
}
.empty-cart {
    text-align: center;
    padding: 40px;
    color: #666;
}
.remove-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 10px;
    text-decoration: none;
    display: inline-block;
}
.item-total {
    font-weight: bold;
    margin-left: 15px;
    min-width: 100px;
    text-align: right;
}
.loading {
    opacity: 0.6;
}
</style>
</head>
<body>
<header>
<div class="container">
<nav>
<h1>Корзина</h1>
<div class="nav-links">
<a href="index.php">Назад к товарам</a>
<a href="logout.php">Выйти</a>
</div>
</nav>
</div>
</header>

<div class="container">
<div class="cart-container">
<?php if (empty($cart_items)): ?>
<div class="empty-cart">
<h3>Корзина пуста</h3>
<p>Добавьте товары из каталога</p>
<a href="index.php" class="btn">Перейти к покупкам</a>
</div>
<?php else: ?>
<div id="cart-items">
<?php foreach ($cart_items as $product_id => $item): ?>
<?php
$item_price = $item['current_price'] ?? $item['price'];
$item_total = $item_price * $item['quantity'];
?>
<div class="cart-item" id="cart-item-<?= $product_id ?>">
<?php if (!empty($item['image'])): ?>
<img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-image">
<?php else: ?>
<div class="cart-item-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 12px;">
Нет изображения
</div>
<?php endif; ?>

<div class="cart-item-info">
<div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
<div class="cart-item-price">
<?= number_format($item_price, 0, ',', ' ') ?> руб.
</div>
</div>

<input type="number"
class="cart-quantity"
value="<?= $item['quantity'] ?>"
min="1"
max="<?= $item['current_quantity'] ?? 99 ?>"
data-product-id="<?= $product_id ?>"
data-price="<?= $item_price ?>">

<div class="item-total" id="item-total-<?= $product_id ?>">
<?= number_format($item_total, 0, ',', ' ') ?> руб.
</div>

<a href="remove_from_cart.php?product_id=<?= $product_id ?>" class="remove-btn">Удалить</a>
</div>
<?php endforeach; ?>
</div>

<div class="cart-total" id="cart-total">
Итого: <?= number_format($total, 0, ',', ' ') ?> руб.
</div>

<div style="text-align: center; margin-top: 30px;">
<a href="order.php" class="btn" style="background: #28a745; padding: 15px 30px; font-size: 1.1em; text-decoration: none;">
Оформить заказ
</a>
</div>
<?php endif; ?>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.cart-quantity');

    quantityInputs.forEach(input => {
        // Сохраняем исходное значение
        input.defaultValue = input.value;

        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const price = parseFloat(this.getAttribute('data-price'));
            const quantity = parseInt(this.value);
            const maxQuantity = parseInt(this.getAttribute('max'));

            // Проверяем допустимость значения
            if (quantity < 1) {
                this.value = 1;
                return;
            }

            if (quantity > maxQuantity) {
                this.value = maxQuantity;
                alert('Нельзя добавить больше ' + maxQuantity + ' шт. этого товара');
                return;
            }

            // Показываем состояние загрузки
            const cartItem = document.getElementById('cart-item-' + productId);
            cartItem.classList.add('loading');

            // Отправляем AJAX-запрос
            const formData = new FormData();
            formData.append('ajax_update', 'true');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем сумму для товара
                    const itemTotalElement = document.getElementById('item-total-' + productId);
                    const newItemTotal = price * quantity;
                    itemTotalElement.textContent = newItemTotal.toLocaleString('ru-RU') + ' руб.';

            // Обновляем общую сумму
            const cartTotalElement = document.getElementById('cart-total');
            cartTotalElement.textContent = 'Итого: ' + data.total.toLocaleString('ru-RU') + ' руб.';

            // Обновляем исходное значение
            input.defaultValue = quantity;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при обновлении корзины');
                this.value = input.defaultValue;
            })
            .finally(() => {
                // Убираем состояние загрузки
                cartItem.classList.remove('loading');
            });
        });

        // Обработка клавиш (Enter для быстрого обновления)
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.blur(); // Убираем фокус, что вызовет событие change
            }
        });
    });
});
</script>
</body>
</html>
