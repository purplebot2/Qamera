<?php
// Copy this file to db.php and fill in your own details.
// db.php is listed in .gitignore, so your password never ends up on GitHub.
$host = "localhost";
$db   = "qamera";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage()); // details go to the server log, not to the visitor
    exit("Database connection failed.");
}
