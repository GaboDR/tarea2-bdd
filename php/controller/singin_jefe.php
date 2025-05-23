<?php
include('../db.php'); 
session_start();

$clave_accesso = 250504;

if (empty($_POST['nombre']) || empty($_POST['rut']) || empty($_POST['email']) || empty($_POST['contrasena']) || empty($_POST['clave']) || empty($_POST['topicos'])) {
    header("Location: ../singin/registro_jefe.php?error=campos_vacios");
    exit;
}

$nombre = $_POST['nombre'];
$rut = $_POST['rut'];
$email = $_POST['email'];
$contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
$clave = $_POST['clave'];
$topicos = $_POST['topicos'];

$topico1 = $topicos[0];

if ($clave != $clave_accesso) {
    $_SESSION['error'] = 'Acceso invalido';
    header("Location: ../singin/registro_jefe.php");
    exit;
} elseif (!is_array($topicos) || count($topicos) == 0){
    $_SESSION['error'] = 'Topicos no seleccionados';
    header("Location: ../singin/registro_jefe.php");
    exit;
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $stmtRevisor = $conexion->prepare("INSERT INTO REVISOR (rut, nombre, email, contrasena, topico_especialidad) VALUES (?, ?, ?, ?, ?)");
    $stmtRevisor->bind_param("sssss", $rut, $nombre, $email, $contrasena, $topico1);
    $stmtRevisor->execute();
    
    $revisor_id = $stmtRevisor->insert_id;
    
    $intermediateStmt = $conexion->prepare("INSERT INTO especialidad_agregada (id_revisor, especialidad_extra) VALUES (?, ?)");
    
    for ($i = 1; $i < count($topicos); $i++) {
        $topico_i = $topicos[$i];
        $intermediateStmt->bind_param("is", $revisor_id, $topico_i);
        $intermediateStmt->execute();
    }

    $stmt = $conexion->prepare("INSERT INTO JEFE_COMITE (rut) VALUES (?)");
    $stmt->bind_param("s", $rut);
    $stmt->execute();

    header("Location: ../sesiones.php");
    exit;
} catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        $_SESSION['error'] = 'Datos existentes';
        header("Location: ../singin/registro_jefe.php");
    } else {
        $_SESSION['error'] = 'Datos invalidos';
        header("Location: ../singin/registro_jefe.php");
    }
    exit;
}
?>