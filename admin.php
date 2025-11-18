<?php
require_once 'config.php';

// Проверяем, является ли пользователь администратором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

// Добавление товара
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $image = $_POST['image'];

    $stmt = $pdo->prepare("INSERT INTO products (name, price, quantity, description, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $quantity, $description, $image]);
    $message = "Товар добавлен!";
}

// Редактирование товара
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $image = $_POST['image'];

    $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $price, $quantity, $description, $image, $id]);
    $message = "Товар обновлен!";
}

// Удаление товара
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    $message = "Товар удален!";
}

// Бан/разбан пользователя
if (isset($_GET['ban_user'])) {
    $user_id = $_GET['ban_user'];
    $stmt = $pdo->prepare("UPDATE users SET banned = NOT banned WHERE id = ?");
    $stmt->execute([$user_id]);
    $message = "Статус пользователя изменен!";
}

// Получаем список товаров
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();

// Получаем список пользователей
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>Панель администратора</title>
<link rel="stylesheet" href="style.css">
<style>
.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}
.edit-form {
    background: #f9f9f9;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
}
.empty-products {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    margin: 20px 0;
}
.users-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
.users-table th, .users-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}
.users-table th {
    background-color: #f2f2f2;
}
.banned {
    color: red;
    font-weight: bold;
}
</style>
</head>
<body>
<header>
<div class="container">
<nav>
<h1>Админка</h1>
<div class="nav-links">
<a href="index.php">На сайт</a>
<a href="logout.php">Выйти</a>
</div>
</nav>
</div>
</header>

<div class="container">
<?php if (isset($message)): ?>
<p style="color: green; padding: 10px; background: #e8f5e8; border-radius: 5px;"><?= $message ?></p>
<?php endif; ?>

<h2>Добавить товар</h2>
<form method="post">
<input type="text" name="name" placeholder="Название товара" required>
<input type="number" step="0.01" name="price" placeholder="Цена" required>
<input type="number" name="quantity" placeholder="Количество" required>
<textarea name="description" placeholder="Описание" style="width:100%; height:100px;"></textarea>
<input type="text" name="image" placeholder="URL изображения">
<button type="submit" name="add_product" class="btn">Добавить товар</button>
</form>

<h2>Управление товарами</h2>

<?php if (empty($products)): ?>
<div class="empty-products">
<h3>Товаров пока нет</h3>
<p>Добавьте первый товар используя форму выше</p>
</div>
<?php else: ?>
<?php foreach ($products as $product): ?>
<div class="edit-form">
<form method="post">
<input type="hidden" name="id" value="<?= $product['id'] ?>">

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
<div>
<label>Название:</label>
<input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
</div>
<div>
<label>Цена:</label>
<input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
</div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
<div>
<label>Количество:</label>
<input type="number" name="quantity" value="<?= $product['quantity'] ?>" required>
</div>
<div>
<label>URL изображения:</label>
<input type="text" name="image" value="<?= htmlspecialchars($product['image']) ?>" placeholder="URL изображения">
</div>
</div>

<div style="margin-bottom: 15px;">
<label>Описание:</label>
<textarea name="description" style="width:100%; height:80px;"><?= htmlspecialchars($product['description']) ?></textarea>
</div>

<?php if (!empty($product['image'])): ?>
<div style="margin-bottom: 15px;">
<label>Текущее изображение:</label><br>
<img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
</div>
<?php endif; ?>

<div style="display: flex; gap: 10px;">
<button type="submit" name="edit_product" class="btn" style="background: #28a745;">Сохранить изменения</button>
<a href="?delete_id=<?= $product['id'] ?>" class="btn" style="background: #dc3545;" onclick="return confirm('Удалить этот товар?')">Удалить товар</a>
</div>
</form>
</div>
<?php endforeach; ?>
<?php endif; ?>

<h2>Управление пользователями</h2>
<table class="users-table">
<tr>
<th>ID</th>
<th>Имя пользователя</th>
<th>Роль</th>
<th>Статус</th>
<th>Действия</th>
</tr>
<?php foreach ($users as $user): ?>
<tr>
<td><?= $user['id'] ?></td>
<td><?= htmlspecialchars($user['username']) ?></td>
<td><?= $user['role'] ?></td>
<td>
<?php if ($user['banned']): ?>
<span class="banned">Забанен</span>
<?php else: ?>
Активен
<?php endif; ?>
</td>
<td>
<a href="?ban_user=<?= $user['id'] ?>" class="btn" style="background: <?= $user['banned'] ? '#28a745' : '#dc3545' ?>;">
<?= $user['banned'] ? 'Разбанить' : 'Забанить' ?>
</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
