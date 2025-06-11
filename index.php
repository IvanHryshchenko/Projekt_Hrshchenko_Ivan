```php
<?php
// Включаем отображение ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'Database.php';
require_once 'Model.php';
require_once 'ContentInterface.php';
require_once 'Article.php';
require_once 'Auth.php';
require_once 'NotificationManager.php';
require_once 'Pagination.php';

// Инициализация классов
$db = new Database();
$article = new Article($db);
$auth = new Auth($db);
$notification = new NotificationManager();

// Обработка маршрутов
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

// Параметры для пагинации и фильтрации
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$search = $_GET['search'] ?? '';
$categoryId = (int)($_GET['category'] ?? 0);

// Обработка действий
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
    $article->create([
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'author' => $_POST['author'] ?? '',
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null
    ]);
    $notification->setNotification('Статья успешно добавлена!');
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'update' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->update((int)($_POST['id'] ?? 0), [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'author' => $_POST['author'] ?? '',
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null
    ]);
    $notification->setNotification('Статья успешно обновлена!');
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'delete' && $auth->isLoggedIn()) {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && $article->deleteById($id)) {
        $notification->setNotification('Статья успешно удалена!');
    } else {
        $notification->setNotification('Ошибка при удалении статьи!');
    }
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'comment' && $method === 'POST') {
    $article->addComment(
        (int)($_POST['article_id'] ?? 0),
        $_POST['comment_author'] ?? 'Аноним',
        $_POST['comment_content'] ?? ''
    );
    $notification->setNotification('Комментарий добавлен!');
    header('Location: ?action=list');
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

    <!-- Уведомления -->
    <?php if ($message = $notification->getNotification()): ?>
        <div class="fixed top-4 right-4 bg-green-500 text-white p-4 rounded shadow-lg notification">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Основной контент -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($action === 'list'): ?>
            <!-- Поиск и фильтр по категориям -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <form method="GET" action="?action=list" class="flex-1">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Поиск по статьям..."
                           class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                </form>
                <form method="GET" action="?action=list" class="w-full sm:w-48">
                    <select name="category" onchange="this.form.submit()"
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                        <option value="0">Все категории</option>
                        <?php foreach ($article->getCategories() as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <h2 class="text-3xl font-poppins text-neon-pink mb-6">Статьи</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $articles = $article->getAll([
                    'page' => $page,
                    'perPage' => $perPage,
                    'search' => $search,
                    'categoryId' => $categoryId
                ]);
                foreach ($articles as $art):
                ?>
                    <div class="article-card bg-gray-800 rounded-lg shadow-lg p-6 hover:shadow-neon transition">
                        <h3 class="text-xl font-semibold text-neon-pink"><?= htmlspecialchars($art['title']) ?></h3>
                        <p class="text-gray-300 mt-2 line-clamp-3"><?= nl2br(htmlspecialchars($art['description'])) ?></p>
                        <p class="text-gray-400 text-sm mt-4">
                            <i class="fas fa-user"></i> Автор: <?= htmlspecialchars($art['author']) ?> | 
                            <i class="fas fa-calendar"></i> <?= $art['created_at'] ?>
                            <?php if ($art['category_name']): ?>
                                | <i class="fas fa-tag"></i> <?= htmlspecialchars($art['category_name']) ?>
                            <?php endif; ?>
                        </p>
                        <!-- Комментарии -->
                        <div class="mt-4">
                            <h4 class="text-lg font-semibold text-gray-300">Комментарии</h4>
                            <?php foreach ($article->getComments($art['id']) as $comment): ?>
                                <div class="comment bg-gray-700 p-3 rounded mt-2">
                                    <p class="text-sm text-gray-400">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($comment['author']) ?> | 
                                        <i class="fas fa-clock"></i> <?= $comment['created_at'] ?>
                                    </p>
                                    <p class="text-gray-300"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                            <form method="POST" action="?action=comment" class="mt-4 space-y-2">
                                <input type="hidden" name="article_id" value="<?= $art['id'] ?>">
                                <input type="text" name="comment_author" placeholder="Ваше имя" required
                                       class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                                <textarea name="comment_content" placeholder="Ваш комментарий" required
                                          class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink h-20"></textarea>
                                <button type="submit" class="bg-neon-pink text-gray-900 py-2 px-4 rounded hover:bg-neon-pink-dark transition">
                                    <i class="fas fa-comment"></i> Добавить комментарий
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Пагинация -->
            <?php
            $pagination = new Pagination($article->getTotal($search, $categoryId), $perPage, $page);
            echo $pagination->render('?action=list', ['search' => $search, 'category' => $categoryId]);
            ?>

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
                    <div>
                        <label for="category_id" class="block text-gray-300">Категория</label>
                        <select id="category_id" name="category_id"
                                class="w-full px-4 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                            <option value="">Без категории</option>
                            <?php foreach ($article->getCategories() as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                                <div>
                                    <label for="category_id-<?= $art['id'] ?>" class="block text-gray-300">Категория</label>
                                    <select id="category_id-<?= $art['id'] ?>" name="category_id"
                                            class="w-full px-4 py-2 bg-gray-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-neon-pink">
                                        <option value="">Без категории</option>
                                        <?php foreach ($article->getCategories() as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $art['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
            <p>© 2025 Поп-культура. Все права защищены.</p>
        </div>
    </footer>

    <!-- Скрипт для автозакрытия уведомлений -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notification = document.querySelector('.notification');
            if (notification) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>