<?php
/*
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

// public/mis-citas.php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$usuario = getCurrentUser();

if (!is_array($usuario)) {
    appRedirect('/public/login.php');
}

$usuarioId = (int) ($usuario['id_usuario'] ?? $usuario['id'] ?? 0);
$usuarioRol = (string) ($usuario['rol'] ?? '');

if ($usuarioId <= 0) {
    appRedirect('/public/login.php');
}

if ($usuarioRol === 'admin') {
    appRedirect('/admin/dashboard.php');
}

$pageTitle = 'Mis citas';

$errores = [];

$mensajeExito = function_exists('takeFlash')
    ? takeFlash('flash_success')
    : ($_SESSION['flash_success'] ?? null);

if (!function_exists('takeFlash') && isset($_SESSION['flash_success'])) {
    unset($_SESSION['flash_success']);
}

$mensajeError = function_exists('takeFlash')
    ? takeFlash('flash_error')
    : ($_SESSION['flash_error'] ?? null);

if (!function_exists('takeFlash') && isset($_SESSION['flash_error'])) {
    unset($_SESSION['flash_error']);
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function formatearFecha(string $fecha): string
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1) {
        return substr($fecha, 8, 2) . '/' . substr($fecha, 5, 2) . '/' . substr($fecha, 0, 4);
    }

    return $fecha;
}

function formatearHora(string $hora): string
{
    return substr($hora, 0, 5);
}

function formatearFechaHora(?string $fechaHora): string
{
    if ($fechaHora === null || trim($fechaHora) === '') {
        return '-';
    }

    $timestamp = strtotime($fechaHora);

    if ($timestamp === false) {
        return $fechaHora;
    }

    return date('d/m/Y H:i', $timestamp);
}

function badgeEstadoClass(string $estado): string
{
    return match ($estado) {
        'aceptada' => 'badge badge-aceptada',
        'rechazada' => 'badge badge-rechazada',
        'cancelada' => 'badge badge-cancelada',
        default => 'badge badge-pendiente',
    };
}

function textoEstado(string $estado): string
{
    return match ($estado) {
        'aceptada' => 'Aceptada',
        'rechazada' => 'Rechazada',
        'cancelada' => 'Cancelada',
        default => 'Pendiente',
    };
}

$estadosValidos = ['pendiente', 'aceptada', 'rechazada', 'cancelada'];
$filtroEstado = trim((string) ($_GET['estado'] ?? ''));

if ($filtroEstado !== '' && !in_array($filtroEstado, $estadosValidos, true)) {
    $filtroEstado = '';
}

$citas = [];

try {
    $sql = '
        SELECT
            c.id_cita,
            c.fecha,
            c.hora,
            c.descripcion,
            c.estado,
            c.id_disponibilidad,
            c.fecha_solicitud,
            d.hora_fin
        FROM citas c
        LEFT JOIN disponibilidad d ON c.id_disponibilidad = d.id_disponibilidad
        WHERE c.id_usuario = :id_usuario
    ';

    $params = [
        ':id_usuario' => $usuarioId,
    ];

    if ($filtroEstado !== '') {
        $sql .= ' AND c.estado = :estado';
        $params[':estado'] = $filtroEstado;
    }

    $sql .= '
        ORDER BY
            FIELD(c.estado, "pendiente", "aceptada", "rechazada", "cancelada"),
            c.fecha DESC,
            c.hora DESC,
            c.fecha_solicitud DESC,
            c.id_cita DESC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $errores[] = 'No se pudieron cargar tus citas.';
}

$contadorEstados = [
    'pendiente' => 0,
    'aceptada' => 0,
    'rechazada' => 0,
    'cancelada' => 0,
];

foreach ($citas as $citaContador) {
    $estadoContador = (string) ($citaContador['estado'] ?? 'pendiente');

    if (array_key_exists($estadoContador, $contadorEstados)) {
        $contadorEstados[$estadoContador]++;
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<main class="mis-citas-page">
    <section class="page-intro center">
        <span class="section-kicker">Zona cliente</span>
        <h2>Mis citas</h2>
        <p>Aquí puedes consultar el estado de tus solicitudes y revisar los detalles de cada reserva.</p>
        <p>
            Sesión iniciada como:
            <strong><?php echo e((string) ($usuario['nombre'] ?? 'Cliente')); ?></strong>
        </p>
    </section>

    <section class="mis-citas-layout">
        <?php if ($mensajeExito !== null): ?>
            <div class="form-card client-message-card">
                <div class="success">
                    <p><?php echo e((string) $mensajeExito); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError !== null): ?>
            <div class="form-card client-message-card">
                <div class="error">
                    <p><?php echo e((string) $mensajeError); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="form-card client-message-card">
                <div class="error">
                    <p><strong>Se han encontrado errores:</strong></p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo e((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <section class="mis-citas-workspace">
            <aside class="form-card mis-citas-filter-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Filtrar mis citas</h3>
                        <p>Consulta tus solicitudes según su estado actual.</p>
                    </div>
                </div>

                <form action="" method="get">
                    <div>
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado">
                            <option value="">Todas</option>
                            <option value="pendiente" <?php echo $filtroEstado === 'pendiente' ? 'selected' : ''; ?>>
                                Pendiente
                            </option>
                            <option value="aceptada" <?php echo $filtroEstado === 'aceptada' ? 'selected' : ''; ?>>
                                Aceptada
                            </option>
                            <option value="rechazada" <?php echo $filtroEstado === 'rechazada' ? 'selected' : ''; ?>>
                                Rechazada
                            </option>
                            <option value="cancelada" <?php echo $filtroEstado === 'cancelada' ? 'selected' : ''; ?>>
                                Cancelada
                            </option>
                        </select>
                    </div>

                    <div class="mt-3 actions">
                        <button type="submit">Filtrar</button>
                        <a class="btn btn-secondary" href="mis-citas.php">Limpiar filtro</a>
                    </div>
                </form>

                <div class="mis-citas-summary">
                    <span>Resumen actual</span>

                    <div class="mis-citas-summary-list">
                        <p>
                            <strong><?php echo (int) $contadorEstados['pendiente']; ?></strong>
                            Pendientes
                        </p>

                        <p>
                            <strong><?php echo (int) $contadorEstados['aceptada']; ?></strong>
                            Aceptadas
                        </p>

                        <p>
                            <strong><?php echo (int) $contadorEstados['rechazada']; ?></strong>
                            Rechazadas
                        </p>

                        <p>
                            <strong><?php echo (int) $contadorEstados['cancelada']; ?></strong>
                            Canceladas
                        </p>
                    </div>
                </div>

                <div class="mt-3 actions">
                    <a class="btn" href="solicitar-cita.php">Solicitar nueva cita</a>
                </div>
            </aside>

            <section class="form-card table-card mis-citas-table-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Listado de mis citas</h3>
                        <p>Consulta la fecha, el horario, la descripción y el estado actual de cada solicitud.</p>
                    </div>
                </div>

                <div class="table-wrapper mis-citas-table-wrapper">
                    <table class="tabla-citas tabla-mis-citas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Solicitud</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($citas)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        Todavía no tienes citas registradas con este filtro.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas as $cita): ?>
                                    <?php $estado = (string) ($cita['estado'] ?? 'pendiente'); ?>

                                    <tr>
                                        <td class="cita-id">
                                            #<?php echo (int) $cita['id_cita']; ?>
                                        </td>

                                        <td>
                                            <?php echo e(formatearFecha((string) $cita['fecha'])); ?>
                                        </td>

                                        <td>
                                            <?php echo e(formatearHora((string) $cita['hora'])); ?>

                                            <?php if (!empty($cita['hora_fin'])): ?>
                                                - <?php echo e(formatearHora((string) $cita['hora_fin'])); ?>
                                            <?php endif; ?>
                                        </td>

                                        <td class="descripcion-cita">
                                            <?php echo nl2br(e((string) $cita['descripcion'])); ?>
                                        </td>

                                        <td>
                                            <span class="<?php echo e(badgeEstadoClass($estado)); ?>">
                                                <?php echo e(textoEstado($estado)); ?>
                                            </span>
                                        </td>

                                        <td class="fecha-solicitud">
                                            <?php echo e(formatearFechaHora((string) ($cita['fecha_solicitud'] ?? ''))); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>