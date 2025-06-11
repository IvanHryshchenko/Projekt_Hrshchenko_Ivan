<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'config.php';
require_once 'Database.php';
require_once 'Article.php';
require_once 'Auth.php';
require_once 'Notification.php';

try {
    $db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    $article = new Article($db);
    $auth = new Auth($db);
    $notification = new Notification();
} catch (Exception $e) {
    die("Ошибка инициализации: " . $e->getMessage());
}

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$search = $_GET['search'] ?? '';
$categoryId = (int)($_GET['category'] ?? 0);

if ($action === 'login' && $method === 'POST') {
    if ($auth->login($_POST['username'], $_POST['password'])) {
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
    $data = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'author' => trim($_POST['author']),
        'category_id' => $_POST['category_id'] ?: null
    ];
    if ($article->create($data)) {
        $notification->set('Статья добавлена!');
    } else {
        $notification->set('Ошибка добавления статьи!');
    }
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'update' && $method === 'POST' && $auth->isLoggedIn()) {
    $id = (int)($_POST['id']);
    $data = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'author' => trim($_POST['author']),
        'category_id' => $_POST['category_id'] ?: null
    ];
    if ($article->update($id, $data)) {
        $notification->set('Статья обновлена!');
    } else {
        $notification->set('Ошибка обновления статьи!');
    }
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'delete' && $auth->isLoggedIn()) {
    $id = (int)($_GET['id']);
    if ($article->delete($id)) {
        $notification->set('Статья удалена!');
    } else {
        $notification->set('Ошибка удаления статьи!');
    }
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'comment' && $method === 'POST') {
    if ($article->addComment($_POST['article_id'], $_POST['comment_author'], $_POST['comment_content'])) {
        $notification->set('Комментарий добавлен!');
    }
    header('Location: ?action=list');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поп-культура</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-900 text-white font-inter">
    <nav class="bg-gray-800 py-4 shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-poppins text-pink-600">Поп-культура</h1>
            <div class="space-x-4">
                <a href="?action=list" class="text-gray-300 hover:text-pink-600 <?= $action === 'list' ? 'font-bold' : '' ?>">
                    <i class="fas fa-home"></i> Главная
                </a>
                <?php if ($auth->isLoggedIn()): ?>
                    <a href="?action=admin" class="text-gray-300 hover:text-pink-600 <?= $action === 'admin' ? 'font-bold' : '' ?>">
                        <i class="fas fa-user-shield"></i> Админ
                    </a>
                    <a href="?action=logout" class="text-gray-300 hover:text-pink-600">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                <?php else: ?>
                    <a href="?action=login" class="text-gray-300 hover:text-pink-600 <?= $action === 'login' ? 'font-bold' : '' ?>">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($message = $notification->get()): ?>
        <div class="fixed top-4 right-4 bg-green-500 text-white p-3 rounded shadow-lg transition-opacity duration-300" id="notification">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($action === 'list'): ?>
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <form method="GET" action="" class="flex-1">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Поиск..."
                           class="w-full px-4 py-2 bg-gray-800 text-white rounded focus:outline-none focus:ring-2 focus:ring-pink-600">
                </form>
                <form method="GET" action="" class="w-full sm:w-48">
                    <select name="category" onchange="this.form.submit()"
                            class="w-full px-4 py-2 bg-gray-800 text-white rounded focus:outline-none focus:ring-2 focus:ring-pink-600">
                        <option value="0">Все категории</option>
                        <?php foreach ($article->getCategories() as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <h2 class="text-3xl font-poppins text-pink-600 mb-6">Статьи</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $articles = $article->getAll($page, $perPage, $search, $categoryId);
                if (empty($articles)) {
                    echo '<p class="text-center text-gray-500">Статьи не найдены.</p>';
                } else {
                    foreach ($articles as $art): ?>
                        <div class="bg-gray-800 rounded-lg p-4 shadow hover:shadow-lg transition">
                            <h3 class="text-xl font-semibold text-pink-600"><?= htmlspecialchars($art['title']) ?></h3>
                            <p class="text-gray-400 mt-2 line-clamp-3"><?= htmlspecialchars($art['description']) ?></p>
                            <p class="text-gray-500 text-sm mt-2">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($art['author']) ?> | 
                                <i class="fas fa-calendar"></i> <?= $art['created_at'] ?>
                                <?php if ($art['category_name']): ?>
                                    | <i class="fas fa-tag"></i> <?= htmlspecialchars($art['category_name']) ?>
                                <?php endif; ?>
                            </p>
                            <div class="mt-4">
                                <h4 class="text-lg font-semibold text-gray-300">Комментарии</h4>
                                <?php $comments = $article->getComments($art['id']);
                                if (empty($comments)) {
                                    echo '<p class="text-gray-500">Нет комментариев.</p>';
                                } else {
                                    foreach ($comments as $comment): ?>
                                        <div class="bg-gray-700 p-2 rounded mt-2">
                                            <p class="text-sm text-gray-400">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($comment['author']) ?> | 
                                                <i class="fas fa-clock"></i> <?= $comment['created_at'] ?>
                                            </p>
                                            <p class="text-gray-300"><?= htmlspecialchars($comment['content']) ?></p>
                                        </div>
                                    <?php endforeach;
                                } ?>
                                <form method="POST" action="?action=comment" class="mt-4 space-y-2">
                                    <input type="hidden" name="article_id" value="<?= $art['id'] ?>">
                                    <input type="text" name="comment_author" placeholder="Ваше имя" required
                                           class="w-full px-3 py-1 bg-gray-700 text-white rounded">
                                    <textarea name="comment_content" placeholder="Комментарий" required
                                              class="w-full px-3 py-1 bg-gray-700 text-white rounded h-16"></textarea>
                                    <button type="submit" class="bg-pink-600 text-white px-3 py-1 rounded hover:bg-pink-700">
                                        <i class="fas fa-comment"></i> Добавить
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach;
                } ?>
            </div>
            <?php echo $article->renderPagination($page, $perPage, $search, $categoryId); ?>

        <?php elseif ($action === 'login'): ?>
            <div class="max-w-md mx-auto bg-gray-800 rounded-lg p-6 shadow-lg mt-10">
                <h2 class="text-2xl font-poppins text-pink-600 mb-4 text-center">Вход</h2>
                <?php if (isset($error)): ?>
                    <p class="bg-red-500 text-white p-2 rounded mb-4"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label for="username" class="block text-gray-300">Имя пользователя</label>
                        <input type="text" id="username" name="username" required
                               class="w-full px-3 py-1 bg-gray-700 text-white rounded">
                    </div>
                    <div>
                        <label for="password" class="block text-gray-300">Пароль</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-1 bg-gray-700 text-white rounded">
                    </div>
                    <button type="submit" class="w-full bg-pink-600 text-white py-2 rounded hover:bg-pink-700">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </form>
            </div>

        <?php elseif ($action === 'admin' && $auth->isLoggedIn()): ?>
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg mt-10">
                <h2 class="text-2xl font-poppins text-pink-600 mb-4">Админ-панель</h2>
                <h3 class="text-lg font-semibold text-gray-300 mb-3">Добавить статью</h3>
                <form method="POST" action="" class="space-y-4 mb-6">
                    <div>
                        <label for="title" class="block text-gray-300">Заголовок</label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-1 bg-gray-700 text-white rounded">
                    </div>
                    <div>
                        <label for="description" class="block text-gray-300">Описание</label>
                        <textarea id="description" name="description" required
                                  class="w-full px-3 py-1 bg-gray-700 text-white rounded h-24"></textarea>
                    </div>
                    <div>
                        <label for="author" class="block text-gray-300">Автор</label>
                        <input type="text" id="author" name="author" required
                               class="w-full px-3 py-1 bg-gray-700 text-white rounded">
                    </div>
                    <div>
                        <label for="category_id" class="block text-gray-300">Категория</label>
                        <select id="category_id" name="category_id"
                                class="w-full px-3 py-1 bg-gray-700 text-white rounded">
                            <option value="">Без категории</option>
                            <?php foreach ($article->getCategories() as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700">
                        <i class="fas fa-plus"></i> Добавить
                    </button>
                </form>

                <h3 class="text-lg font-semibold text-gray-300 mb-3">Список статей</h3>
                <?php $articles = $article->getAll(); ?>
                <?php if (empty($articles)): ?>
                    <p class="text-gray-500">Статьи отсутствуют.</p>
                <?php else: ?>
                    <?php foreach ($articles as $art): ?>
                        <div class="bg-gray-700 rounded-lg p-4 mb-4">
                            <h4 class="text-lg font-semibold text-pink-600"><?= htmlspecialchars($art['title']) ?></h4>
                            <form method="POST" action="" class="space-y-4">
                                <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                <div>
                                    <label for="title-<?= $art['id'] ?>" class="block text-gray-300">Заголовок</label>
                                    <input type="text" id="title-<?= $art['id'] ?>" name="title" value="<?= htmlspecialchars($art['title']) ?>" required
                                           class="w-full px-3 py-1 bg-gray-600 text-white rounded">
                                </div>
                                <div>
                                    <label for="description-<?= $art['id'] ?>" class="block text-gray-300">Описание</label>
                                    <textarea id="description-<?= $art['id'] ?>" name="description" required
                                              class="w-full px-3 py-1 bg-gray-600 text-white rounded h-24"><?= htmlspecialchars($art['description']) ?></textarea>
                                </div>
                                <div>
                                    <label for="author-<?= $art['id'] ?>" class="block text-gray-300">Автор</label>
                                    <input type="text" id="author-<?= $art['id'] ?>" name="author" value="<?= htmlspecialchars($art['author']) ?>" required
                                           class="w-full px-3 py-1 bg-gray-600 text-white rounded">
                                </div>
                                <div>
                                    <label for="category_id-<?= $art['id'] ?>" class="block text-gray-300">Категория</label>
                                    <select id="category_id-<?= $art['id'] ?>" name="category_id"
                                            class="w-full px-3 py-1 bg-gray-600 text-white rounded">
                                        <option value="">Без категории</option>
                                        <?php foreach ($article->getCategories() as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $art['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex space-x-4">
                                    <button type="submit" class="bg-pink-600 text-white px-3 py-1 rounded hover:bg-pink-700">
                                        <i class="fas fa-edit"></i> Сохранить
                                    </button>
                                    <a href="?action=delete&id=<?= $art['id'] ?>" onclick="return confirm('Удалить?')"
                                       class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                        <i class="fas fa-trash"></i> Удалить
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>

        <?php else: ?>
            <div class="text-center mt-10">
                <p class="text-xl text-red-500">Доступ запрещён</p>
                <a href="?action=list" class="text-pink-600 hover:underline">На главную</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-800 py-4 mt-8 text-center text-gray-500">
        <p>© 2025 Поп-культура</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => notification.style.opacity = '0', 2500);
                setTimeout(() => notification.remove(), 2800);
            }
        });
    </script>
</body>
</html>