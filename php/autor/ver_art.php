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
?>

<div class="container mt-5">
    <h2>Mis Artículos</h2>
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
                        <!-- Botón Modificar -->
                        <button 
                            class="btn btn-warning btn-sm" 
                            data-toggle="modal" 
                            data-target="#modalModificar<?= $articulo['id'] ?>"
                            <?= $articulo['num_revisores'] > 0 ? "disabled" : "" ?>
                        >Modificar</button>

                        <!-- Botón Eliminar -->
                        <button 
                            class="btn btn-danger btn-sm" 
                            data-toggle="modal" 
                            data-target="#modalEliminar<?= $articulo['id'] ?>"
                        >Eliminar</button>
                    </td>
                </tr>

                <!-- Modal Modificar -->
                <div class="modal fade" id="modalModificar<?= $articulo['id'] ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form action="../controller/modificar_articulo.php" method="POST">
                            <input type="hidden" name="id" value="<?= $articulo['id'] ?>">
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

<?php include('../includes/footer.php'); ?>
