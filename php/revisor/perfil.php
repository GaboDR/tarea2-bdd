<?php
include('../db.php');
include('../includes/header.php');

if (!isset($_SESSION['revisor_id'])) {
    header("Location: ../login/login_revisor.php");
    exit;
}

$revisor_id = $_SESSION['revisor_id'];

$stmt = $conexion->prepare("SELECT nombre, email, topico_especialidad FROM REVISOR WHERE id = ?");
$stmt->bind_param("i", $revisor_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $revisor = $resultado->fetch_assoc();
} else {
    echo "Error al obtener los datos del perfil.";
    exit;
}

// Obtener especialidades adicionales
$stmt_extra = $conexion->prepare("SELECT ESPECIALIDAD_EXTRA FROM ESPECIALIDAD_AGREGADA WHERE ID_REVISOR = ?");
$stmt_extra->bind_param("i", $revisor_id);
$stmt_extra->execute();
$result_extra = $stmt_extra->get_result();

$especialidades_extra = [];
while ($row = $result_extra->fetch_assoc()) {
    $especialidades_extra[] = $row['ESPECIALIDAD_EXTRA'];
}

// Obtener todos los tópicos disponibles
$sql_topicos = "SELECT nombre FROM TOPICO_ESPECIALIDAD";  // Ajusta si tu tabla se llama distinto
$result_topicos = $conexion->query($sql_topicos);

// Guardamos en un array
$topicos_disponibles = [];
while ($row = $result_topicos->fetch_assoc()) {
    $topicos_disponibles[] = $row['nombre'];
}
// Tópicos que tiene actualmente (principal + adicionales)
$topicos_asignados = array_merge([$revisor['topico_especialidad']], $especialidades_extra);

?>

<div class="container mt-5">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h3 class="mb-0">Perfil del Revisor</h3>
      
    </div>
    <div class="card-body">
            <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['mensaje_exito']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['mensaje_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        <?php unset($_SESSION['mensaje_error']); ?>
        <?php endif; ?>


        <div class="mb-3">
        <strong>Nombre:</strong> <?= htmlspecialchars($revisor['nombre']) ?>
      </div>
      <div class="mb-3">
        <strong>Email:</strong> <?= htmlspecialchars($revisor['email']) ?>
      </div>
      <div>
        <strong>Especialidades:</strong>
        <ul class="list-group mt-2">
          <!-- Especialidad principal -->
          <li class="list-group-item">
            <?= htmlspecialchars($revisor['topico_especialidad']) ?> <span class="badge bg-primary">Principal</span>
          </li>
          
          <!-- Especialidades adicionales -->
          <?php foreach ($especialidades_extra as $extra) { ?>
            <li class="list-group-item">
              <?= htmlspecialchars($extra) ?> <span class="badge bg-secondary">Adicional</span>
            </li>
          <?php } ?>
        </ul>
      </div>
      <!-- Botón que abre el modal -->
      <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editarModal">
        Editar Perfil
      </button>
      
      <!-- Botón para eliminar perfil -->
      <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmarEliminarModal">
      Eliminar Perfil
      </button>
    </div>
  </div>
</div>



</div>

<!-- Modal de Edición -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="../controller/editar_revisor.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editarModalLabel">Editar Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="revisor_id" value="<?= $revisor_id ?>">

        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($revisor['nombre']) ?>" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Correo</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($revisor['email']) ?>" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="••••••••">
        </div>

        <div class="mb-3">
            <label class="form-label">Tópicos</label>
            <?php foreach ($topicos_disponibles as $topico) { 
                $checked = in_array($topico, $topicos_asignados) ? 'checked' : '';
            ?>
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="topicos[]" value="<?= htmlspecialchars($topico); ?>" id="topico<?= htmlspecialchars($topico); ?>" <?= $checked ?>>
                <label class="form-check-label" for="topico<?= htmlspecialchars($topico); ?>">
                    <?= htmlspecialchars($topico); ?>
                </label>
                </div>
            <?php } ?>
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
    <form action="../controller/eliminar_revisor_p.php" method="POST" class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmarEliminarLabel">¿Eliminar perfil?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        Esta acción no se puede deshacer. ¿Estás seguro de que deseas eliminar tu perfil?
        <input type="hidden" name="revisor_id" value="<?= $revisor_id ?>">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>


<?php include('../includes/footer.php'); ?>
