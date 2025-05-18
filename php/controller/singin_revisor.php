<?php
include('../db.php'); 
session_start();

if (empty($_POST['nombre']) || empty($_POST['rut']) || empty($_POST['email']) || empty($_POST['contrasena']) || empty($_POST['topicos'])) {
    header("Location: ../singin/registro_revisor.php?error=campos_vacios");
    exit;
}

$nombre = $_POST['nombre'];
$rut = $_POST['rut'];
$email = $_POST['email'];
$contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
$topicos = $_POST['topicos'];

$topico1 = $topicos[0];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $stmt = $conexion->prepare("INSERT INTO REVISOR (rut, nombre, email, contrasena, topico_especialidad) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $rut, $nombre, $email, $contrasena, $topico1);
    $stmt->execute();

    $revisor_id = $stmt->insert_id;

    $intermedia_stmt = $conexion->prepare("INSERT INTO especialidad_agregada (id_revisor, especialidad_extra) VALUES (?, ?)");

    for ($i = 1; $i < count($topicos); $i++) {
        $topico_i = $topicos[$i];
        $intermedia_stmt->bind_param("is", $revisor_id, $topico_i);
        $intermedia_stmt->execute();
    }
    $_SESSION['revisor_id'] = $revisor_id;

    header("Location: ../revisor/perfil.php");
    exit;
} catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        $_SESSION['error'] = "Datos ya existentes.";

        header("Location: ../singin/registro_revisor.php");
    } else {
        error_log("Error SQL: " . $e->getMessage()); 
        $_SESSION['error'] = "Datos invalidos";

        header("Location: ../singin/registro_revisor.php");
    }
    exit;
}
?>