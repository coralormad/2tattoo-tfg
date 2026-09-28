<?php
// app/controllers/registro_controller.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$errores = $errores ?? [];
$mensajeExito = $mensajeExito ?? null;
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    if ($nombre === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    }

    if ($email === '') {
        $errores[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if ($password === '') {
        $errores[] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 4) {
        $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
    }

    if ($confirmarPassword === '') {
        $errores[] = 'Debes confirmar la contraseña.';
    } elseif ($password !== $confirmarPassword) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        try {
            $sql = "SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email
            ]);

            if ($stmt->fetch()) {
                $errores[] = 'Ya existe una cuenta registrada con ese correo electrónico.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios (nombre, email, password, rol)
                        VALUES (:nombre, :email, :password, 'cliente')";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':email' => $email,
                    ':password' => $passwordHash
                ]);

                $mensajeExito = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
                $nombre = '';
                $email = '';
            }
        } catch (PDOException $e) {
            $errores[] = 'Ha ocurrido un error al crear la cuenta.';
        }
    }
}