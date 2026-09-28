<?php
/*
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

// admin/tatuajes.php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/auth.php';

requireAdmin();

$pageTitle = 'Gestión de tatuajes';
$usuario = getCurrentUser();

$uploadDir = __DIR__ . '/../public/assets/img/';
$imagePublicBase = appPath('/public/assets/img');

$errores = [];
$mensajeExito = takeFlash('flash_success');
$mensajeError = takeFlash('flash_error');

$form = [
    'id_tatuaje' => 0,
    'titulo' => '',
    'descripcion' => '',
    'id_estilo' => '',
    'imagen_actual' => '',
];

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function formatearFechaTatuaje(?string $fecha): string
{
    $fecha = trim((string) $fecha);

    if ($fecha === '') {
        return '-';
    }

    $timestamp = strtotime($fecha);

    return $timestamp ? date('d/m/Y H:i', $timestamp) : $fecha;
}

function obtenerExtensionDesdeMime(string $mimeType): ?string
{
    $mapa = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    return $mapa[$mimeType] ?? null;
}

function validarImagenSubida(array $archivo): array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, null];
    }

    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'No se pudo subir la imagen.'];
    }

    $tamanoMaximo = 5 * 1024 * 1024;

    if (($archivo['size'] ?? 0) > $tamanoMaximo) {
        return [false, 'La imagen no puede superar 5 MB.'];
    }

    $tmpName = (string) ($archivo['tmp_name'] ?? '');

    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [false, 'El archivo de imagen no es válido.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpName) : false;

    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mimeType === false) {
        return [false, 'No se pudo validar el tipo de imagen.'];
    }

    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return [false, 'Solo se permiten imágenes JPG, PNG o WEBP.'];
    }

    return [true, null];
}

function guardarImagenSubida(array $archivo, string $uploadDir): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    }

    $tmpName = (string) ($archivo['tmp_name'] ?? '');

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpName) : false;

    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mimeType === false) {
        throw new RuntimeException('No se pudo detectar el tipo de la imagen.');
    }

    $extension = obtenerExtensionDesdeMime((string) $mimeType);

    if ($extension === null) {
        throw new RuntimeException('La extensión de la imagen no es válida.');
    }

    $nombreArchivo = 'tattoo_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $rutaDestino = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nombreArchivo;

    if (!move_uploaded_file($tmpName, $rutaDestino)) {
        throw new RuntimeException('No se pudo guardar la imagen subida.');
    }

    return $nombreArchivo;
}

function borrarImagenLocal(?string $nombreArchivo, string $uploadDir): void
{
    $nombreArchivo = trim((string) $nombreArchivo);

    if ($nombreArchivo === '') {
        return;
    }

    $nombreSeguro = basename($nombreArchivo);
    $ruta = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nombreSeguro;

    if (is_file($ruta)) {
        unlink($ruta);
    }
}

function obtenerTatuajePorId(PDO $pdo, int $idTatuaje): ?array
{
    $sql = '
        SELECT id_tatuaje, titulo, descripcion, imagen, id_estilo, fecha_creacion
        FROM tatuajes
        WHERE id_tatuaje = :id_tatuaje
        LIMIT 1
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_tatuaje' => $idTatuaje,
    ]);

    $tatuaje = $stmt->fetch(PDO::FETCH_ASSOC);

    return $tatuaje ?: null;
}

$estilos = [];

try {
    $sql = '
        SELECT id_estilo, nombre_estilo
        FROM estilos
        ORDER BY nombre_estilo ASC
    ';

    $stmt = $pdo->query($sql);
    $estilos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = 'No se pudieron cargar los estilos.';
}

$idsEstilosValidos = array_map(
    static fn(array $estilo): int => (int) $estilo['id_estilo'],
    $estilos
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? ''));

    if ($accion === 'crear' || $accion === 'actualizar') {
        $idTatuaje = (int) ($_POST['id_tatuaje'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $idEstilo = trim((string) ($_POST['id_estilo'] ?? ''));

        $form['id_tatuaje'] = $idTatuaje;
        $form['titulo'] = $titulo;
        $form['descripcion'] = $descripcion;
        $form['id_estilo'] = $idEstilo;

        $tatuajeActual = null;

        if ($accion === 'actualizar') {
            if ($idTatuaje <= 0) {
                $errores[] = 'El tatuaje a editar no es válido.';
            } else {
                $tatuajeActual = obtenerTatuajePorId($pdo, $idTatuaje);

                if ($tatuajeActual === null) {
                    $errores[] = 'El tatuaje que intentas editar no existe.';
                } else {
                    $form['imagen_actual'] = (string) ($tatuajeActual['imagen'] ?? '');
                }
            }
        }

        if ($titulo === '') {
            $errores[] = 'El título es obligatorio.';
        }

        if ($idEstilo !== '' && !in_array((int) $idEstilo, $idsEstilosValidos, true)) {
            $errores[] = 'El estilo seleccionado no es válido.';
        }

        [$imagenValida, $errorImagen] = validarImagenSubida($_FILES['imagen'] ?? []);

        if (!$imagenValida && $errorImagen !== null) {
            $errores[] = $errorImagen;
        }

        if (empty($errores)) {
            try {
                $nombreImagen = $tatuajeActual['imagen'] ?? null;

                if (isset($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $nuevaImagen = guardarImagenSubida($_FILES['imagen'], $uploadDir);

                    if ($accion === 'actualizar' && !empty($nombreImagen)) {
                        borrarImagenLocal((string) $nombreImagen, $uploadDir);
                    }

                    $nombreImagen = $nuevaImagen;
                }

                $idEstiloFinal = $idEstilo === '' ? null : (int) $idEstilo;

                if ($accion === 'crear') {
                    $sql = '
                        INSERT INTO tatuajes (titulo, descripcion, imagen, id_estilo)
                        VALUES (:titulo, :descripcion, :imagen, :id_estilo)
                    ';

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':titulo', $titulo);
                    $stmt->bindValue(':descripcion', $descripcion === '' ? null : $descripcion);
                    $stmt->bindValue(':imagen', $nombreImagen);
                    $stmt->bindValue(':id_estilo', $idEstiloFinal, $idEstiloFinal === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt->execute();

                    $_SESSION['flash_success'] = 'Tatuaje creado correctamente.';
                    appRedirect('/admin/tatuajes.php');
                }

                $sql = '
                    UPDATE tatuajes
                    SET titulo = :titulo,
                        descripcion = :descripcion,
                        imagen = :imagen,
                        id_estilo = :id_estilo
                    WHERE id_tatuaje = :id_tatuaje
                ';

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':titulo', $titulo);
                $stmt->bindValue(':descripcion', $descripcion === '' ? null : $descripcion);
                $stmt->bindValue(':imagen', $nombreImagen);
                $stmt->bindValue(':id_estilo', $idEstiloFinal, $idEstiloFinal === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':id_tatuaje', $idTatuaje, PDO::PARAM_INT);
                $stmt->execute();

                $_SESSION['flash_success'] = 'Tatuaje actualizado correctamente.';
                appRedirect('/admin/tatuajes.php');
            } catch (Throwable $e) {
                $errores[] = 'No se pudo guardar el tatuaje.';
            }
        }
    } elseif ($accion === 'eliminar') {
        $idTatuaje = (int) ($_POST['id_tatuaje'] ?? 0);

        if ($idTatuaje <= 0) {
            $_SESSION['flash_error'] = 'El tatuaje seleccionado no es válido.';
            appRedirect('/admin/tatuajes.php');
        }

        try {
            $tatuaje = obtenerTatuajePorId($pdo, $idTatuaje);

            if ($tatuaje === null) {
                $_SESSION['flash_error'] = 'El tatuaje no existe.';
                appRedirect('/admin/tatuajes.php');
            }

            $sql = '
                DELETE FROM tatuajes
                WHERE id_tatuaje = :id_tatuaje
            ';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_tatuaje' => $idTatuaje,
            ]);

            borrarImagenLocal((string) ($tatuaje['imagen'] ?? ''), $uploadDir);

            $_SESSION['flash_success'] = 'Tatuaje eliminado correctamente.';
            appRedirect('/admin/tatuajes.php');
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo eliminar el tatuaje.';
            appRedirect('/admin/tatuajes.php');
        }
    } else {
        $errores[] = 'La acción solicitada no es válida.';
    }
}

$idEditar = (int) ($_GET['editar'] ?? 0);

if ($idEditar > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $tatuajeEditar = obtenerTatuajePorId($pdo, $idEditar);

        if ($tatuajeEditar !== null) {
            $form['id_tatuaje'] = (int) $tatuajeEditar['id_tatuaje'];
            $form['titulo'] = (string) $tatuajeEditar['titulo'];
            $form['descripcion'] = (string) ($tatuajeEditar['descripcion'] ?? '');
            $form['id_estilo'] = $tatuajeEditar['id_estilo'] !== null ? (string) $tatuajeEditar['id_estilo'] : '';
            $form['imagen_actual'] = (string) ($tatuajeEditar['imagen'] ?? '');
        } else {
            $mensajeError = 'El tatuaje que intentas editar no existe.';
        }
    } catch (PDOException $e) {
        $mensajeError = 'No se pudo cargar el tatuaje para editar.';
    }
}

$tatuajes = [];

try {
    $sql = '
        SELECT
            t.id_tatuaje,
            t.titulo,
            t.descripcion,
            t.imagen,
            t.fecha_creacion,
            COALESCE(e.nombre_estilo, "Sin estilo") AS estilo
        FROM tatuajes t
        LEFT JOIN estilos e ON t.id_estilo = e.id_estilo
        ORDER BY t.id_tatuaje DESC
    ';

    $stmt = $pdo->query($sql);
    $tatuajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = 'No se pudieron cargar los tatuajes.';
}

$totalTatuajes = count($tatuajes);
$totalEstilos = count($estilos);
$totalImagenes = 0;

foreach ($tatuajes as $tatuaje) {
    if (!empty($tatuaje['imagen'])) {
        $totalImagenes++;
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<main class="admin-tatuajes-page">
    <section class="page-intro center">
        <span class="section-kicker">Panel de administración</span>
        <h2>Gestión de tatuajes</h2>
        <p>Administra los diseños y trabajos que se mostrarán en el portfolio público.</p>
        <p>
            Sesión iniciada como:
            <strong><?php echo e((string) ($usuario['nombre'] ?? 'Administrador')); ?></strong>
        </p>
    </section>

    <section class="admin-tatuajes-layout">
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
                    <p><strong>Revisa los datos:</strong></p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo e((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <section class="tatuajes-stats-grid" aria-label="Resumen de tatuajes">
            <article class="tatuajes-stat-card stat-tatuajes">
                <div class="stat-icon" aria-hidden="true">✒</div>
                <div>
                    <h3>Total tatuajes</h3>
                    <p><?php echo (int) $totalTatuajes; ?></p>
                </div>
            </article>

            <article class="tatuajes-stat-card stat-estilos">
                <div class="stat-icon" aria-hidden="true">◆</div>
                <div>
                    <h3>Estilos disponibles</h3>
                    <p><?php echo (int) $totalEstilos; ?></p>
                </div>
            </article>

            <article class="tatuajes-stat-card stat-imagenes">
                <div class="stat-icon" aria-hidden="true">▧</div>
                <div>
                    <h3>Imágenes subidas</h3>
                    <p><?php echo (int) $totalImagenes; ?></p>
                </div>
            </article>
        </section>

        <section class="admin-tatuajes-workspace">
            <section class="form-card admin-tatuaje-form-card">
                <div class="section-heading-row">
                    <div>
                        <h3><?php echo $form['id_tatuaje'] > 0 ? 'Editar tatuaje' : 'Añadir tatuaje'; ?></h3>
                        <p>
                            <?php echo $form['id_tatuaje'] > 0
                                ? 'Actualiza la información del diseño seleccionado.'
                                : 'Añade un nuevo diseño o trabajo al portfolio público.'; ?>
                        </p>
                    </div>
                </div>

                <?php if (empty($estilos)): ?>
                    <div class="warning">
                        <p>No hay estilos creados todavía. Puedes guardar el tatuaje sin estilo o crear estilos antes.</p>
                    </div>
                <?php endif; ?>

                <form action="" method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="accion" value="<?php echo $form['id_tatuaje'] > 0 ? 'actualizar' : 'crear'; ?>">
                    <input type="hidden" name="id_tatuaje" value="<?php echo (int) $form['id_tatuaje']; ?>">

                    <div>
                        <label for="titulo">Título</label>
                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            placeholder="Ej. Rosa blackwork"
                            value="<?php echo e($form['titulo']); ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="id_estilo">Estilo</label>
                        <select id="id_estilo" name="id_estilo">
                            <option value="">Selecciona un estilo</option>

                            <?php foreach ($estilos as $estilo): ?>
                                <option
                                    value="<?php echo (int) $estilo['id_estilo']; ?>"
                                    <?php echo $form['id_estilo'] === (string) $estilo['id_estilo'] ? 'selected' : ''; ?>
                                >
                                    <?php echo e((string) $estilo['nombre_estilo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="descripcion">Descripción</label>
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            placeholder="Describe el diseño, técnica, zona recomendada o detalles del trabajo realizado"
                        ><?php echo e($form['descripcion']); ?></textarea>
                    </div>

                    <div>
                        <label for="imagen">Imagen</label>
                        <input
                            type="file"
                            id="imagen"
                            name="imagen"
                            accept=".jpg,.jpeg,.png,.webp"
                        >
                        <p class="field-help">Formatos permitidos: JPG, PNG o WEBP. Tamaño máximo: 5 MB.</p>
                    </div>

                    <?php if ($form['imagen_actual'] !== ''): ?>
                        <div class="current-image-box">
                            <p><strong>Imagen actual</strong></p>
                            <img
                                src="<?php echo e($imagePublicBase . '/' . basename($form['imagen_actual'])); ?>"
                                alt="<?php echo e($form['titulo'] !== '' ? $form['titulo'] : 'Imagen del tatuaje'); ?>"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="mt-3 actions">
                        <button type="submit">
                            <?php echo $form['id_tatuaje'] > 0 ? 'Actualizar tatuaje' : 'Guardar tatuaje'; ?>
                        </button>

                        <?php if ($form['id_tatuaje'] > 0): ?>
                            <a class="btn btn-secondary" href="tatuajes.php">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="form-card table-card admin-tatuajes-table-card">
                <div class="section-heading-row">
                    <div>
                        <h3>Listado de tatuajes</h3>
                        <p>Consulta, edita o elimina los trabajos que aparecen en el portfolio.</p>
                    </div>
                </div>

                <div class="table-wrapper admin-tatuajes-table-wrapper">
                    <table class="tabla-tatuajes tabla-admin-tatuajes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Estilo</th>
                                <th>Imagen</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($tatuajes)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        Todavía no hay tatuajes registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tatuajes as $tatuaje): ?>
                                    <tr>
                                        <td class="cita-id">
                                            #<?php echo (int) $tatuaje['id_tatuaje']; ?>
                                        </td>

                                        <td class="titulo-tatuaje-admin">
                                            <strong><?php echo e((string) $tatuaje['titulo']); ?></strong>

                                            <?php if (!empty($tatuaje['descripcion'])): ?>
                                                <span><?php echo e(mb_strimwidth((string) $tatuaje['descripcion'], 0, 70, '...')); ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-estilo">
                                                <?php echo e((string) $tatuaje['estilo']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if (!empty($tatuaje['imagen'])): ?>
                                                <img
                                                    class="tattoo-thumb"
                                                    src="<?php echo e($imagePublicBase . '/' . basename((string) $tatuaje['imagen'])); ?>"
                                                    alt="<?php echo e((string) $tatuaje['titulo']); ?>"
                                                >
                                            <?php else: ?>
                                                <span class="sin-imagen">Sin imagen</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php echo e(formatearFechaTatuaje((string) $tatuaje['fecha_creacion'])); ?>
                                        </td>

                                        <td>
                                            <div class="acciones-tatuaje">
                                                <a
                                                    class="btn btn-secondary btn-action"
                                                    href="tatuajes.php?editar=<?php echo (int) $tatuaje['id_tatuaje']; ?>"
                                                >
                                                    Editar
                                                </a>

                                                <form action="" method="post" class="accion-inline">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input
                                                        type="hidden"
                                                        name="id_tatuaje"
                                                        value="<?php echo (int) $tatuaje['id_tatuaje']; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn-action btn-reject"
                                                        data-confirm="¿Seguro que quieres eliminar este tatuaje?"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
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