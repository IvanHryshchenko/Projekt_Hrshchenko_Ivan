<?php
// Включаем отображение ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
        $_POST['description'] ?? '',
        $_POST['author'] ?? ''
    );
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'update' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->update(
        (int)($_POST['id'] ?? 0),
        $_POST['title'] ?? '',
        $_POST['description'] ?? '',
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
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поп-культура</title>
    <!-- Tailwind CSS через CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts (Inter и Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@700&display=swap" rel="stylesheet">
    <!-- Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Кастомные стили -->
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-900 text-white font-inter">
    <!-- Навигация -->
    <nav class="bg-gray-800 py-4 shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-poppins text-neon-pink">Поп-культура</h1>
            <div class="space-x-4">
                <a href="?action=list" class="text-gray-300 hover:text-neon-pink transition <?= $action === 'list' ? 'font-semibold' : '' ?>">
                    <i class="fas fa-home"></i> Главная
                </a>
                <?php if ($auth->isLoggedIn()): ?>
                    <a href="?action=admin" class="text-gray-300 hover:text-neon-pink transition <?= $action === 'admin' ? 'font-semibold' : '' ?>">
                        <i class="fas fa-user-shield"></i> Админ-панель
                    </a>
                    <a href="?action=logout" class="text-gray-300 hover:text-neon-pink transition">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                <?php else: ?>
                    <a href="?action=login" class="text-gray-300 hover:text-neon-pink transition <?= $action === 'login' ? 'font-semibold' : '' ?>">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($action === 'list'): ?>
            <h2 class="text-3xl font-poppins text-neon-pink mb-6">Статьи</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($article->getAll() as $art): ?>
                    <div class="article-card bg-gray-800 rounded-lg shadow-lg p-6 hover:shadow-neon transition">
                        <h3 class="text-xl font-semibold text-neon-pink"><?= htmlspecialchars($art['title']) ?></h3>
                        <p class="text-gray-300 mt-2 line-clamp-3"><?= nl2br(htmlspecialchars($art['description'])) ?></p>
                        <p class="text-gray-400 text-sm mt-4">
                            <i class="fas fa-user"></i> Автор: <?= htmlspecialchars($art['author']) ?> | 
                            <i class="fas fa-calendar"></i> <?= $art['created_at'] ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($action === 'login'): ?>
            <div class="max-w-md mx-auto bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-poppins text-neon-pink mb-6 text-center">Вход</h2>
                <?php if (isset($error)): ?>
                    <p class="error bg-red-500 text-white p-4 rounded mb-4"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="POST" action="?action=login" class="space-y-4">
                    <div>
                        <label for="username" class="block text-gray-300">Имя пользователя</label>
                        <input type="text" id="username" name="username" placeholder="Введите имя пользователя" required
                               class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                    </div>
                    <div>
                        <label for="password" class="block text-gray-300">Пароль</label>
                        <input type="password" id="password" name="password" placeholder="Введите пароль" required
                               class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                    </div>
                    <button type="submit" class="w-full bg-neon-pink text-gray-900 py-2 rounded hover:bg-neon-pink-dark transition">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </form>
            </div>

        <?php elseif ($action === 'admin' && $auth->isLoggedIn()): ?>
            <div class="bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-poppins text-neon-pink mb-6">Админ-панель</h2>
                
                <!-- Форма добавления статьи -->
                <h3 class="text-xl font-semibold text-gray-300 mb-4">Добавить статью</h3>
                <form method="POST" action="?action=create" class="space-y-4 mb-8">
                    <div>
                        <label for="title" class="block text-gray-300">Заголовок</label>
                        <input type="text" id="title" name="title" placeholder="Введите заголовок" required
                               class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                    </div>
                    <div>
                        <label for="description" class="block text-gray-300">Описание</label>
                        <textarea id="description" name="description" placeholder="Введите описание" required
                                  class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink h-32"></textarea>
                    </div>
                    <div>
                        <label for="author" class="block text-gray-300">Автор</label>
                        <input type="text" id="author" name="author" placeholder="Введите автора" required
                               class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                    </div>
                    <button type="submit" class="bg-neon-pink text-gray-900 py-2 px-6 rounded hover:bg-neon-pink-dark transition">
                        <i class="fas fa-plus"></i> Добавить
                    </button>
                </form>

                <!-- Список статей -->
                <h3 class="text-xl font-semibold text-gray-300 mb-4">Существующие статьи</h3>
                <div class="space-y-6">
                    <?php foreach ($article->getAll() as $art): ?>
                        <div class="article-card bg-gray-700 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-neon-pink"><?= htmlspecialchars($art['title']) ?></h4>
                            <form method="POST" action="?action=update" class="space-y-4">
                                <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                <div>
                                    <label for="title-<?= $art['id'] ?>" class="block text-gray-300">Заголовок</label>
                                    <input type="text" id="title-<?= $art['id'] ?>" name="title" value="<?= htmlspecialchars($art['title']) ?>" required
                                           class="w-full px-4 py-2 bg-gray-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                                </div>
                                <div>
                                    <label for="description-<?= $art['id'] ?>" class="block text-gray-300">Описание</label>
                                    <textarea id="description-<?= $art['id'] ?>" name="description" required
                                              class="w-full px-4 py-2 bg-gray-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink h-32"><?= htmlspecialchars($art['description']) ?></textarea>
                                </div>
                                <div>
                                    <label for="author-<?= $art['id'] ?>" class="block text-gray-300">Автор</label>
                                    <input type="text" id="author-<?= $art['id'] ?>" name="author" value="<?= htmlspecialchars($art['author']) ?>" required
                                           class="w-full px-4 py-2 bg-gray-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                                </div>
                                <div class="flex space-x-4">
                                    <button type="submit" class="bg-neon-pink text-gray-900 py-2 px-4 rounded hover:bg-neon-pink-dark transition">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                    <a href="?action=delete&id=<?= $art['id'] ?>" onclick="return confirm('Действительно удалить?')"
                                       class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600 transition">
                                        <i class="fas fa-trash"></i> Удалить
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center">
                <p class="text-xl text-red-500">Доступ запрещён</p>
                <a href="?action=list" class="text-neon-pink hover:underline">На главную</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Футер -->
    <footer class="bg-gray-800 py-4 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-400">
            <p>&copy; 2025 Поп-культура. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>