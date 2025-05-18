<?php
include('../db.php');
session_start();

if (isset($_POST['btnchange'])){
    $jefe_rut = $_POST['jefe_rut'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $conexion->begin_transaction();

    try{
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE REVISOR SET nombre = ?, email = ?, contrasena = ? WHERE rut = ?");
            $stmt->bind_param("ssss", $nombre, $email, $hashed_password, $jefe_rut);
        } else {
            $stmt = $conexion->prepare("UPDATE REVISOR SET nombre = ?, email = ? WHERE rut = ?");
            $stmt->bind_param("sss", $nombre, $email, $jefe_rut);
        }

        $stmt->execute();

        $conexion->commit();

        $_SESSION['jefe_nombre'] = $nombre;

        $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente.";
        header("Location: ../jefeRevisor/mainJefe.php");
    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['mensaje_error'] = "Ocurrió un error al actualizar el perfil.";
        header("Location: ../jefeRevisor/mainJefe.php");
    }
}
?>