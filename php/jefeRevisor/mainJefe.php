<title>Página Principal</title>

<?php 
include("../includes/header.php");
include("../db.php");

if (!isset($_SESSION['jefe_rut'])){
    header("Location: ../login/login_jefe.php");
    exit;
}
?>

<div class="container mt-5">
    <h2>Bienvenido de vuelta, <?= htmlspecialchars($_SESSION['jefe_nombre']) ?></h2>
</div>

<?php include('../includes/footer.php'); ?>