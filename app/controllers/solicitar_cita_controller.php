<?php
// app/controllers/solicitar_cita_controller.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$usuario = getCurrentUser();

if (($usuario['rol'] ?? '') === 'admin') {
    appRedirect('/admin/dashboard.php');
}

$usuarioId = (int) ($usuario['id_usuario'] ?? 0);

$errores = [];
$mensajeExito = takeFlash('flash_success');
$descripcion = '';
$idDisponibilidadSeleccionada = '';
$franjasDisponibles = [];

if ($usuarioId <= 0) {
    $errores[] = 'No se ha podido identificar al usuario de la sesión.';
}

function obtenerFranjasDisponibles(PDO $pdo): array
{
    $sql = '
        SELECT id_disponibilidad, fecha, hora_inicio, hora_fin
        FROM disponibilidad
        WHERE disponible = 1
        ORDER BY fecha ASC, hora_inicio ASC
    ';

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errores)) {
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
    $idDisponibilidadSeleccionada = (string) ($_POST['id_disponibilidad'] ?? '');

    $idDisponibilidad = filter_var(
        $idDisponibilidadSeleccionada,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$idDisponibilidad) {
        $errores[] = 'Debes seleccionar una franja válida.';
    }

    if ($descripcion === '') {
        $errores[] = 'La descripción no puede estar vacía.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $sqlFranja = '
                SELECT id_disponibilidad, fecha, hora_inicio, hora_fin, disponible
                FROM disponibilidad
                WHERE id_disponibilidad = :id_disponibilidad
                FOR UPDATE
            ';

            $stmtFranja = $pdo->prepare($sqlFranja);
            $stmtFranja->execute([
                ':id_disponibilidad' => $idDisponibilidad,
            ]);

            $franja = $stmtFranja->fetch(PDO::FETCH_ASSOC);

            if (!$franja) {
                $pdo->rollBack();
                $errores[] = 'La franja seleccionada no existe.';
            } elseif ((int) $franja['disponible'] !== 1) {
                $pdo->rollBack();
                $errores[] = 'La franja ya no está disponible. Selecciona otra.';
            } else {
                $sqlInsert = '
                    INSERT INTO citas (
                        fecha,
                        hora,
                        descripcion,
                        estado,
                        id_usuario,
                        id_disponibilidad
                    ) VALUES (
                        :fecha,
                        :hora,
                        :descripcion,
                        :estado,
                        :id_usuario,
                        :id_disponibilidad
                    )
                ';

                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':fecha' => $franja['fecha'],
                    ':hora' => $franja['hora_inicio'],
                    ':descripcion' => $descripcion,
                    ':estado' => 'pendiente',
                    ':id_usuario' => $usuarioId,
                    ':id_disponibilidad' => (int) $franja['id_disponibilidad'],
                ]);

                $sqlUpdate = '
                    UPDATE disponibilidad
                    SET disponible = 0
                    WHERE id_disponibilidad = :id_disponibilidad
                    AND disponible = 1
                ';

                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':id_disponibilidad' => (int) $franja['id_disponibilidad'],
                ]);

                if ($stmtUpdate->rowCount() !== 1) {
                    $pdo->rollBack();
                    $errores[] = 'No se ha podido bloquear la franja. Inténtalo de nuevo.';
                } else {
                    $pdo->commit();

                    setFlash(
                        'flash_success',
                        'La cita se ha solicitado correctamente y queda pendiente de revisión.'
                    );

                    appRedirect('/public/solicitar-cita.php');
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errores[] = 'Se ha producido un error al crear la cita.';
        }
    }
}

$franjasDisponibles = obtenerFranjasDisponibles($pdo);