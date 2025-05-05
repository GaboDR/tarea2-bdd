<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autor_id'])) {
    $autor_id = $_POST['autor_id'];

    // Prevenir borrado accidental de otro autor
    if ($autor_id != $_SESSION['autor_id']) {
        header("Location: ../autor/perfil_autor.php?error=no_autorizado");
        exit;
    }

    try {
        $stmt = $conexion->prepare("DELETE FROM AUTOR WHERE id = ?");
        $stmt->bind_param("i", $autor_id);
        $stmt->execute();

        // Cerrar sesión y redirigir
        session_destroy();
        header("Location: ../index.php?mensaje=perfil_eliminado");
        exit;

    } catch (mysqli_sql_exception $e) {
        error_log("Error al eliminar perfil: " . $e->getMessage());
        header("Location: ../autor/perfil_autor.php?error=sql_error");
        exit;
    }
} else {
    header("Location: ../autor/perfil_autor.php?error=peticion_invalida");
    exit;
}
?>
