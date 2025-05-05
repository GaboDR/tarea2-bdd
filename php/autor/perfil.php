<?php
include('../db.php');
include('../includes/header.php');

if (!isset($_SESSION['autor_id'])) {
    header("Location: ../login/login_autor.php");
    exit;
}

$autor_id = $_SESSION['autor_id'];

$stmt = $conexion->prepare("SELECT nombre, email FROM AUTOR WHERE id = ?");
$stmt->bind_param("i", $autor_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $autor = $resultado->fetch_assoc();
} else {
    echo "Error al obtener los datos del perfil.";
    exit;
}
?>

<div class="container mt-5">
  <h2>Perfil del Autor</h2>
  <p><strong>Nombre:</strong> <?= htmlspecialchars($autor['nombre']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($autor['email']) ?></p>

  <!-- Botón que abre el modal -->
  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editarModal">
    Editar Perfil
  </button>

<!-- Botón para eliminar perfil -->
<button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmarEliminarModal">
  Eliminar Perfil
</button>

</div>

<!-- Modal de edición -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="../controller/editar_autor.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editarModalLabel">Editar Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="autor_id" value="<?= $autor_id ?>">
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($autor['nombre']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Correo</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($autor['email']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="••••••••">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmarEliminarLabel">¿Eliminar perfil?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Esta acción no se puede deshacer. ¿Seguro que deseas eliminar tu perfil?
      </div>
      <div class="modal-footer">
        <form action="../controller/eliminar_autor.php" method="POST">
          <input type="hidden" name="autor_id" value="<?php echo $_SESSION['autor_id']; ?>">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Eliminar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
