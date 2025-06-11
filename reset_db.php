<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);

    $sql = file_get_contents('database.sql');
    if ($sql === false) {
        throw new Exception("Не удалось прочитать database.sql");
    }
    $pdo->exec($sql);

    echo "База данных успешно создана и заполнена. <a href='index.php'>Перейти на сайт</a>";
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}
?>