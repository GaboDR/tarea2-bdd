<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autor_id'])) {
    $autor_id = $_POST['autor_id'];

    // Prevenir borrado accidental de otro autor
    if ($autor_id != $_SESSION['autor_id']) {
        $_SESSION['error'] = 'Acceso no autorizado';
        header("Location: ../autor/perfil.php");
        exit;
    }

    try {
        $stmt = $conexion->prepare("DELETE FROM AUTOR WHERE id = ?");
        $stmt->bind_param("i", $autor_id);
        $stmt->execute();

        // Cerrar sesión y redirigir
        session_destroy();
        header("Location: ../sesiones.php");
        exit;

    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = "No puedes eliminar este perfil";
        header("Location: ../autor/perfil.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Peticion invalida";
    header("Location: ../autor/perfil.php");
    exit;
}
?>
