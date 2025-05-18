<?php
include('../db.php'); 
session_start();

if (empty($_POST['nombre']) || empty($_POST['rut']) || empty($_POST['email']) || empty($_POST['contrasena'])) {
    header("Location: ../singin/registro_autor.php?error=campos_vacios");
    exit;
}

$nombre = $_POST['nombre'];
$rut = $_POST['rut'];
$email = $_POST['email'];
$contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $stmt = $conexion->prepare("INSERT INTO AUTOR (rut, nombre, email, contrasena) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $rut, $nombre, $email, $contrasena);
    $stmt->execute();

    // Obtener el ID insertado
    $autor_id = $conexion->insert_id;

    // Iniciar sesión y guardar datos
    session_start();
    $_SESSION['autor_id'] = $autor_id;
    $_SESSION['autor_nombre'] = $nombre;

    header("Location: ../autor/perfil.php");
    exit;

} catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        $_SESSION['error'] = "datos existentes";
        header("Location: ../singin/registro_autor.php");
    } else {
        $_SESSION['error'] = "Datos invalidos";
        header("Location: ../singin/registro_autor.php");
    }
    exit;
}

?>