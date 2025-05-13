<?php
include('../db.php');
session_start();

if (isset($_POST['btnappend'])){
    $rut = $_POST['rut'];
    $nombre = $_POST['name'];
    $email = $_POST['email'];
    $especialidades = $_POST['topicos'] ?? [];

    $conexion->begin_transaction();

    try{
        $queryAux = "SELECT ID FROM revisor WHERE rut = ? AND email = ?";
        $stmlAux = $conexion->prepare($queryAux);
        $stmlAux->bind_param("ss", $rut, $email);
        $stmlAux->execute();
        $cantidadRevisoresRepetidos = $stmlAux->get_result();
        $stmlAux->close();

        if ($cantidadRevisoresRepetidos->num_rows > 0){
            $_SESSION['error'] = "Ya existe un revisor con los datos ingresados.";
            header("Location: ../jefeRevisor/gestion_revisores.php");
            exit;
        }

        $contrasenaTemporal = "cambiarClave";
        $contrasenaTemporalHasheada = password_hash($contrasenaTemporal, PASSWORD_DEFAULT);

        if (count($especialidades) >= 1) {
            $query = "INSERT INTO revisor (rut, nombre, email, topico_especialidad, contrasena) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("sssss", $rut, $nombre, $email, $especialidades[0], $contrasenaTemporalHasheada);
            $stmt->execute();

            $revisor_id = $stmt->insert_id;

            if (count($especialidades) > 1) {
                $queryEspecialidad = "INSERT INTO especialidad_agregada (ID_REVISOR, ESPECIALIDAD_EXTRA) VALUES (?, ?)";
                $stmtEspecialidad  = $conexion->prepare($queryEspecialidad);

                foreach (array_slice($especialidades, 1) as $topico){
                    $stmtEspecialidad->bind_param("is", $revisor_id, $topico);
                    $stmtEspecialidad->execute();
                }
            }
        }
        

        $conexion->commit();

        $_SESSION['mensaje'] = "Revisor agregado satisfactoriamente.";
        $_SESSION['correo'] = "Se ha enviado un mail a la dirección de correo indicada.";
        header("Location: ../jefeRevisor/gestion_revisores.php");
    } catch (Exception $e) {
        $conexion->rollback();

        $_SESSION['error'] = "Error al agregar revisor: " . $e->getMessage();
        error_log("Error al modificar revisor: " . $e->getMessage());

        header("Location: ../jefeRevisor/gestion_revisores.php");
        exit;
    }
} else {
    echo var_dump($_POST);
    exit;

    $_SESSION['error'] = "Formulario no valido";
    header("Location: ../jefeRevisor/gestion_revisores.php");
    exit;
}
?>