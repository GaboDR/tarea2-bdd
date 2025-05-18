<?php
include('../db.php');
session_start();

if (!isset($_SESSION['revisor_id'])) {
    header("Location: ../login/login_revisor.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $revisor_id = $_POST['revisor_id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $topicos = $_POST['topicos'] ?? [];

    if (count($topicos) === 0) {
        $_SESSION['mensaje_error'] = "Ocurrió un error al actualizar el perfil.";
        header("Location: ../revisor/perfil.php");
        exit;
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // Actualizar revisor con o sin contraseña
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE REVISOR SET nombre = ?, email = ?, contrasena = ?, topico_especialidad = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nombre, $email, $hashed_password, $topicos[0], $revisor_id);
        } else {
            $stmt = $conexion->prepare("UPDATE REVISOR SET nombre = ?, email = ?, topico_especialidad = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $email, $topicos[0], $revisor_id);
        }

        $stmt->execute();

        // Eliminar especialidades adicionales anteriores
        $stmt_delete = $conexion->prepare("DELETE FROM ESPECIALIDAD_AGREGADA WHERE ID_REVISOR = ?");
        $stmt_delete->bind_param("i", $revisor_id);
        $stmt_delete->execute();

        // Insertar nuevas especialidades adicionales si hay más de una
        if (count($topicos) > 1) {
            $stmt_insert = $conexion->prepare("INSERT INTO ESPECIALIDAD_AGREGADA (ID_REVISOR, ESPECIALIDAD_EXTRA) VALUES (?, ?)");
            for ($i = 1; $i < count($topicos); $i++) {
                $especialidad_extra = $topicos[$i];
                $stmt_insert->bind_param("is", $revisor_id, $especialidad_extra);
                $stmt_insert->execute();
            }
        }

        $conexion->commit();

        // Actualizar datos en sesión
        $_SESSION['autor_nombre'] = $nombre;

        $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente.";
        header("Location: ../revisor/perfil.php");
        exit;

    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['mensaje_error'] = "Ocurrió un error al actualizar el perfil.";
        header("Location: ../revisor/perfil.php");
        exit;
    }
} else {
    echo "Acceso inválido.";
}
?>
