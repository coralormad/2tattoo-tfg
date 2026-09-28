<?php
/*
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

// admin/disponibilidad.php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/auth.php';

requireAdmin();

$pageTitle = 'Gestión de disponibilidad';

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

function esFechaValida(string $fecha): bool
{
    $dateTime = DateTime::createFromFormat('Y-m-d', $fecha);

    return $dateTime !== false && $dateTime->format('Y-m-d') === $fecha;
}

function esHoraValida(string $hora): bool
{
    $dateTime = DateTime::createFromFormat('H:i', $hora);

    return $dateTime !== false && $dateTime->format('H:i') === $hora;
}

function redirigirDisponibilidad(): void
{
    if (function_exists('appRedirect')) {
        appRedirect('/admin/disponibilidad.php');
    }

    header('Location: disponibilidad.php');
    exit;
}

$fecha = '';
$horaInicio = '';
$horaFin = '';
$disponible = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? ''));

    if (!in_array($accion, ['crear', 'toggle_estado'], true)) {
        $errores[] = 'La acción solicitada no es válida.';
    }

    if ($accion === 'crear') {
        $fecha = trim((string) ($_POST['fecha'] ?? ''));
        $horaInicio = trim((string) ($_POST['hora_inicio'] ?? ''));
        $horaFin = trim((string) ($_POST['hora_fin'] ?? ''));
        $disponible = ($_POST['disponible'] ?? '1') === '0' ? '0' : '1';

        if ($fecha === '') {
            $errores[] = 'La fecha es obligatoria.';
        } elseif (!esFechaValida($fecha)) {
            $errores[] = 'La fecha no tiene un formato válido.';
        } elseif ($fecha < date('Y-m-d')) {
            $errores[] = 'No puedes crear franjas en fechas pasadas.';
        }

        if ($horaInicio === '') {
            $errores[] = 'La hora de inicio es obligatoria.';
        } elseif (!esHoraValida($horaInicio)) {
            $errores[] = 'La hora de inicio no tiene un formato válido.';
        }

        if ($horaFin === '') {
            $errores[] = 'La hora de fin es obligatoria.';
        } elseif (!esHoraValida($horaFin)) {
            $errores[] = 'La hora de fin no tiene un formato válido.';
        }

        if (
            $horaInicio !== ''
            && $horaFin !== ''
            && esHoraValida($horaInicio)
            && esHoraValida($horaFin)
            && $horaInicio >= $horaFin
        ) {
            $errores[] = 'La hora de fin debe ser posterior a la hora de inicio.';
        }

        if (empty($errores)) {
            try {
                $sql = '
                    SELECT 1
                    FROM disponibilidad
                    WHERE fecha = :fecha
                    AND (:hora_inicio < hora_fin AND :hora_fin > hora_inicio)
                    LIMIT 1
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':fecha' => $fecha,
                    ':hora_inicio' => $horaInicio,
                    ':hora_fin' => $horaFin,
                ]);

                $existeSolape = $stmt->fetchColumn() !== false;

                if ($existeSolape) {
                    $errores[] = 'Ya existe una franja que se solapa con ese horario.';
                } else {
                    $sql = '
                        INSERT INTO disponibilidad (
                            fecha,
                            hora_inicio,
                            hora_fin,
                            disponible
                        ) VALUES (
                            :fecha,
                            :hora_inicio,
                            :hora_fin,
                            :disponible
                        )
                    ';

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':fecha' => $fecha,
                        ':hora_inicio' => $horaInicio,
                        ':hora_fin' => $horaFin,
                        ':disponible' => (int) $disponible,
                    ]);

                    $_SESSION['flash_success'] = 'Franja creada correctamente.';
                    redirigirDisponibilidad();
                }
            } catch (PDOException $exception) {
                $errores[] = 'Ha ocurrido un error al guardar la franja.';
            }
        }
    }

    if ($accion === 'toggle_estado') {
        $idDisponibilidad = filter_var(
            $_POST['id_disponibilidad'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($idDisponibilidad === false || $idDisponibilidad === null) {
            $errores[] = 'La franja seleccionada no es válida.';
        } else {
            try {
                $sql = '
                    SELECT
                        d.id_disponibilidad,
                        d.disponible,
                        EXISTS(
                            SELECT 1
                            FROM citas c
                            WHERE c.id_disponibilidad = d.id_disponibilidad
                            AND c.estado IN ("pendiente", "aceptada")
                            LIMIT 1
                        ) AS tiene_cita_activa
                    FROM disponibilidad d
                    WHERE d.id_disponibilidad = :id_disponibilidad
                    LIMIT 1
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_disponibilidad' => $idDisponibilidad,
                ]);

                $franjaActual = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$franjaActual) {
                    $errores[] = 'La franja seleccionada no existe.';
                } else {
                    $estaDisponible = (int) $franjaActual['disponible'] === 1;
                    $tieneCitaActiva = (int) $franjaActual['tiene_cita_activa'] === 1;

                    if (!$estaDisponible && $tieneCitaActiva) {
                        $errores[] = 'La franja tiene una cita activa y no puede volver a marcarse como disponible.';
                    } else {
                        $nuevoEstado = $estaDisponible ? 0 : 1;

                        $sql = '
                            UPDATE disponibilidad
                            SET disponible = :disponible
                            WHERE id_disponibilidad = :id_disponibilidad
                        ';

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':disponible' => $nuevoEstado,
                            ':id_disponibilidad' => $idDisponibilidad,
                        ]);

                        $_SESSION['flash_success'] = 'Estado de la franja actualizado correctamente.';
                        redirigirDisponibilidad();
                    }
                }
            } catch (PDOException $exception) {
                $errores[] = 'Ha ocurrido un error al actualizar el estado de la franja.';
            }
        }
    }
}

$franjas = [];

try {
    $sql = '
        SELECT
            d.id_disponibilidad,
            d.fecha,
            d.hora_inicio,
            d.hora_fin,
            d.disponible,
            EXISTS(
                SELECT 1
                FROM citas c
                WHERE c.id_disponibilidad = d.id_disponibilidad
                AND c.estado IN ("pendiente", "aceptada")
                LIMIT 1
            ) AS tiene_cita_activa
        FROM disponibilidad d
        ORDER BY d.fecha ASC, d.hora_inicio ASC
    ';

    $stmt = $pdo->query($sql);
    $franjas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $errores[] = 'No se pudo cargar la disponibilidad.';
}

$totalFranjas = count($franjas);
$franjasDisponibles = 0;
$franjasNoDisponibles = 0;
$franjasConCitaActiva = 0;

foreach ($franjas as $franja) {
    if ((int) $franja['disponible'] === 1) {
        $franjasDisponibles++;
    } else {
        $franjasNoDisponibles++;
    }

    if ((int) ($franja['tiene_cita_activa'] ?? 0) === 1) {
        $franjasConCitaActiva++;
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<main class="admin-disponibilidad-page">
    <section class="page-intro center">
        <span class="section-kicker">Panel de administración</span>
        <h2>Gestión de disponibilidad</h2>
        <p>Configura las franjas horarias que estarán disponibles para reservar cita.</p>
        <p>
            Sesión iniciada como:
            <strong><?php echo e($usuarioNombre); ?></strong>
        </p>
    </section>

    <section class="admin-disponibilidad-layout">
        <?php if ($mensajeExito !== null): ?>
            <div class="form-card admin-message-card">
                <div class="success">
                    <p><?php echo e((string) $mensajeExito); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="form-card admin-message-card">
                <div class="error">
                    <p><strong>Revisa los datos:</strong></p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo e((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <section class="disponibilidad-stats-grid" aria-label="Resumen de disponibilidad">
            <article class="disponibilidad-stat-card stat-disponible">
                <div class="stat-icon" aria-hidden="true">✓</div>
                <div>
                    <h3>Disponibles</h3>
                    <p><?php echo (int) $franjasDisponibles; ?></p>
                </div>
            </article>

            <article class="disponibilidad-stat-card stat-no-disponible">
                <div class="stat-icon" aria-hidden="true">–</div>
                <div>
                    <h3>No disponibles</h3>
                    <p><?php echo (int) $franjasNoDisponibles; ?></p>
                </div>
            </article>

            <article class="disponibilidad-stat-card stat-cita-activa">
                <div class="stat-icon" aria-hidden="true">●</div>
                <div>
                    <h3>Con cita activa</h3>
                    <p><?php echo (int) $franjasConCitaActiva; ?></p>
                </div>
            </article>

            <article class="disponibilidad-stat-card stat-total">
                <div class="stat-icon" aria-hidden="true">#</div>
                <div>
                    <h3>Total registradas</h3>
                    <p><?php echo (int) $totalFranjas; ?></p>
                </div>
            </article>
        </section>

        <section class="admin-disponibilidad-workspace">
            <section class="form-card admin-disponibilidad-form-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Añadir nueva franja</h3>
                        <p>Define una fecha, horario y estado inicial para que pueda usarse en las reservas.</p>
                    </div>
                </div>

                <form action="" method="post" novalidate>
                    <input type="hidden" name="accion" value="crear">

                    <div>
                        <label for="fecha">Fecha</label>
                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            value="<?php echo e($fecha); ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="hora_inicio">Hora de inicio</label>
                        <input
                            type="time"
                            id="hora_inicio"
                            name="hora_inicio"
                            value="<?php echo e($horaInicio); ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="hora_fin">Hora de fin</label>
                        <input
                            type="time"
                            id="hora_fin"
                            name="hora_fin"
                            value="<?php echo e($horaFin); ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="disponible">Estado inicial</label>
                        <select id="disponible" name="disponible">
                            <option value="1" <?php echo $disponible === '1' ? 'selected' : ''; ?>>
                                Disponible
                            </option>
                            <option value="0" <?php echo $disponible === '0' ? 'selected' : ''; ?>>
                                No disponible
                            </option>
                        </select>
                        <p class="field-help">
                            Las franjas disponibles podrán ser seleccionadas por los clientes.
                        </p>
                    </div>

                    <div class="mt-3 actions">
                        <button type="submit">Guardar franja</button>
                    </div>
                </form>
            </section>

            <section class="form-card table-card admin-disponibilidad-table-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Listado de disponibilidad</h3>
                        <p>Consulta las franjas registradas y cambia su estado cuando sea necesario.</p>
                    </div>
                </div>

                <div class="table-wrapper admin-disponibilidad-table-wrapper">
                    <table class="tabla-disponibilidad tabla-admin-disponibilidad">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th>Cita activa</th>
                                <th>Acción</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($franjas)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        Todavía no hay franjas registradas.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($franjas as $franja): ?>
                                    <?php
                                    $idFranja = (int) $franja['id_disponibilidad'];
                                    $estaDisponible = (int) $franja['disponible'] === 1;
                                    $tieneCitaActiva = (int) ($franja['tiene_cita_activa'] ?? 0) === 1;
                                    ?>

                                    <tr>
                                        <td class="cita-id">
                                            #<?php echo $idFranja; ?>
                                        </td>

                                        <td>
                                            <?php echo e(formatearFecha((string) $franja['fecha'])); ?>
                                        </td>

                                        <td>
                                            <?php echo e(formatearHora((string) $franja['hora_inicio'])); ?>
                                        </td>

                                        <td>
                                            <?php echo e(formatearHora((string) $franja['hora_fin'])); ?>
                                        </td>

                                        <td>
                                            <?php if ($estaDisponible): ?>
                                                <span class="badge badge-disponible">Disponible</span>
                                            <?php else: ?>
                                                <span class="badge badge-no-disponible">No disponible</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($tieneCitaActiva): ?>
                                                <span class="badge badge-pendiente">Sí</span>
                                            <?php else: ?>
                                                <span class="badge badge-disponible">No</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <form action="" method="post" class="accion-inline">
                                                <input type="hidden" name="accion" value="toggle_estado">
                                                <input
                                                    type="hidden"
                                                    name="id_disponibilidad"
                                                    value="<?php echo $idFranja; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn-action <?php echo $estaDisponible ? 'btn-reject' : 'btn-accept'; ?>"
                                                    data-confirm="¿Seguro que quieres cambiar el estado de esta franja?"
                                                >
                                                    <?php echo $estaDisponible ? 'Bloquear' : 'Activar'; ?>
                                                </button>
                                            </form>
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