<?php
session_start();
include('../db.php');

$revisor_id = $_SESSION['revisor_id'];
$articulo_id = $_POST['articulo_id'];
$puntuacion_global = $_POST['puntuacion_global'];
$originalidad = $_POST['originalidad'];
$claridad = $_POST['claridad'];
$relevancia = $_POST['relevancia'];
$comentarios = $_POST['comentarios'];

// Obtener el ID de ARTICULO_REVISOR desde la base de datos
$query = "SELECT id FROM ARTICULO_REVISOR WHERE id_revisor = ? AND id_articulo = ?";
$stmt_lookup = $conexion->prepare($query);
$stmt_lookup->bind_param("ii", $revisor_id, $articulo_id);
$stmt_lookup->execute();
$stmt_lookup->bind_result($articulo_revisor_id);
$stmt_lookup->fetch();
$stmt_lookup->close();

// Ahora sí llamar al procedimiento con 6 parámetros
$stmt = $conexion->prepare("CALL sp_guardar_revision(?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisiii", $articulo_revisor_id, $puntuacion_global, $comentarios, $originalidad, $claridad, $relevancia);


if ($stmt->execute()) {
    $_SESSION['exito'] = "Revisión guardada correctamente.";
} else {
    $_SESSION['error'] = "Error al guardar revisión.";
}

$stmt->close();
$conexion->close();

header("Location: ../revisor/articulos_revisar.php");
exit();
?>
