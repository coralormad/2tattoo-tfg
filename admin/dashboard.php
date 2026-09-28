<?php
// admin/dashboard.php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireAdmin();

$usuario = getCurrentUser();
$nombreAdministrador = 'Administrador';

if (is_array($usuario) && !empty($usuario['nombre'])) {
    $nombreAdministrador = (string) $usuario['nombre'];
}

$pageTitle = 'Panel de administración';

require_once __DIR__ . '/../app/includes/header.php';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<main class="admin-dashboard-page">
    <section class="page-intro center dashboard-intro">
        <span class="section-kicker">Zona privada</span>
        <h2>Panel de administración</h2>

        <p>
            Bienvenida,
            <strong><?php echo e($nombreAdministrador); ?></strong>.
        </p>

        <p>
            Desde este panel puedes gestionar las citas de los clientes, actualizar el portfolio
            y organizar la disponibilidad del estudio.
        </p>
    </section>

    <section class="admin-dashboard-layout">
        <section class="dashboard-action-grid" aria-label="Accesos del panel de administración">
            <article class="dashboard-action-card">
                <div class="dashboard-action-icon" aria-hidden="true">📅</div>

                <div class="dashboard-action-content">
                    <h3>Gestión de citas</h3>
                    <p>
                        Revisa las solicitudes recibidas y actualiza su estado según la disponibilidad del estudio.
                    </p>

                    <a class="btn" href="citas.php">Gestionar citas</a>
                </div>
            </article>

            <article class="dashboard-action-card">
                <div class="dashboard-action-icon" aria-hidden="true">✒</div>

                <div class="dashboard-action-content">
                    <h3>Gestión de tatuajes</h3>
                    <p>
                        Añade, edita o elimina trabajos para mantener actualizado el portfolio público.
                    </p>

                    <a class="btn" href="tatuajes.php">Gestionar tatuajes</a>
                </div>
            </article>

            <article class="dashboard-action-card">
                <div class="dashboard-action-icon" aria-hidden="true">⏱</div>

                <div class="dashboard-action-content">
                    <h3>Disponibilidad</h3>
                    <p>
                        Crea franjas horarias y controla qué horarios pueden reservar los clientes.
                    </p>

                    <a class="btn" href="disponibilidad.php">Gestionar disponibilidad</a>
                </div>
            </article>
        </section>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>