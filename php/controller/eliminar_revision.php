<?php
session_start();
include('../db.php');

$revision_id = $_POST['revision_id'];

$stmt = $conexion->prepare("CALL sp_eliminar_revision(?)");
$stmt->bind_param("i", $revision_id);

if ($stmt->execute()) {
    $_SESSION['exito'] = "Revisión eliminada correctamente.";
} else {
    $_SESSION['error'] = "Error al eliminar revisión.";
}

$stmt->close();
$conexion->close();

header("Location: ../revisor/articulos_revisar.php");
exit();
?>
