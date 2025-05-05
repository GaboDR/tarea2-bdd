<?php include('../includes/header.php'); 
include('../db.php');
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Registro de Autor</h2>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        switch ($_GET['error']) {
            case 'campos_vacios':
                echo 'Por favor, completa todos los campos.';
                break;
            case 'rut_existente':
                echo 'Datos ya registrados.';
                break;
            case 'sql_error':
                echo 'Ocurrió un error al registrar. Intenta nuevamente.';
                break;
            default:
                echo 'Ocurrió un error desconocido.';
        }
        ?>
    </div>
<?php endif; ?>

    <form action="../controller/singin_autor.php" method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre completo</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
            <div class="invalid-feedback">Por favor, ingresa tu nombre.</div>
        </div>

        <div class="mb-3">
            <label for="rut" class="form-label">RUT</label>
            <input type="text" class="form-control" id="rut" name="rut" maxlength="10" required>
            <div class="invalid-feedback">Por favor, ingresa tu RUT.</div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="invalid-feedback">Ingresa un correo válido.</div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            <div class="invalid-feedback">Ingresa una contraseña válida.</div>

        </div>

        <div class="d-grid">
            <button class="btn btn-primary w-100" type="submit" name="registro_autor">Registrar</button>

        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
