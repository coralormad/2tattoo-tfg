<?php
// public/index.php

declare(strict_types=1);

/*
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

$pageTitle = 'Inicio';

require_once __DIR__ . '/../app/includes/auth.php';

$usuario = getCurrentUser();
$nombre = is_array($usuario) ? (string) ($usuario['nombre'] ?? '') : '';
$rol = is_array($usuario) ? (string) ($usuario['rol'] ?? '') : '';

require_once __DIR__ . '/../app/includes/header.php';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<main class="home-page">
    <section class="home-hero">
        <div class="home-hero-content">
            <span class="hero-kicker">Estudio • Arte • Reserva online</span>

            <h2>2Tattoo</h2>

            <?php if ($usuario === null): ?>
                <p>
                    Una plataforma visual para descubrir trabajos de tatuaje, explorar estilos y solicitar una cita
                    de forma clara, sencilla y conectada con la disponibilidad real del estudio.
                </p>

                <div class="hero-actions">
                    <a class="btn" href="<?php echo e(appPath('/public/registro.php')); ?>">Crear cuenta</a>
                    <a class="btn btn-secondary" href="<?php echo e(appPath('/public/portfolio.php')); ?>">Ver portfolio</a>
                    <a class="btn btn-secondary" href="<?php echo e(appPath('/public/login.php')); ?>">Iniciar sesión</a>
                </div>
            <?php elseif ($rol === 'admin'): ?>
                <p>
                    Bienvenida de nuevo,
                    <strong><?php echo e($nombre !== '' ? $nombre : 'Administrador'); ?></strong>.
                    Accede al panel para gestionar citas, tatuajes y disponibilidad del estudio.
                </p>

                <div class="hero-actions">
                    <a class="btn" href="<?php echo e(appPath('/admin/dashboard.php')); ?>">Ir al panel admin</a>
                    <a class="btn btn-secondary" href="<?php echo e(appPath('/admin/citas.php')); ?>">Gestionar citas</a>
                    <a class="btn btn-secondary" href="<?php echo e(appPath('/public/portfolio.php')); ?>">Ver portfolio</a>
                </div>
            <?php else: ?>
                <p>
                    Bienvenida de nuevo,
                    <strong><?php echo e($nombre !== '' ? $nombre : 'Cliente'); ?></strong>.
                    Desde aquí puedes solicitar una cita, revisar tus reservas y explorar el portfolio del estudio.
                </p>

                <div class="hero-actions">
                    <a class="btn" href="<?php echo e(appPath('/public/solicitar-cita.php')); ?>">Solicitar cita</a>
                    <a class="btn btn-secondary" href="<?php echo e(appPath('/public/mis-citas.php')); ?>">Mis citas</a>
                    <a class="btn btn-secondary" href="<?php echo e(appPath('/public/portfolio.php')); ?>">Ver portfolio</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="home-hero-panel" aria-label="Resumen de funciones">
            <div class="home-mini-card">
                <span>01</span>
                <h3>Explora estilos</h3>
                <p>Consulta trabajos del portfolio filtrados por estilo.</p>
            </div>

            <div class="home-mini-card">
                <span>02</span>
                <h3>Elige una franja</h3>
                <p>Solicita una cita según la disponibilidad configurada.</p>
            </div>

            <div class="home-mini-card">
                <span>03</span>
                <h3>Sigue tu reserva</h3>
                <p>Comprueba el estado de tu solicitud desde tu cuenta.</p>
            </div>
        </div>
    </section>

    <section class="home-section-heading center">
        <span class="section-kicker">Qué ofrece la plataforma</span>
        <h2>Diseñada para conectar cliente y estudio</h2>
        <p>
            2Tattoo centraliza el portfolio, la solicitud de citas y la gestión interna del estudio
            en una aplicación web sencilla, visual y funcional.
        </p>
    </section>

    <section class="home-feature-grid">
        <article class="home-feature-card">
            <div class="home-feature-icon" aria-hidden="true">✒</div>
            <h3>Portfolio visual</h3>
            <p>
                Muestra trabajos de tatuaje con imagen, estilo y descripción, facilitando que el cliente
                encuentre referencias antes de solicitar una cita.
            </p>
            <a href="<?php echo e(appPath('/public/portfolio.php')); ?>">Explorar portfolio</a>
        </article>

        <article class="home-feature-card">
            <div class="home-feature-icon" aria-hidden="true">⏱</div>
            <h3>Reservas organizadas</h3>
            <p>
                Las citas se solicitan sobre franjas disponibles previamente creadas desde el panel
                de administración.
            </p>

            <?php if ($usuario === null): ?>
                <a href="<?php echo e(appPath('/public/registro.php')); ?>">Crear cuenta</a>
            <?php elseif ($rol !== 'admin'): ?>
                <a href="<?php echo e(appPath('/public/solicitar-cita.php')); ?>">Solicitar cita</a>
            <?php else: ?>
                <a href="<?php echo e(appPath('/admin/disponibilidad.php')); ?>">Gestionar disponibilidad</a>
            <?php endif; ?>
        </article>

        <article class="home-feature-card">
            <div class="home-feature-icon" aria-hidden="true">✓</div>
            <h3>Seguimiento claro</h3>
            <p>
                El cliente puede consultar si su cita está pendiente, aceptada, rechazada o cancelada
                desde su zona personal.
            </p>

            <?php if ($usuario === null): ?>
                <a href="<?php echo e(appPath('/public/login.php')); ?>">Iniciar sesión</a>
            <?php elseif ($rol !== 'admin'): ?>
                <a href="<?php echo e(appPath('/public/mis-citas.php')); ?>">Ver mis citas</a>
            <?php else: ?>
                <a href="<?php echo e(appPath('/admin/citas.php')); ?>">Gestionar citas</a>
            <?php endif; ?>
        </article>
    </section>

    <section class="home-process">
        <div class="home-process-text">
            <span class="section-kicker">Proceso de reserva</span>
            <h2>De la idea a la cita en pocos pasos</h2>
            <p>
                La página está pensada para que el cliente no tenga que depender de mensajes sueltos
                o conversaciones desordenadas. Todo queda registrado y conectado con el sistema de gestión.
            </p>
        </div>

        <div class="home-process-steps">
            <article>
                <span>1</span>
                <div>
                    <h3>Consulta el portfolio</h3>
                    <p>Revisa diseños y estilos para definir mejor tu idea.</p>
                </div>
            </article>

            <article>
                <span>2</span>
                <div>
                    <h3>Solicita una cita</h3>
                    <p>Selecciona una franja disponible y describe el tatuaje.</p>
                </div>
            </article>

            <article>
                <span>3</span>
                <div>
                    <h3>Espera confirmación</h3>
                    <p>El estudio revisa la solicitud y actualiza su estado.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="home-cta">
        <?php if ($usuario === null): ?>
            <div>
                <span class="section-kicker">Empieza ahora</span>
                <h2>Prepara tu próxima idea de tatuaje</h2>
                <p>
                    Crea una cuenta para solicitar citas, revisar el estado de tus reservas
                    y acceder a una experiencia más organizada.
                </p>
            </div>

            <div class="hero-actions">
                <a class="btn" href="<?php echo e(appPath('/public/registro.php')); ?>">Crear cuenta</a>
                <a class="btn btn-secondary" href="<?php echo e(appPath('/public/portfolio.php')); ?>">Ver portfolio</a>
            </div>
        <?php elseif ($rol === 'admin'): ?>
            <div>
                <span class="section-kicker">Administración</span>
                <h2>Continúa gestionando el estudio</h2>
                <p>
                    Revisa citas pendientes, actualiza el portfolio y organiza las franjas disponibles.
                </p>
            </div>

            <div class="hero-actions">
                <a class="btn" href="<?php echo e(appPath('/admin/dashboard.php')); ?>">Panel admin</a>
                <a class="btn btn-secondary" href="<?php echo e(appPath('/admin/tatuajes.php')); ?>">Gestionar tatuajes</a>
            </div>
        <?php else: ?>
            <div>
                <span class="section-kicker">Zona cliente</span>
                <h2>Gestiona tus reservas</h2>
                <p>
                    Solicita una nueva cita o revisa el estado de tus solicitudes anteriores.
                </p>
            </div>

            <div class="hero-actions">
                <a class="btn" href="<?php echo e(appPath('/public/solicitar-cita.php')); ?>">Nueva solicitud</a>
                <a class="btn btn-secondary" href="<?php echo e(appPath('/public/mis-citas.php')); ?>">Mis citas</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>