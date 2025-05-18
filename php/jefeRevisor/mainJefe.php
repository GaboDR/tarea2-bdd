<title>Página Principal</title>

<?php 
include("../includes/header.php");
include("../db.php");

if (!isset($_SESSION['jefe_rut'])){
    header("Location: ../login/login_jefe.php");
    exit;
}

$jefe_rut = $_SESSION['jefe_rut'];

$stmt = $conexion->prepare("SELECT nombre, email FROM revisor WHERE rut = ?");
$stmt->bind_param("s", $jefe_rut);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $jefe = $resultado->fetch_assoc();
} else {
    echo "Error al obtener los datos del perfil.";
    var_dump($resultado);
    exit;
}
?>

<div class="container mt-5">
    <div class="card show">
        <div class="card-header bg-secondary text-white">
            <h3 class="mb-0">Perfil Jefe de Comité</h3>
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
                <strong>Nombre:</strong> <?= htmlspecialchars($jefe['nombre']) ?>
            </div>
            <div class="mb-3">
                <strong>Email:</strong> <?= htmlspecialchars($jefe['email']) ?>
            </div>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editarModal">
                Editar Perfil
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="../controller/editar_jefe.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarModalLabel">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="jefe_rut" value="<?= $jefe_rut ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($jefe['nombre']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($jefe['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" name="btnchange">Guardar Cambios</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>