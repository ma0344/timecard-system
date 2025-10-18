<?php
// db_config.php
// 必要に応じて値を書き換えてください
// $host = 'localhost';
// $port = '3307';
// $db = '49u8_full_time_time_card';
// $user = 'ma';
// $pass = '1qaz2wsx#EDC$RFV';
// $charset = 'utf8';
// $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
// define('DB_DSN', 'mysql:host=localhost;port=3307;dbname=49u8_full_time_time_card;charset=utf8mb4');
// define('DB_USER', $user);
// define('DB_PASS', $pass);


// $host = '192.168.10.10';
// $port = '3307';
// $db = '49u8_full_time_time_card';
// $user = 'ma';
// $pass = '1qaz2wsx#EDC$RFV';
// $charset = 'utf8';
$host = 'mysql8008.in.shared-server.net';
$port = '13654';
$db = '49u8_full_time_time_card';
$user = 'ZwKL084';
$pass = '1qaz2WSX';
$charset = 'utf8';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('データベース接続に失敗しました: ' . $e->getMessage());
}
