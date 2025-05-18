<?php
include("../db.php");
session_start();

if (!isset($_SESSION['revisor_id']) || !isset($_POST['revisor_id'])) {
    header("Location: ../login/login_revisor.php");
    exit;
}

$revisor_id_sesion = $_SESSION['revisor_id'];
$revisor_id_formulario = $_POST['revisor_id'];

// Seguridad adicional: validar que el revisor que se elimina es el que está logueado
if ($revisor_id_sesion != $revisor_id_formulario) {
    $_SESSION['error'] = "Acción no permitida.";
    header("Location: ../revisor/perfil.php");
    exit;
}

$conexion->begin_transaction();

try {
    // Verificar si tiene revisiones pendientes
    $stmt = $conexion->prepare("SELECT 1 FROM articulo_revisor WHERE ID_REVISOR = ?");
    $stmt->bind_param("i", $revisor_id_sesion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "No puedes eliminar tu perfil porque tienes revisiones pendientes.";
        header("Location: ../revisor/perfil.php");
        exit;
    }

    // Eliminar especialidades agregadas
    $stmt = $conexion->prepare("DELETE FROM especialidad_agregada WHERE ID_REVISOR = ?");
    $stmt->bind_param("i", $revisor_id_sesion);
    $stmt->execute();

    // Eliminar cuenta del revisor
    $stmt = $conexion->prepare("DELETE FROM revisor WHERE ID = ?");
    $stmt->bind_param("i", $revisor_id_sesion);
    $stmt->execute();

    $conexion->commit();

    // Cerrar sesión
    session_destroy();

    // Redirigir al login con mensaje de éxito
    header("Location: ../sesiones.php ");
    exit;

} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al eliminar perfil del revisor: " . $e->getMessage());

    $_SESSION['error'] = "Error al eliminar perfil. Intenta nuevamente.";
    header("Location: ../revisor/perfil.php");
    exit;
}
