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

        $query = "SELECT * FROM jefe_comite JOIN revisor ON revisor.rut = jefe_comite.rut WHERE revisor.id = ?";
        $queryStmt = $conexion->prepare($query);
        $queryStmt->bind_param("i", $id);
        $queryStmt->execute();
        $resultQuery = $queryStmt->get_result();

        if ($resultQuery->num_rows == 0){
            if ($result->num_rows == 0){
                $deleteQueryRevisor = "DELETE FROM revisor WHERE ID = ?";
                $deleteRevisorStmt = $conexion->prepare($deleteQueryRevisor);
                $deleteRevisorStmt->bind_param('i', $id);
                $deleteRevisorStmt->execute();
    
                $_SESSION['mensaje'] = "Revisor eliminado correctamente.";
                $_SESSION['correo'] = "Se ha enviado una notificación mediante correo electrónico al revisor eliminado.";
            } else {
                $_SESSION['mensaje'] = "El revisor seleccionado no puede ser eliminado ya que posee revisiones pendientes.";
            }
        } else {
            throw new Exception("No es posible eliminar a un jefe de comité.");
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