<?php
session_start();

require_once 'Database.php';
require_once 'Article.php';
require_once 'Auth.php';

// Инициализация классов
$db = new Database();
$article = new Article($db);
$auth = new Auth($db);

// Обработка маршрутов
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'login' && $method === 'POST') {
    if ($auth->login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: ?action=admin');
        exit;
    } else {
        $error = "Неверные данные";
    }
} elseif ($action === 'logout') {
    $auth->logout();
    header('Location: ?action=list');
    exit;
} elseif ($action === 'create' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->create(
        $_POST['title'] ?? '',
        $_POST['content'] ?? '',
        $_POST['author'] ?? ''
    );
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'update' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->update(
        (int)($_POST['id'] ?? 0),
        $_POST['title'] ?? '',
        $_POST['content'] ?? '',
        $_POST['author'] ?? ''
    );
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'delete' && $auth->isLoggedIn()) {
    $article->delete((int)($_GET['id'] ?? 0));
    header('Location: ?action=admin');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поп-культура</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Поп-культура</h1>
    
    <?php if ($action === 'list'): ?>
        <h2>Статьи</h2>
        <?php foreach ($article->getAll() as $art): ?>
            <div class="article">
                <h3><?= htmlspecialchars($art['title']) ?></h3>
                <p><?= nl2br(htmlspecialchars($art['content'])) ?></p>
                <p><small>Автор: <?= htmlspecialchars($art['author']) ?> | <?= $art['created_at'] ?></small></p>
            </div>
        <?php endforeach; ?>
        <?php if ($auth->isLoggedIn()): ?>
            <a href="?action=admin">Админ-панель</a>
        <?php else: ?>
            <a href="?action=login">Войти</a>
        <?php endif; ?>
        
    <?php elseif ($action === 'login'): ?>
        <h2>Вход</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="?action=login">
            <input type="text" name="username" placeholder="Имя пользователя" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        
    <?php elseif ($action === 'admin' && $auth->isLoggedIn()): ?>
        <div class="admin-panel">
            <h2>Админ-панель</h2>
            <a href="?action=logout">Выйти</a>
            <h3>Добавить статью</h3>
            <form method="POST" action="?action=create">
                <input type="text" name="title" placeholder="Заголовок" required>
                <textarea name="content" placeholder="Содержание" required></textarea>
                <input type="text" name="author" placeholder="Автор" required>
                <button type="submit">Добавить</button>
            </form>
            
            <h3>Существующие статьи</h3>
            <?php foreach ($article->getAll() as $art): ?>
                <div class="article">
                    <h4><?= htmlspecialchars($art['title']) ?></h4>
                    <form method="POST" action="?action=update">
                        <input type="hidden" name="id" value="<?= $art['id'] ?>">
                        <input type="text" name="title" value="<?= htmlspecialchars($art['title']) ?>" required>
                        <textarea name="content" required><?= htmlspecialchars($art['content']) ?></textarea>
                        <input type="text" name="author" value="<?= htmlspecialchars($art['author']) ?>" required>
                        <button type="submit">Редактировать</button>
                        <a href="?action=delete&id=<?= $art['id'] ?>" onclick="return confirm('Действительно удалить?')">Удалить</a>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <p>Доступ запрещен</p>
        <a href="?action=list">На главную</a>
    <?php endif; ?>
</body>
</html>