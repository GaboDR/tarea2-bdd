<?php
include('../db.php');
session_start();

if (isset($_POST['btndelete'])){
    $idArticulo = $_POST['id_articulo'];
    $idRevisor = $_POST['id_revisor'];

    $conexion->begin_transaction();
    try {
        $query = "DELETE FROM articulo_revisor WHERE ID_ARTICULO = ? AND ID_REVISOR = ?";
        $queryStmt = $conexion->prepare($query);
        $queryStmt->bind_param("ii", $idArticulo, $idRevisor);
        $queryStmt->execute();

        $conexion->commit();

        $_SESSION['mensaje'] = "Se ha quitado el revisor indicado de la revision del articulo de manera satisfactoria.";
        header("Location: ../jefeRevisor/asignar_revisores.php");
    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['error'] = "Error al quitar el revisor: " . $e->getMessage();
        header("Location: ../jefeRevisor/asignar_revisores.php");
    }
}
?>