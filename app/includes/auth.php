<?php
// app/includes/auth.php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAppBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptName === '') {
        return '';
    }

    $basePath = preg_replace('#/(public|admin)(/.*)?$#', '', $scriptName);

    if (!is_string($basePath) || $basePath === '/' || $basePath === '.') {
        return '';
    }

    return rtrim($basePath, '/');
}

function appPath(string $path = ''): string
{
    $basePath = getAppBasePath();

    if ($path === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    if (str_starts_with($path, '/')) {
        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            return $path;
        }

        return ($basePath !== '' ? $basePath : '') . $path;
    }

    return ($basePath !== '' ? $basePath : '') . '/' . ltrim($path, '/');
}

function appRedirect(string $path): void
{
    header('Location: ' . appPath($path));
    exit;
}

function getSessionUserId(): int
{
    $id = $_SESSION['usuario']['id_usuario']
        ?? $_SESSION['usuario']['id']
        ?? $_SESSION['id_usuario']
        ?? $_SESSION['usuario_id']
        ?? $_SESSION['id']
        ?? null;

    $idValidado = filter_var(
        $id,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    return $idValidado !== false ? (int) $idValidado : 0;
}

function getSessionUserRole(): string
{
    $rol = $_SESSION['usuario']['rol']
        ?? $_SESSION['rol']
        ?? $_SESSION['usuario_rol']
        ?? '';

    return is_string($rol) ? trim($rol) : '';
}

function getSessionUserName(): string
{
    $nombre = $_SESSION['usuario']['nombre']
        ?? $_SESSION['nombre']
        ?? $_SESSION['usuario_nombre']
        ?? '';

    return is_string($nombre) ? trim($nombre) : '';
}

function getSessionUserEmail(): string
{
    $email = $_SESSION['usuario']['email']
        ?? $_SESSION['email']
        ?? $_SESSION['usuario_email']
        ?? '';

    return is_string($email) ? trim($email) : '';
}

function isLoggedIn(): bool
{
    return getSessionUserId() > 0;
}

function getCurrentUser(): ?array
{
    $userId = getSessionUserId();

    if ($userId <= 0) {
        return null;
    }

    $usuario = $_SESSION['usuario'] ?? [];

    if (!is_array($usuario)) {
        $usuario = [];
    }

    $usuario['id_usuario'] = $userId;
    $usuario['id'] = $userId;
    $usuario['rol'] = getSessionUserRole();
    $usuario['nombre'] = getSessionUserName();
    $usuario['email'] = getSessionUserEmail();

    return $usuario;
}

function setCurrentUser(array $usuario): void
{
    $id = $usuario['id_usuario'] ?? $usuario['id'] ?? 0;
    $idValidado = filter_var(
        $id,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($idValidado === false) {
        throw new InvalidArgumentException('El usuario debe tener un id válido.');
    }

    $rol = isset($usuario['rol']) && is_string($usuario['rol']) ? trim($usuario['rol']) : '';
    $nombre = isset($usuario['nombre']) && is_string($usuario['nombre']) ? trim($usuario['nombre']) : '';
    $email = isset($usuario['email']) && is_string($usuario['email']) ? trim($usuario['email']) : '';

    session_regenerate_id(true);

    $_SESSION['usuario'] = $usuario;
    $_SESSION['usuario']['id_usuario'] = (int) $idValidado;
    $_SESSION['usuario']['id'] = (int) $idValidado;
    $_SESSION['usuario']['rol'] = $rol;
    $_SESSION['usuario']['nombre'] = $nombre;
    $_SESSION['usuario']['email'] = $email;

    $_SESSION['id_usuario'] = (int) $idValidado;
    $_SESSION['usuario_id'] = (int) $idValidado;
    $_SESSION['id'] = (int) $idValidado;
    $_SESSION['rol'] = $rol;
    $_SESSION['usuario_rol'] = $rol;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['email'] = $email;
    $_SESSION['usuario_email'] = $email;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        appRedirect('/public/login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();

    $usuario = getCurrentUser();
    $rol = is_array($usuario) ? (string) ($usuario['rol'] ?? '') : '';

    if ($rol !== 'admin') {
        appRedirect('/public/index.php');
    }
}

function requireGuest(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $usuario = getCurrentUser();
    $rol = is_array($usuario) ? (string) ($usuario['rol'] ?? '') : '';

    if ($rol === 'admin') {
        appRedirect('/admin/dashboard.php');
    }

    appRedirect('/public/index.php');
}

function setFlash(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function takeFlash(string $key): ?string
{
    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);

    return is_string($message) ? $message : null;
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}