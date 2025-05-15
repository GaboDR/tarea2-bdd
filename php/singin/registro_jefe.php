<?php include('../includes/header.php'); 
include('../db.php');

// Consulta de tópicos
$query = "SELECT nombre FROM topico_especialidad";
$result = mysqli_query($conexion, $query);
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Registro de Jefe de Comite</h2>
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
            case 'acesso_invalido':
                echo 'Permiso de clave rechazado.';
                break;
            case 'sin_topicos':
                echo 'Debes seleccionar al menos un tópico de especialidad.';
                break;
            default:
                echo 'Ocurrió un error desconocido.';
        }
        ?>
    </div>
<?php endif; ?>

    <form action="../controller/singin_jefe.php" method="POST" class="needs-validation" novalidate>
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

        <div class="mb-3">
            <label for="password" class="form-label">Clave de acceso</label>
            <input type="password" class="form-control" id="clave" name="clave" required>
            <div class="invalid-feedback">Ingresa una contraseña válida.</div>
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
            <button class="btn btn-primary w-100" type="submit" name="registro_jefe">Registrar</button>

        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
