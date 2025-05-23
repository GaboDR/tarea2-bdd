<?php
include('../includes/header.php');
include('../db.php');

if (!isset($_SESSION['autor_id'])) {
    header("Location: ../login/login_autor.php");
    exit;
}

$autor_id = $_SESSION['autor_id'];

$query = "
    SELECT a.id, a.titulo, a.resumen, a.num_revisores
    FROM articulo a
    LEFT JOIN autor_participante ap ON a.id = ap.id_articulo
    WHERE a.autor_contacto = ? OR ap.id_autor = ?
    GROUP BY a.id
";
$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $autor_id, $autor_id);
$stmt->execute();
$result = $stmt->get_result();

// Guardar tópicos en array
$query_topicos = "SELECT nombre FROM topico_especialidad";
$result_topicos = $conexion->query($query_topicos);
$topicos_array = [];
while ($row = $result_topicos->fetch_assoc()) {
    $topicos_array[] = $row;
}

// Guardar autores en array
$query_autores = "SELECT * FROM autor WHERE id != ?";
$stmt_autores = $conexion->prepare($query_autores);
$stmt_autores->bind_param("i", $_SESSION['autor_id']);
$stmt_autores->execute();
$result_autores = $stmt_autores->get_result();
$autores_array = [];
while ($row = $result_autores->fetch_assoc()) {
    $autores_array[] = $row;
}
?>

<div class="container mt-5">
    <h2>Mis Artículos</h2>
    <?php
        include('../includes/flash.php');
        mostrar_mensaje_sesion('error');
        mostrar_mensaje_sesion('exito');
        mostrar_mensaje_sesion('info');
        ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Título</th>
                <th>Resumen</th>
                <th>Revisión</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($articulo = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($articulo['titulo']) ?></td>
                    <td><?= htmlspecialchars($articulo['resumen']) ?></td>
                    <td><?= $articulo['num_revisores'] > 0 ? "En Revisión" : "Pendiente" ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalModificar<?= $articulo['id'] ?>" <?= $articulo['num_revisores'] > 0 ? "disabled" : "" ?>>Modificar</button>
                        <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modalEliminar<?= $articulo['id'] ?>" <?= $articulo['num_revisores'] > 0 ? "disabled" : "" ?>>Eliminar</button>
                    </td>
                </tr>

                <!-- Revisiones -->
                <?php
                $revisiones_stmt = $conexion->prepare("
                    SELECT r.puntuacion_global, r.originalidad, r.claridad, r.relevancia, r.comentarios, rv.nombre AS revisor_nombre
                    FROM revision r
                    JOIN articulo_revisor ar ON r.articulo_revisor_id = ar.id
                    JOIN revisor rv ON ar.id_revisor = rv.id
                    WHERE ar.id_articulo = ?
                ");
                $revisiones_stmt->bind_param("i", $articulo['id']);
                $revisiones_stmt->execute();
                $revisiones_result = $revisiones_stmt->get_result();
                ?>

                <?php if ($revisiones_result->num_rows > 0): ?>
                <tr>
                    <td colspan="4">
                        <strong>Revisiones Realizadas:</strong>
                        <div class="accordion" id="accordionRevisiones<?= $articulo['id'] ?>">
                            <?php $index = 0; ?>
                            <?php while ($rev = $revisiones_result->fetch_assoc()): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $articulo['id'] . $index ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $articulo['id'] . $index ?>" aria-expanded="false" aria-controls="collapse<?= $articulo['id'] . $index ?>">
                                            Revisor: <?= htmlspecialchars($rev['revisor_nombre']) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $articulo['id'] . $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $articulo['id'] . $index ?>" data-bs-parent="#accordionRevisiones<?= $articulo['id'] ?>">
                                        <div class="accordion-body">
                                            <p><strong>Puntuación Global:</strong> <?= $rev['puntuacion_global'] ?></p>
                                            <p><strong>Originalidad:</strong> <?= $rev['originalidad'] ?></p>
                                            <p><strong>Claridad:</strong> <?= $rev['claridad'] ?></p>
                                            <p><strong>Relevancia:</strong> <?= $rev['relevancia'] ?></p>
                                            <p><strong>Comentarios:</strong> <?= nl2br(htmlspecialchars($rev['comentarios'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php $index++; ?>
                            <?php endwhile; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- Modal Modificar -->
                <!-- ... tu modal de modificar aquí como ya lo tenías ... -->
                <div class="modal fade" id="modalModificar<?= $articulo['id'] ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form action="../controller/modificar_articulo.php" method="POST">
                            <input type="hidden" name="articulo_id" value="<?= $articulo['id'] ?>">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modificar Artículo</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="titulo">Título</label>
                                        <input type="text" class="form-control" name="titulo" value="<?= htmlspecialchars($articulo['titulo']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="resumen">Resumen</label>
                                        <textarea class="form-control" name="resumen" rows="4" required><?= htmlspecialchars($articulo['resumen']) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tópicos</label>
                                        <div class="form-check">
                                            <?php foreach ($topicos_array as $row_topico) { ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="topicos[]" value="<?= htmlspecialchars($row_topico['nombre']); ?>" id="topico<?= htmlspecialchars($row_topico['nombre']); ?>">
                                                    <label class="form-check-label" for="topico<?= htmlspecialchars($row_topico['nombre']); ?>">
                                                        <?= htmlspecialchars($row_topico['nombre']); ?>
                                                    </label>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Autores</label>
                                        <div class="form-check">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" checked disabled>
                                                <input type="hidden" name="autores[]" value="<?= $_SESSION['autor_id']; ?>">
                                                <label class="form-check-label">
                                                    <?= $_SESSION['autor_nombre'] ?? 'Autor Logueado' ?> (Autor Logueado)
                                                </label>
                                            </div>

                                            <?php foreach ($autores_array as $row_autor) { ?>
                                                <div class="form-check">
                                                    <input class="form-check-input autor-checkbox" type="checkbox" name="autores[]" value="<?= $row_autor['ID']; ?>" id="autor<?= $row_autor['ID']; ?>">
                                                    <label class="form-check-label" for="autor<?= $row_autor['ID']; ?>">
                                                        <?= htmlspecialchars($row_autor['NOMBRE']); ?>
                                                    </label>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="autor_contacto">Autor de Contacto</label>
                                        <select name="autor_contacto" id="autor_contacto" class="form-control" required>
                                            <option value="<?= $_SESSION['autor_id']; ?>">
                                                <?= $_SESSION['autor_nombre'] ?? 'Autor Logueado' ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Eliminar -->
                <!-- ... tu modal de eliminar aquí como ya lo tenías ... -->
                <div class="modal fade" id="modalEliminar<?= $articulo['id'] ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form action="../controller/eliminar_articulo.php" method="POST">
                            <input type="hidden" name="id" value="<?= $articulo['id'] ?>">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Eliminar Artículo</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    ¿Estás seguro que deseas eliminar este artículo?
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </tbody>
    </table>
</div>


<script>
const checkboxes = document.querySelectorAll('.autor-checkbox');
const selectContacto = document.getElementById('autor_contacto');
const autorLogueado = {
    id: '<?= $_SESSION['autor_id']; ?>',
    nombre: '<?= $_SESSION['autor_nombre'] ?? "Autor Logueado" ?>'
};

function updateAutorContacto() {
    if (!selectContacto) return;

    selectContacto.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = autorLogueado.id;
    opt.textContent = autorLogueado.nombre + ' (Autor Logueado)';
    selectContacto.appendChild(opt);

    checkboxes.forEach(chk => {
        if (chk.checked) {
            const label = document.querySelector(`label[for="${chk.id}"]`);
            const option = document.createElement('option');
            option.value = chk.value;
            option.textContent = label ? label.textContent : chk.value;
            selectContacto.appendChild(option);
        }
    });
}

checkboxes.forEach(chk => chk.addEventListener('change', updateAutorContacto));
updateAutorContacto();
</script>

<?php include('../includes/footer.php'); ?>
