<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Заполните все поля";
    } else {
        try {
            // Проверяем, нет ли уже такого пользователя
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->fetchColumn() > 0) {
                $error = "Пользователь с таким именем уже существует";
            } else {
                // Создаем пользователя
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $password])) {
                    $success = "Регистрация успешна! Теперь вы можете войти.";
                } else {
                    $error = "Ошибка при регистрации";
                }
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = "Пользователь с таким именем уже существует";
            } else {
                $error = "Ошибка базы данных: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Регистрация</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<div class="container">
<nav>
<h1>Магазин</h1>
<div class="nav-links">
<a href="index.php">На главную</a>
<a href="login.php">Войти</a>
</div>
</nav>
</div>
</header>

<div class="container">
<div style="max-width: 400px; margin: 50px auto;">
<h2>Регистрация</h2>

<?php if ($error): ?>
<div style="color: red; background: #ffeaea; padding: 10px; border-radius: 5px; margin: 10px 0;">
<?= $error ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div style="color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;">
<?= $success ?>
<br><a href="login.php" class="btn" style="display: inline-block; margin-top: 10px;">Войти</a>
</div>
<?php else: ?>
<form method="post">
<input type="text" name="username" placeholder="Имя пользователя" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
<input type="password" name="password" placeholder="Пароль" required>
<button type="submit" class="btn" style="width: 100%;">Зарегистрироваться</button>
</form>
<?php endif; ?>

<p style="text-align: center; margin-top: 20px;">
Уже есть аккаунт? <a href="login.php">Войти</a>
</p>
</div>
</div>
</body>
</html>
