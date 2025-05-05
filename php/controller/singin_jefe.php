<?php
include('../db.php'); 

$clave_accesso = 250504;

if (empty($_POST['nombre']) || empty($_POST['rut']) || empty($_POST['email']) || empty($_POST['contrasena']) || empty($_POST['clave'])) {
    header("Location: ../singin/registro_jefe.php?error=campos_vacios");
    exit;
}

$nombre = $_POST['nombre'];
$rut = $_POST['rut'];
$email = $_POST['email'];
$contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
$clave = $_POST['clave'];

if ($clave != $clave_accesso) {
    header("Location: ../singin/registro_jefe.php?error=acceso_invalido");
    exit;
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $stmt = $conexion->prepare("INSERT INTO JEFE_COMITE (rut, nombre, email, contrasena) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $rut, $nombre, $email, $contrasena);
    $stmt->execute();

    header("Location: ../index.php");
    exit;
} catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        header("Location: ../singin/registro_jefe.php?error=rut_existente");
    } else {
        header("Location: ../singin/registro_jefe.php?error=sql_error");
    }
    exit;
}
?>