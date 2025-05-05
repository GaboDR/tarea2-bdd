<?php include('../includes/header.php'); 
include('../db.php')
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body">
          <h3 class="card-title text-center mb-4">Login de Autor</h3>
          <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        switch ($_GET['error']) {
            case 'campos_vacios':
                echo 'Por favor, completa todos los campos.';
                break;
            case 'contrasena_incorrectra':
                echo 'Password incorrecta.';
                break;
            case 'sql_error':
                echo 'Ocurrió un error al registrar. Intenta nuevamente.';
                break;
            case 'usuario_no_encontrado':
                echo 'Usuario no existente.';
                break;
            default:
                echo 'Ocurrió un error desconocido.';
        }
        ?>
    </div>
<?php endif; ?>
          <form action="../controller/login_autor.php" method="POST">
            <div class="mb-3">
              <label for="email" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" id="email" name="email" required placeholder="autor@ejemplo.com">
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
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
