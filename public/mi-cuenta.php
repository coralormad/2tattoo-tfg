<?php
/*
=========================================================
ARCHIVO 1: public/mi-cuenta.php
=========================================================
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$usuario = getCurrentUser();

if (($usuario['rol'] ?? '') === 'admin') {
    appRedirect('/tfg-tattoo/admin/dashboard.php');
}

$pageTitle = 'Mi cuenta';

$errores = [];
$citas = [];

$estadisticas = [
    'total' => 0,
    'pendiente' => 0,
    'aceptada' => 0,
    'rechazada' => 0,
    'cancelada' => 0,
];

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatearFecha(string $fecha): string
{
    $timestamp = strtotime($fecha);
    return $timestamp ? date('d/m/Y', $timestamp) : $fecha;
}

function formatearHora(string $hora): string
{
    $timestamp = strtotime($hora);
    return $timestamp ? date('H:i', $timestamp) : $hora;
}

function badgeEstadoClass(string $estado): string
{
    return match ($estado) {
        'aceptada' => 'estado-badge estado-aceptada',
        'rechazada' => 'estado-badge estado-rechazada',
        'cancelada' => 'estado-badge estado-cancelada',
        default => 'estado-badge estado-pendiente',
    };
}

try {
    $sql = '
        SELECT
            c.id_cita,
            c.fecha,
            c.hora,
            c.descripcion,
            c.estado,
            c.fecha_solicitud,
            c.id_disponibilidad,
            d.hora_fin
        FROM citas c
        LEFT JOIN disponibilidad d ON c.id_disponibilidad = d.id_disponibilidad
        WHERE c.id_usuario = :id_usuario
        ORDER BY c.fecha DESC, c.hora DESC, c.id_cita DESC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_usuario' => (int) $usuario['id_usuario'],
    ]);

    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $estadisticas['total'] = count($citas);

    foreach ($citas as $cita) {
        $estado = $cita['estado'];

        if (array_key_exists($estado, $estadisticas)) {
            $estadisticas[$estado]++;
        }
    }
} catch (PDOException $e) {
    $errores[] = 'No se pudieron cargar tus citas.';
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<main>
    <section class="page-intro center">
        <h2>Mi cuenta</h2>
        <p>Bienvenido, <strong><?php echo e($usuario['nombre'] ?? 'Cliente'); ?></strong>.</p>
        <p>Aquí puedes consultar el estado de tus solicitudes de cita.</p>
    </section>

    <?php if (!empty($errores)): ?>
        <section class="form-card">
            <div class="error">
                <p><strong>Se ha producido un error:</strong></p>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endif; ?>

    <section class="dashboard-stats">
        <article class="stat-card">
            <h3>Total de citas</h3>
            <p><?php echo $estadisticas['total']; ?></p>
        </article>

        <article class="stat-card">
            <h3>Pendientes</h3>
            <p><?php echo $estadisticas['pendiente']; ?></p>
        </article>

        <article class="stat-card">
            <h3>Aceptadas</h3>
            <p><?php echo $estadisticas['aceptada']; ?></p>
        </article>

        <article class="stat-card">
            <h3>Rechazadas</h3>
            <p><?php echo $estadisticas['rechazada']; ?></p>
        </article>
    </section>

    <section class="form-card table-card">
        <h3>Mis citas</h3>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Solicitada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="6">Todavía no has solicitado ninguna cita.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas as $cita): ?>
                        <tr>
                            <td><?php echo (int) $cita['id_cita']; ?></td>
                            <td><?php echo e(formatearFecha($cita['fecha'])); ?></td>
                            <td>
                                <?php echo e(formatearHora($cita['hora'])); ?>
                                <?php if (!empty($cita['hora_fin'])): ?>
                                    - <?php echo e(formatearHora((string) $cita['hora_fin'])); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo nl2br(e($cita['descripcion'])); ?></td>
                            <td>
                                <span class="<?php echo e(badgeEstadoClass($cita['estado'])); ?>">
                                    <?php echo e(ucfirst($cita['estado'])); ?>
                                </span>
                            </td>
                            <td><?php echo e(formatearFecha(substr((string) $cita['fecha_solicitud'], 0, 10))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="center">
        <a class="button" href="solicitar-cita.php">Solicitar nueva cita</a>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>


<?php
/*
=========================================================
ARCHIVO 2: AJUSTE OPCIONAL EN app/includes/header.php
SOLO LA PARTE DEL NAV PARA CLIENTE
=========================================================
Pega esta lógica dentro del bloque donde ya gestionas
los enlaces según sesión y rol.
*/
?>

<?php if ($usuario === null): ?>
    <a class="nav-link <?php echo navActive('solicitar-cita.php', $currentFile); ?>" href="<?php echo $baseUrl; ?>/solicitar-cita.php">Solicitar cita</a>
    <a class="nav-link <?php echo navActive('login.php', $currentFile); ?>" href="<?php echo $baseUrl; ?>/login.php">Login</a>
    <a class="nav-link <?php echo navActive('registro.php', $currentFile); ?>" href="<?php echo $baseUrl; ?>/registro.php">Registro</a>
<?php else: ?>
    <?php if ($usuarioRol === 'admin'): ?>
        <a class="nav-link <?php echo navActiveIn(['dashboard.php', 'citas.php', 'tatuajes.php', 'disponibilidad.php'], $currentFile); ?>" href="<?php echo $adminUrl; ?>/dashboard.php">Panel admin</a>
    <?php else: ?>
        <a class="nav-link <?php echo navActive('solicitar-cita.php', $currentFile); ?>" href="<?php echo $baseUrl; ?>/solicitar-cita.php">Solicitar cita</a>
        <a class="nav-link <?php echo navActive('mi-cuenta.php', $currentFile); ?>" href="<?php echo $baseUrl; ?>/mi-cuenta.php">Mi cuenta</a>
    <?php endif; ?>

    <a class="nav-link" href="<?php echo $baseUrl; ?>/logout.php">Cerrar sesión</a>
<?php endif; ?>