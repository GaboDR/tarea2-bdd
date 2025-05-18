<?php
session_start();
include('../db.php');

$revision_id = $_POST['revision_id'];
$puntuacion_global = $_POST['puntuacion_global'];
$originalidad = $_POST['originalidad'];
$claridad = $_POST['claridad'];
$relevancia = $_POST['relevancia'];
$comentarios = $_POST['comentarios'];

$stmt = $conexion->prepare("CALL actualizar_revision(?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisiii", $revision_id, $puntuacion_global, $comentarios, $originalidad,$claridad, $relevancia);

if ($stmt->execute()) {
    $_SESSION['exito'] = "Revisión actualizada correctamente.";
} else {
    $_SESSION['error'] = "Error al actualizar revisión.";
}

$stmt->close();
$conexion->close();

header("Location: ../revisor/articulos_revisar.php");
exit();
?>



