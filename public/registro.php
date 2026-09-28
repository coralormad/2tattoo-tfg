<?php
// public/registro.php

declare(strict_types=1);

$pageTitle = 'Registro';

$errores = [];
$mensajeExito = null;
$nombre = '';
$email = '';

require_once __DIR__ . '/../app/controllers/registro_controller.php';
require_once __DIR__ . '/../app/includes/header.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<main>
    <section class="page-intro center">
        <h2>Crear cuenta</h2>
        <p>Regístrate para solicitar citas, consultar disponibilidad y seguir el estado de tus reservas.</p>
    </section>

    <section class="form-card">
        <?php if (!empty($errores)): ?>
            <div class="error">
                <p><strong>Revisa el formulario:</strong></p>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo e((string) $error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($mensajeExito !== null): ?>
            <div class="success">
                <p><?php echo e((string) $mensajeExito); ?></p>
            </div>
        <?php endif; ?>

        <form action="" method="post" novalidate>
            <div>
                <label for="nombre">Nombre completo</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    placeholder="Introduce tu nombre"
                    value="<?php echo e($nombre); ?>"
                    required
                >
            </div>

            <div>
                <label for="email">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Introduce tu correo"
                    value="<?php echo e($email); ?>"
                    required
                >
            </div>

            <div>
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Crea una contraseña"
                    required
                >
            </div>

            <div>
                <label for="confirmar_password">Confirmar contraseña</label>
                <input
                    type="password"
                    id="confirmar_password"
                    name="confirmar_password"
                    placeholder="Repite la contraseña"
                    required
                >
            </div>

            <div class="mt-3">
                <button type="submit">Registrarse</button>
            </div>

            <p class="mt-3 mb-0">
                ¿Ya tienes cuenta?
                <a href="login.php">Inicia sesión aquí</a>
            </p>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>