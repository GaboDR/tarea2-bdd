<?php
include('../db.php');
session_start();

if (!isset($_SESSION['autor_id'])) {
    header("Location: ../login/login_autor.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $articulo_id = $_POST['articulo_id'];
    $titulo = $_POST['titulo'];
    $resumen = $_POST['resumen'];
    $topicos = $_POST['topicos'] ?? [];
    $autores = $_POST['autores'] ?? [];
    $autor_contacto = $_POST['autor_contacto'];

    // Verificar que el artículo se puede modificar
    $check_stmt = $conexion->prepare("SELECT num_revisores FROM articulo WHERE id = ?");
    $check_stmt->bind_param("i", $articulo_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $articulo = $check_result->fetch_assoc();

    if (!$articulo || $articulo['num_revisores'] > 0) {
        $_SESSION['error'] = "Este artículo no se puede modificar porque ya está en revisión.";
        header("Location: ../autor/creararticulo.php");
        exit;
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // Actualizar artículo
        $stmt = $conexion->prepare("UPDATE articulo SET titulo = ?, resumen = ?, topico_principal = ?, autor_contacto = ? WHERE id = ?");
        $stmt->bind_param("sssii", $titulo, $resumen, $topicos[0], $autor_contacto, $articulo_id);
        $stmt->execute();

        // Eliminar tópicos extra anteriores
        $delete_topicos_stmt = $conexion->prepare("DELETE FROM topicos_extra WHERE id_articulo = ?");
        $delete_topicos_stmt->bind_param("i", $articulo_id);
        $delete_topicos_stmt->execute();

        // Insertar nuevos tópicos extra (si hay más de uno)
        if (count($topicos) > 1) {
            $stmt_topico = $conexion->prepare("INSERT INTO topicos_extra (id_articulo, topico_extra) VALUES (?, ?)");
            for ($i = 1; $i < count($topicos); $i++) {
                $stmt_topico->bind_param("is", $articulo_id, $topicos[$i]);
                $stmt_topico->execute();
            }
        }

        // Eliminar autores anteriores
        $delete_autores_stmt = $conexion->prepare("DELETE FROM autor_participante WHERE id_articulo = ?");
        $delete_autores_stmt->bind_param("i", $articulo_id);
        $delete_autores_stmt->execute();

        // Insertar nuevos autores (excepto el autor de contacto)
        $stmt_autor = $conexion->prepare("INSERT INTO autor_participante (id_articulo, id_autor) VALUES (?, ?)");
        foreach ($autores as $autor_id) {
            if ($autor_id != $autor_contacto) {
                $stmt_autor->bind_param("ii", $articulo_id, $autor_id);
                $stmt_autor->execute();
            }
        }

        $conexion->commit();
        header("Location: ../autor/ver_art.php");
        exit;
    } catch (Exception $e) {
        $conexion->rollback();

        $_SESSION['error'] = $e->getMessage();
        header("Location: ../autor/ver_art.php");
        exit;
    }
} else {
    echo "Acceso inválido.";
}
?>
