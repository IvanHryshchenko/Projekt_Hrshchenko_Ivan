<?php
// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'Database.php';
require_once 'Article.php';
require_once 'Auth.php';

// Initialize classes
$db = new Database();
$article = new Article($db);
$auth = new Auth($db);

// Handle routes
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'login' && $method === 'POST') {
    if ($auth->login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: ?action=admin');
        exit;
    } else {
        $error = "Invalid credentials";
    }
} elseif ($action === 'logout') {
    $auth->logout();
    header('Location: ?action=list');
    exit;
} elseif ($action === 'create' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->create(
        ['title' => $_POST['title'] ?? '', 'description' => $_POST['description'] ?? '', 'author' => $_POST['author'] ?? '']
    );
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'update' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->update(
        (int)($_POST['id'] ?? 0),
        ['title' => $_POST['title'] ?? '', 'description' => $_POST['description'] ?? '', 'author' => $_POST['author'] ?? '']
    );
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'delete' && $auth->isLoggedIn()) {
    $article->delete((int)($_GET['id'] ?? 0));
    header('Location: ?action=admin');
    exit;
} elseif ($action === 'like' && $auth->isLoggedIn()) {
    $article->like((int)($_GET['id'] ?? 0), $_SESSION['user_id']);
    header('Location: ?action=list');
    exit;
} elseif ($action === 'unlike' && $auth->isLoggedIn()) {
    $article->unlike((int)($_GET['id'] ?? 0), $_SESSION['user_id']);
    header('Location: ?action=list');
    exit;
} elseif ($action === 'comment' && $method === 'POST' && $auth->isLoggedIn()) {
    $article->addComment((int)($_GET['id'] ?? 0), $_SESSION['user_id'], $_POST['content'] ?? '');
    header('Location: ?action=list');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pop Culture</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="font-inter" data-theme="blue">
    <nav class="bg-gray-800 py-4 shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-poppins">Pop Culture</h1>
            <div class="flex items-center space-x-4">
                <a href="?action=list" class="text-gray-300 hover:text-neon-pink transition">
                    <i class="fas fa-home"></i> Home
                </a>
                <?php if ($auth->isLoggedIn()): ?>
                    <a href="?action=admin" class="text-gray-300 hover:text-neon-pink transition">
                        <i class="fas fa-user-shield"></i> Admin Panel
                    </a>
                    <a href="?action=logout" class="text-gray-300 hover:text-neon-pink transition">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="?action=login" class="text-gray-300 hover:text-neon-pink transition">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
                <div class="theme-switcher">
                    <i class="fas fa-palette"></i>
                    <div class="theme-options">
                        <div class="theme-option" data-theme="black-red">Black-Red</div>
                        <div class="theme-option" data-theme="blue">Blue</div>
                        <div class="theme-option" data-theme="green">Green</div>
                        <div class="theme-option" data-theme="purple">Purple</div>
                        <div class="theme-option" data-theme="yellow">Yellow</div>
                        <div class="theme-option" data-theme="orange">Orange</div>
                        <div class="theme-option" data-theme="pink">Pink</div>
                        <div class="theme-option" data-theme="cyan">Cyan</div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($action === 'list'): ?>
            <h2 class="text-3xl font-poppins mb-6">Articles</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($article->getAll() as $art): ?>
                    <div class="article-card rounded-lg shadow-lg p-6 hover:shadow-neon transition">
                        <h3 class="text-xl font-semibold"><?= htmlspecialchars($art['title']) ?></h3>
                        <p class="mt-2 line-clamp-3"><?= nl2br(htmlspecialchars($art['description'])) ?></p>
                        <p class="text-sm mt-4">
                            <i class="fas fa-user"></i> Author: <?= htmlspecialchars($art['author']) ?> | 
                            <i class="fas fa-calendar"></i> <?= $art['created_at'] ?>
                        </p>
                        <div class="mt-4">
                            <span>
                                <i class="fas fa-heart"></i> Likes: <?= $article->getLikes($art['id']) ?>
                            </span>
                            <?php if ($auth->isLoggedIn()): ?>
                                <?php if ($article->hasLiked($art['id'], $_SESSION['user_id'])): ?>
                                    <a href="?action=unlike&id=<?= $art['id'] ?>" class="ml-2 hover:text-red-600">
                                        <i class="fas fa-heart-broken"></i> Unlike
                                    </a>
                                <?php else: ?>
                                    <a href="?action=like&id=<?= $art['id'] ?>" class="ml-2 hover:text-neon-pink-dark">
                                        <i class="fas fa-heart"></i> Like
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col h-full">
                            <div class="comments-section">
                                <h4 class="text-lg font-semibold">Comments</h4>
                                <?php foreach ($article->getComments($art['id']) as $comment): ?>
                                    <div class="p-2 rounded mt-2">
                                        <p style="word-break: break-word; white-space: normal;"><?= htmlspecialchars($comment['content']) ?></p>
                                        <p class="text-sm">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($comment['username']) ?> | 
                                            <i class="fas fa-calendar"></i> <?= $comment['created_at'] ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($auth->isLoggedIn()): ?>
                                <div class="comment-form">
                                    <form method="POST" action="?action=comment&id=<?= $art['id'] ?>">
                                        <textarea name="content" placeholder="Your comment" required></textarea>
                                        <button type="submit">
                                            <i class="fas fa-comment"></i> Add
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($action === 'login'): ?>
            <div class="max-w-md mx-auto rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-poppins mb-6 text-center">Login</h2>
                <?php if (isset($error)): ?>
                    <p class="error text-white p-4 rounded mb-4"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="POST" action="?action=login" class="space-y-4">
                    <div>
                        <label for="username" class="block">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required
                               class="w-full px-4 py-2 rounded focus:outline-none">
                    </div>
                    <div>
                        <label for="password" class="block">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required
                               class="w-full px-4 py-2 rounded focus:outline-none">
                    </div>
                    <button type="submit" class="w-full py-2 rounded">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
            </div>

        <?php elseif ($action === 'admin' && $auth->isLoggedIn()): ?>
            <div class="admin-panel">
                <h2 class="text-2xl font-poppins mb-6">Admin Panel</h2>
                <h3 class="text-xl font-semibold mb-4">Add Article</h3>
                <form method="POST" action="?action=create" class="space-y-4 mb-8">
                    <div>
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" placeholder="Enter title" required>
                    </div>
                    <div>
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Enter description" required></textarea>
                    </div>
                    <div>
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" placeholder="Enter author" required>
                    </div>
                    <button type="submit">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </form>
                <h3 class="text-xl font-semibold mb-4">Existing Articles</h3>
                <div class="space-y-6">
                    <?php foreach ($article->getAll() as $art): ?>
                        <div class="admin-article-card">
                            <h4><?= htmlspecialchars($art['title']) ?></h4>
                            <form method="POST" action="?action=update">
                                <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                <div>
                                    <label for="title-<?= $art['id'] ?>">Title</label>
                                    <input type="text" id="title-<?= $art['id'] ?>" name="title" value="<?= htmlspecialchars($art['title']) ?>" required>
                                </div>
                                <div>
                                    <label for="description-<?= $art['id'] ?>">Description</label>
                                    <textarea id="description-<?= $art['id'] ?>" name="description" required><?= htmlspecialchars($art['description']) ?></textarea>
                                </div>
                                <div>
                                    <label for="author-<?= $art['id'] ?>">Author</label>
                                    <input type="text" id="author-<?= $art['id'] ?>" name="author" value="<?= htmlspecialchars($art['author']) ?>" required>
                                </div>
                                <div class="flex space-x-4">
                                    <button type="submit">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?action=delete&id=<?= $art['id'] ?>" onclick="return confirm('Are you sure you want to delete?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center">
                <p class="text-xl">Access Denied</p>
                <a href="?action=list" class="hover:underline">Go to Home</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-800 py-4 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>© 2025 Pop Culture. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const themeSwitcher = document.querySelector('.theme-switcher');
        const themeOptions = document.querySelectorAll('.theme-option');

        themeSwitcher.addEventListener('click', () => {
            const options = themeSwitcher.querySelector('.theme-options');
            options.style.display = options.style.display === 'block' ? 'none' : 'block';
        });

        themeOptions.forEach(option => {
            option.addEventListener('click', () => {
                const theme = option.getAttribute('data-theme');
                document.body.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
            });
        });

        // Apply saved theme or blue by default
        const savedTheme = localStorage.getItem('theme') || 'blue';
        document.body.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>