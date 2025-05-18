<?php include('../includes/header.php'); ?>
<?php include('../db.php'); ?>

<?php
// Consulta de tópicos
$query = "SELECT nombre FROM topico_especialidad";
$result = mysqli_query($conexion, $query);
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-4 text-center">Registro de Revisor</h4>
        <?php
        include('../includes/flash.php');
        mostrar_mensaje_sesion('error');
        mostrar_mensaje_sesion('exito');
        mostrar_mensaje_sesion('info');
        ?>
    </div>
          <form action="../controller/singin_revisor.php" method="POST">
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre completo</label>
              <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>

            <div class="mb-3">
              <label for="rut" class="form-label">RUT</label>
              <input type="text" class="form-control" id="rut" name="rut" maxlength="10" required>
            </div>

            <div class="mb-3">
              <label for="correo" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" id="correo" name="email" required>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="password" name="contrasena" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Tópicos de especialidad</label>
              <div class="form-check">
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="topicos[]" value="<?= $row['nombre']; ?>" id="topico<?= $row['nombre']; ?>">
                    <label class="form-check-label" for="topico<?= $row['nombre']; ?>">
                      <?= htmlspecialchars($row['nombre']); ?>
                    </label>
                  </div>
                <?php } ?>
              </div>
            </div>


            <div class="d-grid">
              <button type="submit" class="btn btn-success">Registrar revisor</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
