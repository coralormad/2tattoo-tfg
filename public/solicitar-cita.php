<?php
// public/solicitar-cita.php

declare(strict_types=1);

$pageTitle = 'Solicitar cita';

require_once __DIR__ . '/../app/controllers/solicitar_cita_controller.php';
require_once __DIR__ . '/../app/includes/header.php';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function formatearFechaCita(?string $fecha): string
{
    if ($fecha === null || trim($fecha) === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1) {
        return substr($fecha, 8, 2) . '/' . substr($fecha, 5, 2) . '/' . substr($fecha, 0, 4);
    }

    return $fecha;
}

function formatearHoraCita(?string $hora): string
{
    if ($hora === null || trim($hora) === '') {
        return '';
    }

    return substr($hora, 0, 5);
}
?>

<main class="solicitar-cita-page">
    <section class="page-intro center">
        <span class="section-kicker">Reserva personalizada</span>
        <h2>Solicitar cita</h2>
        <p>Selecciona una franja disponible y describe la idea de tu tatuaje para que el estudio pueda revisar tu solicitud.</p>
    </section>

    <section class="solicitar-cita-layout">
        <div class="form-card solicitar-cita-card">
            <div class="section-heading-row">
                <div>
                    <h3>Nueva solicitud</h3>
                    <p>Elige un horario disponible y añade los detalles principales de tu idea.</p>
                </div>
            </div>

            <?php if (!empty($mensajeExito)): ?>
                <div class="success">
                    <p><?php echo e($mensajeExito); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="error">
                    <p><strong>Revisa la solicitud:</strong></p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo e((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($franjasDisponibles)): ?>
                <div class="info">
                    <p>No hay franjas disponibles en este momento. Vuelve a consultarlo más adelante.</p>
                </div>

                <div class="mt-3 actions">
                    <a class="btn btn-secondary" href="mis-citas.php">Ver mis citas</a>
                </div>
            <?php else: ?>
                <form action="" method="post" novalidate>
                    <div>
                        <label for="id_disponibilidad">Franja disponible</label>

                        <select name="id_disponibilidad" id="id_disponibilidad" required>
                            <option value="">Selecciona una franja</option>

                            <?php foreach ($franjasDisponibles as $franja): ?>
                                <?php
                                $franjaId = (int) ($franja['id_disponibilidad'] ?? 0);

                                $selected = ((string) $idDisponibilidadSeleccionada === (string) $franjaId)
                                    ? 'selected'
                                    : '';

                                $fecha = formatearFechaCita((string) ($franja['fecha'] ?? ''));
                                $horaInicio = formatearHoraCita((string) ($franja['hora_inicio'] ?? ''));
                                $horaFin = formatearHoraCita((string) ($franja['hora_fin'] ?? ''));

                                $textoFranja = sprintf(
                                    '%s | %s - %s',
                                    $fecha,
                                    $horaInicio,
                                    $horaFin
                                );
                                ?>

                                <option value="<?php echo $franjaId; ?>" <?php echo $selected; ?>>
                                    <?php echo e($textoFranja); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <p class="field-help">
                            Solo aparecen horarios marcados como disponibles por el administrador.
                        </p>
                    </div>

                    <div>
                        <label for="descripcion">Descripción o idea del tatuaje</label>

                        <textarea
                            name="descripcion"
                            id="descripcion"
                            rows="5"
                            maxlength="1000"
                            placeholder="Describe brevemente tu idea, zona del cuerpo, tamaño aproximado o estilo..."
                            required
                        ><?php echo e($descripcion ?? ''); ?></textarea>

                        <p class="field-help">
                            Cuanta más información añadas, más fácil será revisar tu solicitud.
                        </p>
                    </div>

                    <div class="mt-3 actions">
                        <button type="submit">Solicitar cita</button>
                        <a class="btn btn-secondary" href="mis-citas.php">Ver mis citas</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <aside class="card cita-info-card">
            <h3>Antes de solicitar</h3>
            <p>Tu cita quedará registrada como pendiente hasta que el administrador la revise.</p>

            <ul>
                <li>Selecciona una franja disponible.</li>
                <li>Describe tu idea con claridad.</li>
                <li>Consulta el estado desde “Mis citas”.</li>
            </ul>
        </aside>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>