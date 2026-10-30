<?php
// app/includes/header.php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? '2Tattoo';

if (!function_exists('assetPath')) {
    function assetPath(string $path): string
    {
        $archivo = __DIR__ . '/../..' . $path;
        $version = is_file($archivo) ? (string) filemtime($archivo) : '1';

        return appPath($path) . '?v=' . $version;
    }
}

$usuario = getCurrentUser();
$estaLogueado = isLoggedIn();
$rolUsuario = is_array($usuario) ? (string) ($usuario['rol'] ?? '') : '';
$nombreUsuario = is_array($usuario) ? (string) ($usuario['nombre'] ?? '') : '';

if (!function_exists('paginaActual')) {
    function paginaActual(): string
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $ruta = parse_url($requestUri, PHP_URL_PATH);

        return is_string($ruta) ? basename($ruta) : '';
    }
}

if (!function_exists('enlaceActivo')) {
    function enlaceActivo(string $archivo): bool
    {
        return paginaActual() === $archivo;
    }
}

if (!function_exists('claseEnlace')) {
    function claseEnlace(string $archivo, string $extra = ''): string
    {
        $clases = [];

        if ($extra !== '') {
            $clases[] = $extra;
        }

        if (enlaceActivo($archivo)) {
            $clases[] = 'active';
        }

        return implode(' ', $clases);
    }
}

if (!function_exists('ariaCurrent')) {
    function ariaCurrent(string $archivo): string
    {
        return enlaceActivo($archivo) ? 'page' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e((string) $pageTitle); ?> |  2Tattoo</title>
    <link rel="stylesheet" href="<?php echo e(assetPath('/public/assets/css/style.css')); ?>"></head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="site-branding">
                <a
                    href="<?php echo e(appPath('/public/index.php')); ?>"
                    class="logo <?php echo e(claseEnlace('index.php')); ?>"
                    <?php if (ariaCurrent('index.php') !== ''): ?>aria-current="<?php echo e(ariaCurrent('index.php')); ?>"<?php endif; ?>
                >
                     2Tattoo 
                </a>
            </div>

            <nav class="main-nav" aria-label="Navegación principal">
                <ul class="nav-list">
                    <li>
                        <a
                            href="<?php echo e(appPath('/public/index.php')); ?>"
                            class="<?php echo e(claseEnlace('index.php')); ?>"
                            <?php if (ariaCurrent('index.php') !== ''): ?>aria-current="<?php echo e(ariaCurrent('index.php')); ?>"<?php endif; ?>
                        >
                            Inicio
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?php echo e(appPath('/public/portfolio.php')); ?>"
                            class="<?php echo e(claseEnlace('portfolio.php')); ?>"
                            <?php if (ariaCurrent('portfolio.php') !== ''): ?>aria-current="<?php echo e(ariaCurrent('portfolio.php')); ?>"<?php endif; ?>
                        >
                            Portfolio
                        </a>
                    </li>

                    <?php if (!$estaLogueado): ?>
                        <li>
                            <a
                                href="<?php echo e(appPath('/public/registro.php')); ?>"
                                class="<?php echo e(claseEnlace('registro.php', 'nav-highlight')); ?>"
                                <?php if (ariaCurrent('registro.php') !== ''): ?>aria-current="<?php echo e(ariaCurrent('registro.php')); ?>"<?php endif; ?>
                            >
                                Crear cuenta
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/public/login.php')); ?>"
                                class="<?php echo e(claseEnlace('login.php')); ?>"
                                <?php if (ariaCurrent('login.php') !== ''): ?>aria-current="<?php echo e(ariaCurrent('login.php')); ?>"<?php endif; ?>
                            >
                                Iniciar sesión
                            </a>
                        </li>

                    <?php elseif ($rolUsuario === 'admin'): ?>
                        <li>
                            <a
                                href="<?php echo e(appPath('/admin/dashboard.php')); ?>"
                                class="<?php echo e(claseEnlace('dashboard.php', 'nav-highlight')); ?>"
                            >
                                Panel admin
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/admin/citas.php')); ?>"
                                class="<?php echo e(claseEnlace('citas.php')); ?>"
                            >
                                Citas
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/admin/tatuajes.php')); ?>"
                                class="<?php echo e(claseEnlace('tatuajes.php')); ?>"
                            >
                                Tatuajes
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/admin/disponibilidad.php')); ?>"
                                class="<?php echo e(claseEnlace('disponibilidad.php')); ?>"
                            >
                                Disponibilidad
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/public/logout.php')); ?>"
                                class="logout-link"
                            >
                                Cerrar sesión
                            </a>
                        </li>

                    <?php else: ?>
                        <li>
                            <a
                                href="<?php echo e(appPath('/public/solicitar-cita.php')); ?>"
                                class="<?php echo e(claseEnlace('solicitar-cita.php', 'nav-highlight')); ?>"
                            >
                                Solicitar cita
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/public/mis-citas.php')); ?>"
                                class="<?php echo e(claseEnlace('mis-citas.php')); ?>"
                            >
                                Mis citas
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?php echo e(appPath('/public/logout.php')); ?>"
                                class="logout-link"
                            >
                                Cerrar sesión
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <?php if ($estaLogueado && $nombreUsuario !== ''): ?>
            <div class="header-user">
                <p>
                    Sesión iniciada como
                    <strong><?php echo e($nombreUsuario); ?></strong>
                    <?php if ($rolUsuario !== ''): ?>
                        <span><?php echo e($rolUsuario); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </header>