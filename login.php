<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        // Проверяем, не забанен ли пользователь
        if ($user['banned']) {
            $error = "Ваш аккаунт заблокирован! Обратитесь к администратору.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        }
    } else {
        $error = "Неверное имя пользователя или пароль";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Вход</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<div class="container">
<nav>
<h1>Магазин</h1>
<div class="nav-links">
<a href="index.php">На главную</a>
<a href="register.php">Регистрация</a>
</div>
</nav>
</div>
</header>

<div class="container">
<div style="max-width: 400px; margin: 50px auto;">
<h2>Вход</h2>
<?php if ($error): ?>
<div style="color: red; background: #ffeaea; padding: 10px; border-radius: 5px; margin: 10px 0;">
<?= $error ?>
</div>
<?php endif; ?>

<form method="post">
<input type="text" name="username" placeholder="Имя пользователя" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
<input type="password" name="password" placeholder="Пароль" required>
<button type="submit" class="btn" style="width: 100%;">Войти</button>
</form>

<p style="text-align: center; margin-top: 20px;">
Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
</p>

<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
<strong>Тестовый аккаунт администратора:</strong><br>
Логин: admin<br>
Пароль: admin123
</div>
</div>
</div>
</body>
</html>
