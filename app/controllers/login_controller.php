<?php
// app/controllers/login_controller.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (function_exists('requireGuest')) {
    requireGuest();
}

$errores = [];
$mensaje = takeFlash('flash_error');
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '') {
        $errores[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if ($password === '') {
        $errores[] = 'La contraseña es obligatoria.';
    }

    if (empty($errores)) {
        try {
            $sql = '
                SELECT id_usuario, nombre, email, password, rol
                FROM usuarios
                WHERE email = :email
                LIMIT 1
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email,
            ]);

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario || !password_verify($password, (string) $usuario['password'])) {
                $errores[] = 'Email o contraseña incorrectos.';
            } else {
                session_regenerate_id(true);

                $_SESSION['usuario'] = [
                    'id_usuario' => (int) $usuario['id_usuario'],
                    'nombre' => (string) $usuario['nombre'],
                    'email' => (string) $usuario['email'],
                    'rol' => (string) $usuario['rol'],
                ];

                if ((string) $usuario['rol'] === 'admin') {
                    appRedirect('/admin/dashboard.php');
                }

                appRedirect('/public/index.php');
            }
        } catch (PDOException $e) {
            $errores[] = 'Ha ocurrido un error al iniciar sesión.';
        }
    }
}