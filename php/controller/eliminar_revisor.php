<?php
include("../db.php");
session_start();

if (isset($_POST['btndelete'])){
    $id = $_POST['id'];

    $conexion->begin_transaction();
    try{
        $deleteQueryAgregada = "DELETE FROM especialidad_agregada WHERE ID_REVISOR = ?";
        $deleteStmt = $conexion->prepare($deleteQueryAgregada);
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();

        $deleteArticuloRevisorQuery = "DELETE FROM articulo_revisor WHERE ID_REVISOR = ?";
        $deleteArticuloRevisorStmt = $conexion->prepare($deleteArticuloRevisorQuery);
        $deleteArticuloRevisorStmt->bind_param("i", $id);
        $deleteArticuloRevisorStmt->execute();

        $deleteQueryRevisor = "DELETE FROM revisor WHERE ID = ?";
        $deleteRevisorStmt = $conexion->prepare($deleteQueryRevisor);
        $deleteRevisorStmt->bind_param('i', $id);
        $deleteRevisorStmt->execute();

        $conexion->commit();

        $_SESSION['mensaje'] = "Revisor eliminado correctamente";
        header("Location: ../jefeRevisor/gestion_revisores.php");
        exit;
    } catch (Exception $e){
        $conexion->rollback();
        
        $_SESSION['error'] = "Error al eliminar revisor: " . $e->getMessage();
        error_log("Error al modificar revisor: " . $e->getMessage());
        
        header("Location: ../jefeRevisor/gestion_revisores.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Formulario no válido";
    header("Location: ../jefeRevisor/gestion_revisores.php");
    exit;
}
?>