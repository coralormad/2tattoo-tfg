<?php
/*
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

// admin/citas.php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/auth.php';

requireAdmin();

$pageTitle = 'Gestión de citas';

$usuario = getCurrentUser();
$usuarioNombre = 'Administrador';

if (is_array($usuario) && !empty($usuario['nombre'])) {
    $usuarioNombre = (string) $usuario['nombre'];
}

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

function badgeEstadoClass(string $estado): string
{
    return match ($estado) {
        'aceptada' => 'estado-badge estado-aceptada',
        'rechazada' => 'estado-badge estado-rechazada',
        'cancelada' => 'estado-badge estado-cancelada',
        default => 'estado-badge estado-pendiente',
    };
}

function textoEstadoCita(string $estado): string
{
    return match ($estado) {
        'aceptada' => 'Aceptada',
        'rechazada' => 'Rechazada',
        'cancelada' => 'Cancelada',
        default => 'Pendiente',
    };
}

function redirigirCitasAdmin(): void
{
    header('Location: citas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? ''));
    $idCita = filter_var(
        $_POST['id_cita'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($idCita === false || $idCita === null) {
        $_SESSION['flash_error'] = 'La cita seleccionada no es válida.';
        redirigirCitasAdmin();
    }

    if (!in_array($accion, ['aceptar', 'rechazar'], true)) {
        $_SESSION['flash_error'] = 'La acción solicitada no es válida.';
        redirigirCitasAdmin();
    }

    try {
        $pdo->beginTransaction();

        $sql = '
            SELECT id_cita, estado, id_disponibilidad
            FROM citas
            WHERE id_cita = :id_cita
            LIMIT 1
            FOR UPDATE
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_cita' => (int) $idCita,
        ]);

        $cita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cita) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'La cita no existe.';
            redirigirCitasAdmin();
        }

        if (($cita['estado'] ?? '') !== 'pendiente') {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Solo se pueden gestionar citas pendientes.';
            redirigirCitasAdmin();
        }

        $idDisponibilidad = (int) ($cita['id_disponibilidad'] ?? 0);

        if ($accion === 'aceptar') {
            if ($idDisponibilidad > 0) {
                $sql = '
                    SELECT id_disponibilidad, disponible
                    FROM disponibilidad
                    WHERE id_disponibilidad = :id_disponibilidad
                    LIMIT 1
                    FOR UPDATE
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_disponibilidad' => $idDisponibilidad,
                ]);

                $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$disponibilidad) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = 'La franja asociada a la cita no existe.';
                    redirigirCitasAdmin();
                }

                $sql = '
                    SELECT 1
                    FROM citas
                    WHERE id_disponibilidad = :id_disponibilidad
                      AND estado IN ("pendiente", "aceptada")
                      AND id_cita <> :id_cita
                    LIMIT 1
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_disponibilidad' => $idDisponibilidad,
                    ':id_cita' => (int) $idCita,
                ]);

                $hayConflicto = $stmt->fetchColumn() !== false;

                if ($hayConflicto) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = 'La franja ya tiene otra cita activa.';
                    redirigirCitasAdmin();
                }

                $sql = '
                    UPDATE disponibilidad
                    SET disponible = 0
                    WHERE id_disponibilidad = :id_disponibilidad
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_disponibilidad' => $idDisponibilidad,
                ]);
            }

            $sql = '
                UPDATE citas
                SET estado = "aceptada"
                WHERE id_cita = :id_cita
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_cita' => (int) $idCita,
            ]);

            $pdo->commit();

            $_SESSION['flash_success'] = 'La cita se ha aceptado correctamente.';
            redirigirCitasAdmin();
        }

        $sql = '
            UPDATE citas
            SET estado = "rechazada"
            WHERE id_cita = :id_cita
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_cita' => (int) $idCita,
        ]);

        if ($idDisponibilidad > 0) {
            $sql = '
                SELECT id_disponibilidad
                FROM disponibilidad
                WHERE id_disponibilidad = :id_disponibilidad
                LIMIT 1
                FOR UPDATE
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_disponibilidad' => $idDisponibilidad,
            ]);

            $sql = '
                SELECT 1
                FROM citas
                WHERE id_disponibilidad = :id_disponibilidad
                  AND estado IN ("pendiente", "aceptada")
                LIMIT 1
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_disponibilidad' => $idDisponibilidad,
            ]);

            $hayCitasActivas = $stmt->fetchColumn() !== false;

            if (!$hayCitasActivas) {
                $sql = '
                    UPDATE disponibilidad
                    SET disponible = 1
                    WHERE id_disponibilidad = :id_disponibilidad
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_disponibilidad' => $idDisponibilidad,
                ]);
            }
        }

        $pdo->commit();

        $_SESSION['flash_success'] = 'La cita se ha rechazado correctamente.';
        redirigirCitasAdmin();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['flash_error'] = 'No se pudo actualizar la cita.';
        redirigirCitasAdmin();
    }
}

$busqueda = trim((string) ($_GET['busqueda'] ?? ''));
$filtroEstado = trim((string) ($_GET['estado'] ?? ''));
$filtroFecha = trim((string) ($_GET['fecha'] ?? ''));

$estadosValidos = ['pendiente', 'aceptada', 'rechazada', 'cancelada'];

if ($filtroEstado !== '' && !in_array($filtroEstado, $estadosValidos, true)) {
    $filtroEstado = '';
}

if ($filtroFecha !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroFecha) !== 1) {
    $filtroFecha = '';
}

$estadisticas = [
    'pendiente' => 0,
    'aceptada' => 0,
    'rechazada' => 0,
    'cancelada' => 0,
];

try {
    $sql = '
        SELECT estado, COUNT(*) AS total
        FROM citas
        GROUP BY estado
    ';

    $stmt = $pdo->query($sql);
    $filasEstadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filasEstadisticas as $fila) {
        $estado = (string) ($fila['estado'] ?? '');
        $total = (int) ($fila['total'] ?? 0);

        if (array_key_exists($estado, $estadisticas)) {
            $estadisticas[$estado] = $total;
        }
    }
} catch (PDOException $exception) {
    $errores[] = 'No se pudieron cargar las estadísticas.';
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
            u.nombre AS cliente_nombre,
            u.email AS cliente_email,
            d.hora_fin
        FROM citas c
        INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
        LEFT JOIN disponibilidad d ON c.id_disponibilidad = d.id_disponibilidad
        WHERE 1 = 1
    ';

    $params = [];

    if ($busqueda !== '') {
    $busquedaLimpia = ltrim($busqueda, '#');
    $busquedaLike = '%' . $busquedaLimpia . '%';

    $sql .= '
        AND (
            CAST(c.id_cita AS CHAR) LIKE :busqueda_id
            OR u.nombre LIKE :busqueda_nombre
            OR u.email LIKE :busqueda_email
            OR c.fecha LIKE :busqueda_fecha
            OR c.hora LIKE :busqueda_hora
            OR c.descripcion LIKE :busqueda_descripcion
            OR c.estado LIKE :busqueda_estado
        )
    ';

    $params[':busqueda_id'] = $busquedaLike;
    $params[':busqueda_nombre'] = $busquedaLike;
    $params[':busqueda_email'] = $busquedaLike;
    $params[':busqueda_fecha'] = $busquedaLike;
    $params[':busqueda_hora'] = $busquedaLike;
    $params[':busqueda_descripcion'] = $busquedaLike;
    $params[':busqueda_estado'] = $busquedaLike;
}

    if ($filtroEstado !== '') {
        $sql .= ' AND c.estado = :estado';
        $params[':estado'] = $filtroEstado;
    }

    if ($filtroFecha !== '') {
        $sql .= ' AND c.fecha = :fecha';
        $params[':fecha'] = $filtroFecha;
    }

    $sql .= '
        ORDER BY
            FIELD(c.estado, "pendiente", "aceptada", "rechazada", "cancelada"),
            c.fecha ASC,
            c.hora ASC,
            c.id_cita ASC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $errores[] = 'No se pudieron cargar las citas.';
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<main class="admin-citas-page">
    <section class="page-intro center">
        <span class="section-kicker">Panel de administración</span>
        <h2>Gestión de citas</h2>
        <p>Administra las solicitudes de cita de los clientes y controla su estado desde un único panel.</p>
        <p>
            Sesión iniciada como:
            <strong><?php echo e($usuarioNombre); ?></strong>
        </p>
    </section>

    <section class="admin-citas-layout">
        <?php if ($mensajeExito !== null): ?>
            <div class="form-card admin-message-card">
                <div class="success">
                    <p><?php echo e((string) $mensajeExito); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError !== null): ?>
            <div class="form-card admin-message-card">
                <div class="error">
                    <p><?php echo e((string) $mensajeError); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="form-card admin-message-card">
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

        <section class="citas-stats-grid" aria-label="Resumen de estados de citas">
            <article class="citas-stat-card stat-pendiente">
                <div class="stat-icon" aria-hidden="true">⏳</div>
                <div>
                    <h3>Pendientes</h3>
                    <p><?php echo (int) $estadisticas['pendiente']; ?></p>
                </div>
            </article>

            <article class="citas-stat-card stat-aceptada">
                <div class="stat-icon" aria-hidden="true">✓</div>
                <div>
                    <h3>Aceptadas</h3>
                    <p><?php echo (int) $estadisticas['aceptada']; ?></p>
                </div>
            </article>

            <article class="citas-stat-card stat-rechazada">
                <div class="stat-icon" aria-hidden="true">✕</div>
                <div>
                    <h3>Rechazadas</h3>
                    <p><?php echo (int) $estadisticas['rechazada']; ?></p>
                </div>
            </article>

            <article class="citas-stat-card stat-cancelada">
                <div class="stat-icon" aria-hidden="true">○</div>
                <div>
                    <h3>Canceladas</h3>
                    <p><?php echo (int) $estadisticas['cancelada']; ?></p>
                </div>
            </article>
        </section>

        <section class="admin-citas-workspace">
            <section class="form-card admin-citas-filter-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Filtros</h3>
                        <p>Busca por nombre, email, ID, fecha, hora, descripción o estado.</p>
                    </div>
                </div>

                <form action="" method="get">
                    <div>
                        <label for="busqueda">Buscar cita</label>
                        <input
                            type="text"
                            name="busqueda"
                            id="busqueda"
                            placeholder="Ej. Coral, gmail, pendiente, 12:00, #4..."
                            value="<?php echo e($busqueda); ?>"
                        >
                    </div>

                    <div>
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado">
                            <option value="">Todos</option>
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

                    <div>
                        <label for="fecha">Fecha exacta</label>
                        <input
                            type="date"
                            name="fecha"
                            id="fecha"
                            value="<?php echo e($filtroFecha); ?>"
                        >
                    </div>

                    <div class="mt-3 actions">
                        <button type="submit">Filtrar</button>
                        <a class="btn btn-secondary" href="citas.php">Limpiar filtros</a>
                    </div>
                </form>
            </section>

            <section class="form-card table-card admin-citas-table-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Listado de citas</h3>
                        <p>
                            <?php if ($busqueda !== '' || $filtroEstado !== '' || $filtroFecha !== ''): ?>
                                Resultados filtrados según los criterios introducidos.
                            <?php else: ?>
                                Revisa cada solicitud, consulta los datos del cliente y gestiona las citas pendientes.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="table-wrapper admin-citas-table-wrapper">
                    <table class="tabla-citas tabla-admin-citas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($citas)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        No hay citas que coincidan con la búsqueda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas as $cita): ?>
                                    <?php $estado = (string) ($cita['estado'] ?? 'pendiente'); ?>

                                    <tr>
                                        <td class="cita-id">
                                            #<?php echo (int) $cita['id_cita']; ?>
                                        </td>

                                        <td class="cliente-cita">
                                            <strong><?php echo e((string) $cita['cliente_nombre']); ?></strong>
                                            <span><?php echo e((string) $cita['cliente_email']); ?></span>
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

                                        <td class="descripcion-admin-cita">
                                            <?php echo nl2br(e((string) $cita['descripcion'])); ?>
                                        </td>

                                        <td>
                                            <span class="<?php echo e(badgeEstadoClass($estado)); ?>">
                                                <?php echo e(textoEstadoCita($estado)); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($estado === 'pendiente'): ?>
                                                <div class="acciones-cita">
                                                    <form action="" method="post" class="accion-inline">
                                                        <input
                                                            type="hidden"
                                                            name="id_cita"
                                                            value="<?php echo (int) $cita['id_cita']; ?>"
                                                        >
                                                        <input type="hidden" name="accion" value="aceptar">
                                                        <button
                                                            type="submit"
                                                            class="btn-action btn-accept"
                                                            data-confirm="¿Seguro que quieres aceptar esta cita?"
                                                        >
                                                            Aceptar
                                                        </button>
                                                    </form>

                                                    <form action="" method="post" class="accion-inline">
                                                        <input
                                                            type="hidden"
                                                            name="id_cita"
                                                            value="<?php echo (int) $cita['id_cita']; ?>"
                                                        >
                                                        <input type="hidden" name="accion" value="rechazar">
                                                        <button
                                                            type="submit"
                                                            class="btn-action btn-reject"
                                                            data-confirm="¿Seguro que quieres rechazar esta cita?"
                                                        >
                                                            Rechazar
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="sin-acciones">Sin acciones</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        <section class="center">
            <a class="btn btn-secondary" href="dashboard.php">Volver al panel</a>
        </section>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>