<?php
// app/controllers/registro_controller.php

require_once __DIR__ . '/../config/database.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if ($email === '') {
        $errores[] = 'El email es obligatorio.';
    }

    if ($password === '') {
        $errores[] = 'La contraseña es obligatoria.';
    }

    if (empty($errores)) {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, email, password, rol) 
                VALUES (:nombre, :email, :password, 'cliente')";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':password' => $passwordHash
        ]);

        header('Location: login.php');
        exit;
    }
}