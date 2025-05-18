<?php
include('includes/header.php');
include('db.php');

// Consulta modificada para incluir tópicos adicionales
$query = "
SELECT
    a.ID AS articulo_id,
    a.TITULO,
    a.RESUMEN,
    a.TOPICO_PRINCIPAL,
    a.NUM_REVISORES,
    a.puntajeFinal,
    GROUP_CONCAT(DISTINCT au.NOMBRE SEPARATOR ', ') AS autores,
    GROUP_CONCAT(DISTINCT te.TOPICO_EXTRA SEPARATOR ', ') AS topicos_extra,
    ar.ID_REVISOR,
    r.ID AS revision_id,
    r.puntuacion_global,
    r.originalidad,
    r.claridad,
    r.relevancia,
    r.comentarios
FROM ARTICULO a
JOIN ARTICULO_REVISOR ar ON ar.ID_ARTICULO = a.ID
JOIN REVISION r ON r.ARTICULO_REVISOR_ID = ar.ID
LEFT JOIN AUTOR_PARTICIPANTE ap ON ap.ID_ARTICULO = a.ID
LEFT JOIN AUTOR au ON au.ID = ap.ID_AUTOR
LEFT JOIN topicos_extra te ON te.ID_ARTICULO = a.ID
GROUP BY a.ID, r.ID
ORDER BY a.ID, r.ID;
";

$result = $conexion->query($query);

$articulos = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['articulo_id'];
    if (!isset($articulos[$id])) {
        $todos_los_topicos = $row['TOPICO_PRINCIPAL'];
        if (!empty($row['topicos_extra'])) {
            $todos_los_topicos .= ', ' . $row['topicos_extra'];
        }

        $articulos[$id] = [
            'titulo' => $row['TITULO'],
            'resumen' => $row['RESUMEN'],
            'topicos' => $todos_los_topicos,
            'num_revisores' => $row['NUM_REVISORES'],
            'puntaje_final' => $row['puntajeFinal'],
            'autores' => $row['autores'],
            'revisiones' => []
        ];
    }

    $articulos[$id]['revisiones'][] = [
        'id_revision' => $row['revision_id'],
        'id_revisor' => $row['ID_REVISOR'],
        'puntuacion_global' => $row['puntuacion_global'],
        'originalidad' => $row['originalidad'],
        'claridad' => $row['claridad'],
        'relevancia' => $row['relevancia'],
        'comentarios' => $row['comentarios'],
    ];
}
?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Artículos Evaluados</h2>
    <?php if (count($articulos) === 0): ?>
        <div class="alert alert-warning text-center" role="alert">
            No hay artículos evaluados aún.
        </div>
    <?php else: ?>
        <?php foreach ($articulos as $articulo): ?>
            <div class="card mb-5 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-1"><?= htmlspecialchars($articulo['titulo']) ?></h4>
                    <small><strong>Autores:</strong> <?= htmlspecialchars($articulo['autores']) ?></small><br>
                    <small><strong>Tópicos:</strong> <?= htmlspecialchars($articulo['topicos']) ?></small><br>
                    <small><strong>Resumen:</strong> <?= htmlspecialchars($articulo['resumen']) ?></small><br>
                    <small><strong>Número de Revisores:</strong> <?= (int)$articulo['num_revisores'] ?></small><br>
                    <small><strong>Puntaje Final:</strong> <?= is_null($articulo['puntaje_final']) ? '-' : htmlspecialchars($articulo['puntaje_final']) ?></small>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Revisiones</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Revisor ID</th>
                                    <th scope="col">Puntaje Global</th>
                                    <th scope="col">Originalidad</th>
                                    <th scope="col">Claridad</th>
                                    <th scope="col">Relevancia</th>
                                    <th scope="col">Comentarios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articulo['revisiones'] as $rev): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rev['id_revisor']) ?></td>
                                        <td><?= htmlspecialchars($rev['puntuacion_global']) ?></td>
                                        <td><?= htmlspecialchars($rev['originalidad']) ?></td>
                                        <td><?= htmlspecialchars($rev['claridad']) ?></td>
                                        <td><?= htmlspecialchars($rev['relevancia']) ?></td>
                                        <td style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($rev['comentarios'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
