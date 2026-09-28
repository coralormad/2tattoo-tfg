<?php
// public/login.php

declare(strict_types=1);

$pageTitle = 'Iniciar sesión';

require_once __DIR__ . '/../app/controllers/login_controller.php';

$errores = $errores ?? [];
$mensaje = $mensaje ?? null;
$email = $email ?? '';

require_once __DIR__ . '/../app/includes/header.php';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<main class="auth-page login-page">
    <section class="page-intro center">
        <span class="section-kicker">Acceso de usuario</span>
        <h2>Iniciar sesión</h2>
        <p>Accede a TwoTattoo para solicitar citas, consultar el estado de tus reservas y seguir tu proceso con el estudio.</p>
    </section>

    <section class="login-workspace">
        <section class="form-card auth-card login-card">
            <div class="section-heading-row">
                <div>
                    <h3>Accede a tu cuenta</h3>
                    <p>Introduce tus datos para continuar.</p>
                </div>
            </div>

            <?php if ($mensaje !== null): ?>
                <div class="warning">
                    <p><?php echo e((string) $mensaje); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="error">
                    <p><strong>No se pudo iniciar sesión:</strong></p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo e((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="post" novalidate>
                <div>
                    <label for="email">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Introduce tu correo"
                        value="<?php echo e($email); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div>
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Introduce tu contraseña"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="mt-3 actions">
                    <button type="submit">Entrar</button>
                </div>

                <p class="auth-link mt-3 mb-0">
                    ¿Aún no tienes cuenta?
                    <a href="registro.php">Regístrate aquí</a>
                </p>
            </form>
        </section>

        <aside class="auth-welcome-card">
            <span class="auth-welcome-kicker">2Tattoo</span>

            <h3>Tu espacio para gestionar citas de forma sencilla</h3>

            <p>
                Crea una cuenta para reservar una franja disponible, explicar tu idea y consultar el estado de tu solicitud desde un mismo lugar.
            </p>

            <div class="auth-benefits-grid">
                <article>
                    <span aria-hidden="true">✦</span>
                    <div>
                        <h4>Reserva organizada</h4>
                        <p>Elige una franja disponible y envía tu solicitud al estudio.</p>
                    </div>
                </article>

                <article>
                    <span aria-hidden="true">✓</span>
                    <div>
                        <h4>Estado actualizado</h4>
                        <p>Consulta si tu cita está pendiente, aceptada o rechazada.</p>
                    </div>
                </article>

                <article>
                    <span aria-hidden="true">◆</span>
                    <div>
                        <h4>Portfolio conectado</h4>
                        <p>Explora estilos y trabajos antes de enviar tu idea.</p>
                    </div>
                </article>
            </div>

            <div class="auth-cta-box">
                <p>¿Es tu primera vez en TwoTattoo?</p>
                <a class="btn" href="registro.php">Crear cuenta</a>
            </div>
        </aside>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>