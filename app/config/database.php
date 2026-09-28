<?php
// app/config/database.php

declare(strict_types=1);

$host = '127.0.0.1';
$port = '3308';
$dbname = 'tfg_tattoo';
$username = 'tfg_user';
$password = '1234';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    exit('Error de conexión a la base de datos: ' . $exception->getMessage());
}