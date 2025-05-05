<?php include('../includes/header.php'); 
include('../db.php')
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="text-center mb-4">Login Revisor</h4>

          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
              <?php
              switch ($_GET['error']) {
                  case 'campos_vacios':
                      echo 'Completa todos los campos.';
                      break;
                  case 'contrasena_incorrecta':
                      echo 'Contraseña incorrecta.';
                      break;
                  case 'usuario_no_encontrado':
                      echo 'Usuario no encontrado.';
                      break;
                  case 'sql_error':
                      echo 'Error interno.';
                      break;
                  default:
                      echo 'Error desconocido.';
              }
              ?>
            </div>
          <?php endif; ?>

          <form action="../controller/login_revisor.php" method="POST">
            <div class="mb-3">
              <label for="email" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary">Iniciar sesión</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
