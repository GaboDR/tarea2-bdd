<?php
include('../includes/header.php');
include('../db.php');

$revisor_id = $_SESSION['revisor_id'];

// Obtener todos los artículos asignados al revisor junto con su posible revisión
$query = "SELECT a.id, a.titulo, a.resumen, r.id AS revision_id
          FROM ARTICULO a
          INNER JOIN ARTICULO_REVISOR ar ON a.id = ar.id_articulo
          LEFT JOIN REVISION r ON r.ARTICULO_REVISOR_ID = ar.id
          WHERE ar.id_revisor = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $revisor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-5">
  <h2 class="mb-4 text-center">Artículos Asignados</h2>

  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($row['titulo']) ?></h5>
        <p class="card-text"><?= nl2br(htmlspecialchars($row['resumen'])) ?></p>

        <?php if ($row['revision_id']): ?>
  <div class="alert alert-success d-flex justify-content-between align-items-center">
    Ya has calificado este artículo.
    <div>
      <!-- Botón para editar -->
      <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $row['id'] ?>">
        Editar
      </button>
      <!-- Botón para eliminar -->
      <form action="../controller/eliminar_revision.php" method="POST" style="display:inline;">
        <input type="hidden" name="revision_id" value="<?= $row['revision_id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro que deseas eliminar esta revisión?');">
          Eliminar
        </button>
      </form>
    </div>
  </div>

  <!-- Modal para editar revisión -->
  <div class="modal fade" id="modalEditar<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalLabelEditar<?= $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="../controller/actualizar_revision.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="modalLabelEditar<?= $row['id'] ?>">Editar Revisión: <?= htmlspecialchars($row['titulo']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="revision_id" value="<?= $row['revision_id'] ?>">

            <!-- Aquí deberías cargar los valores actuales de la revisión (ver sugerencia abajo) -->

            <div class="mb-3">
              <label>Puntuación Global (1-10)</label>
              <input type="number" name="puntuacion_global" min="1" max="10" class="form-control" required>
            </div>

            <div class="mb-3">
              <label>Originalidad (1-5)</label>
              <input type="number" name="originalidad" min="1" max="5" class="form-control" required>
            </div>

            <div class="mb-3">
              <label>Claridad (1-5)</label>
              <input type="number" name="claridad" min="1" max="5" class="form-control" required>
            </div>

            <div class="mb-3">
              <label>Relevancia (1-5)</label>
              <input type="number" name="relevancia" min="1" max="5" class="form-control" required>
            </div>

            <div class="mb-3">
              <label>Comentarios</label>
              <textarea name="comentarios" rows="4" class="form-control" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar Revisión</button>
          </div>
        </form>
      </div>
    </div>
  </div>

        <?php else: ?>
          <!-- Botón para abrir modal -->
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCalificar<?= $row['id'] ?>">
            Calificar
          </button>

          <!-- Modal -->
          <div class="modal fade" id="modalCalificar<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form action="../controller/guardar_revision.php" method="POST">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel<?= $row['id'] ?>">Calificar Artículo: <?= htmlspecialchars($row['titulo']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="articulo_id" value="<?= $row['id'] ?>">

                    <div class="mb-3">
                      <label for="puntuacion_global<?= $row['id'] ?>" class="form-label">Puntuación Global (1-10)</label>
                      <input type="number" name="puntuacion_global" min="1" max="10" class="form-control" required>
                    </div>

                    <div class="mb-3">
                      <label>Originalidad (1-5)</label>
                      <input type="number" name="originalidad" min="1" max="5" class="form-control" required>
                    </div>

                    <div class="mb-3">
                      <label>Claridad (1-5)</label>
                      <input type="number" name="claridad" min="1" max="5" class="form-control" required>
                    </div>

                    <div class="mb-3">
                      <label>Relevancia (1-5)</label>
                      <input type="number" name="relevancia" min="1" max="5" class="form-control" required>
                    </div>

                    <div class="mb-3">
                      <label>Comentarios</label>
                      <textarea name="comentarios" rows="4" class="form-control" required></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Revisión</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include('../includes/footer.php'); ?>
