<?php
include('../db.php');
session_start();

if (!isset($_POST['autor_id'])) {
    header("Location: ../autor/perfil.php?error=datos_invalidos");
    exit;
}

$autor_id = $_POST['autor_id'];
$nombre = $_POST['nombre'];
$email = $_POST['email'];
$password = $_POST['password'];

try {
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE AUTOR SET nombre = ?, email = ?, contrasena = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $email, $hashed_password, $autor_id);
    } else {
        $stmt = $conexion->prepare("UPDATE AUTOR SET nombre = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $email, $autor_id);
    }

    $stmt->execute();

    // Actualizar datos en sesión si se cambió el nombre
    $_SESSION['autor_nombre'] = $nombre;

    header("Location: ../autor/perfil.php?exito=perfil_actualizado");
    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Error al actualizar perfil: " . $e->getMessage());
    header("Location: ../autor/perfil.php?error=sql_error");
    exit;
}
