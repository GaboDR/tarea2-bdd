<?php
include('../db.php');
session_start();

if (isset($_POST['btnasign'])) {
    $idRevisor = $_POST['id_revisor'];
    $especialidades = $_POST['especialidad'];
    $idArticulo = $_POST['eleccionAsign'];

    $conexion->begin_transaction();

    try {
        $query = "SELECT a.nombre, a.id, a.rut
                    FROM autor a
                    WHERE a.id = (SELECT autor_contacto FROM articulo WHERE id = ?)

                    UNION

                    SELECT a.nombre, a.id, a.rut
                    FROM autor a
                    JOIN autor_participante ap ON a.id = ap.id_autor
                    WHERE ap.id_articulo = ?";
        $queryStmt = $conexion->prepare($query);
        $queryStmt->bind_param("ii", $idArticulo, $idArticulo);
        $queryStmt->execute();
        $autores = $queryStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $queryAux = "SELECT r.rut, r.id, r.nombre FROM revisor r WHERE r.id = ?";
        $queryAuxStmt = $conexion->prepare($queryAux);
        $queryAuxStmt->bind_param("i", $idRevisor);
        $queryAuxStmt->execute();
        $revisor = $queryAuxStmt->get_result()->fetch_assoc();

        $coincidencia = false;

        foreach ($autores as $autor) {
            if ($autor['rut'] === $revisor['rut']) {
                $coincidencia = true;
                break;
            }
        }

        if(!$coincidencia){
            $insertQuery = "INSERT INTO articulo_revisor (ID_ARTICULO, ID_REVISOR) VALUES (?, ?)";
            $insertStmt = $conexion->prepare($insertQuery);
            $insertStmt->bind_param("ii", $idArticulo, $idRevisor);
            $insertStmt->execute();

            $conexion->commit();

            $_SESSION['mensaje'] = "Se ha asignado correctamente el revisor al articulo indicado.";
            header("Location: ../jefeRevisor/asignar_revisores.php");
        } else {
            throw new Exception("No se puede asignar la revisión del articulo al revisor seleccionado debido a que este es autor del articulo.");
        }
    } catch (Exception $e) {
        $conexion->rollback();

        $_SESSION['error'] = "No ha sido posible realizar la asignación indicada: " . $e->getMessage();
        header("Location: ../jefeRevisor/asignar_revisores.php");
    }
}
?>