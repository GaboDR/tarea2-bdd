<?php include('includes/header.php'); ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Bienvenido al Sistema de Gestión de Artículos</h2>
    <div class="row justify-content-center">
        <!-- Login Card -->
        <div class="col-md-5">
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white">Iniciar Sesión</div>
                <div class="card-body text-center">
                    <a href="login/login_autor.php" class="btn btn-outline-primary btn-block mb-2">Autor</a>
                    <a href="login/login_revisor.php" class="btn btn-outline-primary btn-block mb-2">Revisor</a>
                    <a href="login/login_jefe.php" class="btn btn-outline-primary btn-block">Jefe de Comité</a>
                </div>
            </div>
        </div>
        <!-- Sign Up Card -->
        <div class="col-md-5">
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">Registrarse</div>
                <div class="card-body text-center">
                    <a href="singin/registro_autor.php" class="btn btn-outline-success btn-block mb-2">Autor</a>
                    <a href="singin/registro_revisor.php" class="btn btn-outline-success btn-block mb-2">Revisor</a>
                    <a href="singin/registro_jefe.php" class="btn btn-outline-success btn-block">Jefe de Comité</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
