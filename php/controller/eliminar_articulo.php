<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articulo_id = $_POST['id'];

    // Verificar que el artículo tenga 0 revisores antes de eliminar
    $stmt_check = $conexion->prepare("SELECT num_revisores FROM articulo WHERE id = ?");
    $stmt_check->bind_param("i", $articulo_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $articulo = $result->fetch_assoc();

    if (!$articulo || $articulo['num_revisores'] > 0) {
        header("Location: ../autor/articulos.php?error=no_se_puede_eliminar");
        exit;
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // Eliminar tópicos extra
        $stmt_topico = $conexion->prepare("DELETE FROM topicos_extra WHERE id_articulo = ?");
        $stmt_topico->bind_param("i", $articulo_id);
        $stmt_topico->execute();

        // Eliminar autores participantes
        $stmt_autor = $conexion->prepare("DELETE FROM autor_participante WHERE id_articulo = ?");
        $stmt_autor->bind_param("i", $articulo_id);
        $stmt_autor->execute();

        // Eliminar artículo
        $stmt_articulo = $conexion->prepare("DELETE FROM articulo WHERE id = ?");
        $stmt_articulo->bind_param("i", $articulo_id);
        $stmt_articulo->execute();

        $conexion->commit();
        header("Location: ../autor/ver_art.php");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conexion->rollback();
        error_log("Error al eliminar artículo: " . $e->getMessage());
        header("Location: ../autor/articulos.php?error=sql_error");
        exit;
    }
} else {
    header("Location: ../autor/articulos.php?error=peticion_invalida");
    exit;
}
?>
