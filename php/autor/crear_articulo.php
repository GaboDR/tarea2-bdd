<?php 
include('../includes/header.php'); 
include('../db.php');

if (!isset($_SESSION['autor_id'])) {
    header("Location: ../login/login_autor.php");
    exit;
}

// Cargar todos los autores (menos el logueado)
$query_autores = "SELECT * FROM autor WHERE id != ?";
$stmt_autores = $conexion->prepare($query_autores);
$stmt_autores->bind_param("i", $_SESSION['autor_id']);
$stmt_autores->execute();
$result_autores = $stmt_autores->get_result();

// Tópicos
$query_topicos = "SELECT nombre FROM topico_especialidad";
$result_topicos = $conexion->query($query_topicos);
?>

<div class="container mt-5">
  <h2>Crear Nuevo Artículo</h2>
  <p>Llena los siguientes campos para enviar un nuevo artículo.</p>

  <form action="../controller/guardar_articulo.php" method="POST">
    <!-- Título -->
    <div class="form-group">
      <label for="titulo">Título</label>
      <input type="text" name="titulo" id="titulo" class="form-control" required>
    </div>

    <!-- Resumen -->
    <div class="form-group">
      <label for="resumen">Resumen</label>
      <textarea name="resumen" id="resumen" class="form-control" rows="4" required></textarea>
    </div>

    <!-- Tópicos -->
    <div class="mb-3">
      <label class="form-label">Tópicos</label>
      <div class="form-check">
        <?php while ($row_topico = mysqli_fetch_assoc($result_topicos)) { ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="topicos[]" value="<?= htmlspecialchars($row_topico['nombre']); ?>" id="topico<?= htmlspecialchars($row_topico['nombre']); ?>">
            <label class="form-check-label" for="topico<?= htmlspecialchars($row_topico['nombre']); ?>">
              <?= htmlspecialchars($row_topico['nombre']); ?>
            </label>
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- Autores -->
    <div class="mb-3">
      <label class="form-label">Autores</label>
      <div class="form-check">
        <!-- Autor logueado -->
        <div class="form-check">
          <input class="form-check-input" type="checkbox" checked disabled>
          <input type="hidden" name="autores[]" value="<?= $_SESSION['autor_id']; ?>">
          <label class="form-check-label">
            <?= $_SESSION['autor_nombre'] ?? 'Autor Logueado' ?> (Autor Logueado)
          </label>
        </div>

        <?php while ($row_autor = mysqli_fetch_assoc($result_autores)) { ?>
          <div class="form-check">
            <input class="form-check-input autor-checkbox" type="checkbox" name="autores[]" value="<?= $row_autor['ID']; ?>" id="autor<?= $row_autor['ID']; ?>">
            <label class="form-check-label" for="autor<?= $row_autor['ID']; ?>">
              <?= htmlspecialchars($row_autor['NOMBRE']); ?>
            </label>
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- Autor de Contacto -->
    <div class="form-group">
      <label for="autor_contacto">Autor de Contacto</label>
      <select name="autor_contacto" id="autor_contacto" class="form-control" required>
        <option value="<?= $_SESSION['autor_id']; ?>">
          <?= $_SESSION['autor_nombre'] ?? 'Autor Logueado' ?>
        </option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Enviar Artículo</button>
  </form>
</div>

<script>
// Actualiza el select de autor de contacto
const checkboxes = document.querySelectorAll('.autor-checkbox');
const selectContacto = document.getElementById('autor_contacto');

// Guarda el autor logueado por defecto
const autorLogueado = {
  id: '<?= $_SESSION['autor_id']; ?>',
  nombre: '<?= $_SESSION['autor_nombre'] ?? "Autor Logueado" ?>'
};

function updateAutorContacto() {
  // Limpiar select
  selectContacto.innerHTML = '';

  // Agregar autor logueado
  const opt = document.createElement('option');
  opt.value = autorLogueado.id;
  opt.textContent = autorLogueado.nombre + ' (Autor Logueado)';
  selectContacto.appendChild(opt);

  // Agregar autores seleccionados
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

// Evento al cambiar cualquier checkbox
checkboxes.forEach(chk => chk.addEventListener('change', updateAutorContacto));

// Inicializar
updateAutorContacto();
</script>

<?php include('../includes/footer.php'); ?>
