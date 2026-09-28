<?php
/*
Proyecto TFG Tattoo.
Sistema de gestión de citas y portfolio para tatuadores.
Código estructurado con PHP + MySQL (PDO).
Mantener coherencia con arquitectura existente.
*/

// public/portfolio.php

declare(strict_types=1);

$pageTitle = 'Portfolio';

require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/config/database.php';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function obtenerRutaImagen(string $imagen): string
{
    $imagen = trim($imagen);

    if ($imagen === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#', $imagen) === 1) {
        return $imagen;
    }

    if ($imagen[0] === '/') {
        return $imagen;
    }

    if (strpos($imagen, 'assets/') === 0) {
        return appPath('/public/' . ltrim($imagen, '/'));
    }

    return appPath('/public/assets/img/' . ltrim($imagen, '/'));
}

$estilos = [];
$tatuajes = [];
$error = null;

$idEstiloSeleccionado = filter_var(
    $_GET['estilo'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($idEstiloSeleccionado === false || $idEstiloSeleccionado === null) {
    $idEstiloSeleccionado = 0;
}

try {
    $sqlEstilos = '
        SELECT id_estilo, nombre_estilo
        FROM estilos
        ORDER BY nombre_estilo ASC
    ';

    $stmtEstilos = $pdo->query($sqlEstilos);
    $estilos = $stmtEstilos->fetchAll(PDO::FETCH_ASSOC);

    $idsEstilosValidos = array_map(
        static fn(array $estilo): int => (int) $estilo['id_estilo'],
        $estilos
    );

    if ($idEstiloSeleccionado > 0 && !in_array($idEstiloSeleccionado, $idsEstilosValidos, true)) {
        $idEstiloSeleccionado = 0;
    }

    $sql = '
        SELECT
            t.id_tatuaje,
            t.titulo,
            t.descripcion,
            t.imagen,
            t.id_estilo,
            COALESCE(e.nombre_estilo, "Sin estilo") AS estilo
        FROM tatuajes t
        LEFT JOIN estilos e ON t.id_estilo = e.id_estilo
    ';

    $params = [];

    if ($idEstiloSeleccionado > 0) {
        $sql .= ' WHERE t.id_estilo = :id_estilo';
        $params[':id_estilo'] = $idEstiloSeleccionado;
    }

    $sql .= ' ORDER BY t.id_tatuaje DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tatuajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $error = 'No se pudo cargar el portfolio en este momento.';
}

$nombreEstiloSeleccionado = 'Todos los estilos';

foreach ($estilos as $estilo) {
    if ((int) $estilo['id_estilo'] === $idEstiloSeleccionado) {
        $nombreEstiloSeleccionado = (string) $estilo['nombre_estilo'];
        break;
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<main class="portfolio-page">
    <section class="page-intro center">
        <span class="section-kicker">Arte y portfolio</span>
        <h2>Portfolio de tatuajes</h2>
        <p>Explora diseños, estilos y trabajos realizados. Filtra por estilo para encontrar referencias más cercanas a tu idea.</p>
    </section>

    <section class="portfolio-workspace">
        <aside class="form-card portfolio-filter-card">
            <div class="section-heading-row">
                <div>
                    <h3>Filtrar por estilo</h3>
                    <p>Selecciona una categoría para ajustar la galería.</p>
                </div>
            </div>

            <form action="" method="get">
                <div>
                    <label for="estilo">Estilo</label>
                    <select name="estilo" id="estilo">
                        <option value="">Todos los estilos</option>

                        <?php foreach ($estilos as $estilo): ?>
                            <option
                                value="<?php echo (int) $estilo['id_estilo']; ?>"
                                <?php echo $idEstiloSeleccionado === (int) $estilo['id_estilo'] ? 'selected' : ''; ?>
                            >
                                <?php echo e((string) $estilo['nombre_estilo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-3 actions">
                    <button type="submit">Filtrar</button>
                    <a class="btn btn-secondary" href="portfolio.php">Limpiar filtro</a>
                </div>
            </form>

            <div class="portfolio-filter-summary">
                <span>Mostrando</span>
                <strong><?php echo e($nombreEstiloSeleccionado); ?></strong>
                <p><?php echo count($tatuajes); ?> resultado(s) encontrados.</p>
            </div>
        </aside>

        <section class="portfolio-gallery-panel">
            <div class="section-heading-row portfolio-heading">
                <div>
                    <h3>Galería de trabajos</h3>
                    <p>Tarjetas visuales con el estilo, imagen y descripción de cada tatuaje.</p>
                </div>
            </div>

            <?php if ($error !== null): ?>
                <div class="form-card portfolio-message-card">
                    <div class="error">
                        <p><?php echo e($error); ?></p>
                    </div>
                </div>
            <?php elseif (empty($tatuajes)): ?>
                <div class="form-card portfolio-message-card">
                    <div class="warning">
                        <p>No hay tatuajes disponibles para este filtro.</p>
                    </div>
                </div>
            <?php else: ?>
                <section class="galeria-tatuajes portfolio-gallery">
                    <?php foreach ($tatuajes as $tatuaje): ?>
                        <?php
                        $rutaImagen = obtenerRutaImagen((string) ($tatuaje['imagen'] ?? ''));
                        $titulo = (string) ($tatuaje['titulo'] ?? 'Tatuaje');
                        $descripcion = trim((string) ($tatuaje['descripcion'] ?? ''));
                        $estilo = (string) ($tatuaje['estilo'] ?? 'Sin estilo');
                        ?>

                        <article class="tatuaje portfolio-tattoo-card">
                            <div class="portfolio-image-wrap">
                                <?php if ($rutaImagen !== ''): ?>
                                    <img
                                        src="<?php echo e($rutaImagen); ?>"
                                        alt="<?php echo e($titulo); ?>"
                                    >
                                <?php else: ?>
                                    <div class="portfolio-image-placeholder">
                                        <span>Sin imagen</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="portfolio-card-content">
                                <span class="badge badge-estilo">
                                    <?php echo e($estilo); ?>
                                </span>

                                <h3><?php echo e($titulo); ?></h3>

                                <?php if ($descripcion !== ''): ?>
                                    <p><?php echo nl2br(e($descripcion)); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Diseño disponible en portfolio.</p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </section>
    </section>
</main>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>