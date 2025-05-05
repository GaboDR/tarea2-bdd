<?php
# no se ha probado nada
include('../db.php');
session_start();

if (!isset($_SESSION['autor_id'])) {
    die("Acceso no autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = $_POST['titulo'];
    $resumen = $_POST['resumen'];
    $autor_contacto = $_POST['autor_contacto'];
    $topicos = $_POST['topicos'] ?? [];
    $autores = $_POST['autores'] ?? [];

    // Asegurarse que el autor logueado esté incluido (aunque esté deshabilitado en el form)
    $autor_logueado = $_SESSION['autor_id'];
    if (!in_array($autor_logueado, $autores)) {
        $autores[] = $autor_logueado;
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // Insertar artículo
        $stmt = $conexion->prepare("INSERT INTO articulo (titulo, resumen, autor_contacto, topico_principal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $titulo, $resumen, $autor_contacto, $topicos[0]);
        $stmt->execute();
        $articulo_id = $conexion->insert_id;

        // Insertar tópicos
        if (count($topicos) > 1) {
            $stmt_topico = $conexion->prepare("INSERT INTO topicos_extra (id_articulo, topico_extra) VALUES (?, ?)");
            for ($i = 1; $i < count($topicos); $i++) {
                $stmt_topico->bind_param("is", $articulo_id, $topicos[$i]);
                $stmt_topico->execute();
            }
        }

        // Insertar autores
        $stmt_autor = $conexion->prepare("INSERT INTO AUTOR_PARTICIPANTE (ID_ARTICULO, ID_AUTOR) VALUES (?, ?)");

        foreach ($autores as $autor_id) {
            if ($autor_id != $autor_contacto) {  // Evitar insertar al autor de contacto
                $stmt_autor->bind_param("ii", $articulo_id, $autor_id);
                $stmt_autor->execute();
            }
        }

        // Confirmar transacción
        $conexion->commit();

        header("Location: ../autor/ver_art.php");
        exit;
    } catch (Exception $e) {
        $conexion->rollback();
        die("Error al guardar el artículo: " . $e->getMessage());
    }
} else {
    echo "Acceso inválido.";
}
?>
