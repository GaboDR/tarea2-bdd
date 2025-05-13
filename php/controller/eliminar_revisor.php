<?php
include("../db.php");
session_start();

if (isset($_POST['btndelete'])){
    $id = $_POST['id'];

    $conexion->begin_transaction();
    try{
        $ArticuloRevisorQuery = "SELECT * FROM articulo_revisor WHERE ID_REVISOR = ?";
        $ArticuloRevisorStmt = $conexion->prepare($ArticuloRevisorQuery);
        $ArticuloRevisorStmt->bind_param("i", $id);
        $ArticuloRevisorStmt->execute();
        $result = $ArticuloRevisorStmt->get_result();

        if ($result->num_rows == 0){
            $deleteQueryAgregada = "DELETE FROM especialidad_agregada WHERE ID_REVISOR = ?";
            $deleteStmt = $conexion->prepare($deleteQueryAgregada);
            $deleteStmt->bind_param("i", $id);
            $deleteStmt->execute();
    
            $deleteQueryRevisor = "DELETE FROM revisor WHERE ID = ?";
            $deleteRevisorStmt = $conexion->prepare($deleteQueryRevisor);
            $deleteRevisorStmt->bind_param('i', $id);
            $deleteRevisorStmt->execute();

            $_SESSION['mensaje'] = "Revisor eliminado correctamente.";
            $_SESSION['correo'] = "Se ha enviado una notificación mediante correo electrónico al revisor eliminado.";
        } else {
            $_SESSION['mensaje'] = "El revisor seleccionado no puede ser eliminado ya posee una revision pendiente.";
        }

        $conexion->commit();

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