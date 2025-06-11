<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Параметры подключения к MariaDB
$host = 'localhost';
$username = 'root';
$password = ''; // Пустой пароль по умолчанию в XAMPP
$dbname = 'popculture_db';
$charset = 'utf8mb4';

try {
    // Подключаемся к серверу MariaDB без указания базы данных
    $pdo = new PDO(
        "mysql:host=$host;charset=$charset",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Проверяем права пользователя
    $privileges = $pdo->query("SELECT * FROM mysql.user WHERE User = '$username' AND Host = 'localhost'")->fetch(PDO::FETCH_ASSOC);
    if (!$privileges || $privileges['Drop_priv'] !== 'Y' || $privileges['Create_priv'] !== 'Y') {
        throw new Exception("Пользователь '$username'@'localhost' не имеет прав DROP или CREATE.");
    }

    // Пытаемся удалить базу данных
    $maxAttempts = 3;
    $attempt = 0;
    $databaseDropped = false;

    while ($attempt < $maxAttempts && !$databaseDropped) {
        try {
            $pdo->exec("DROP DATABASE IF EXISTS $dbname");
            echo "База данных '$dbname' успешно удалена (если существовала).<br>";
            $databaseDropped = true;
        } catch (PDOException $e) {
            $attempt++;
            echo "Попытка $attempt: Не удалось удалить базу данных: " . $e->getMessage() . "<br>";
            if ($attempt < $maxAttempts) {
                sleep(2); // Задержка 2 секунды перед повторной попыткой
            }
        }
    }

    if (!$databaseDropped) {
        throw new Exception("Не удалось удалить базу данных '$dbname' после $maxAttempts попыток.");
    }

    // Проверяем, что база данных действительно удалена
    $checkDb = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if ($checkDb->rowCount() > 0) {
        throw new Exception("База данных '$dbname' всё ещё существует после попытки удаления.");
    }

    // Создаём новую базу данных
    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
    echo "База данных '$dbname' успешно создана.<br>";

    // Переключаемся на новую базу
    $pdo->exec("USE $dbname");

    // Читаем и выполняем SQL-скрипт
    $sqlFile = 'database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Файл '$sqlFile' не найден.");
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Не удалось прочитать файл '$sqlFile'.");
    }

    // Выполняем SQL-скрипт
    $pdo->exec($sql);
    echo "SQL-скрипт успешно выполнен. Таблицы созданы, данные вставлены.<br>";

    echo "База данных готова! Можете проверить сайт: <a href='index.php'>Перейти на сайт</a>";

} catch (PDOException $e) {
    die("Ошибка PDO: " . $e->getMessage());
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>