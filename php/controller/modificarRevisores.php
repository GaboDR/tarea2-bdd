<?php
include('../db.php');
session_start();

if (isset($_POST['btnchangedata'])){
    $nombre = $_POST['name'];
    $rut = $_POST['rut'];
    $email = $_POST['email'];
    $especialidades = $_POST['topicos'] ?? [];
    $id = $_POST['id'];

    // Iniciar una transacción
    $conexion->begin_transaction();
    
    try {
        // Paso 1: Actualizar la especialidad principal
        if (count($especialidades) >= 1) {
            $query = "UPDATE revisor SET rut = ?, nombre = ?, email = ?, topico_especialidad = ? WHERE id = ?";
            $stml = $conexion->prepare($query);
            $stml->bind_param("ssssi", $rut, $nombre, $email, $especialidades[0], $id);
            $stml->execute();
        }

        // Paso 2: Eliminar las especialidades anteriores (incluyendo la principal)
        $deleteQuery = "DELETE FROM especialidad_agregada WHERE ID_REVISOR = ?";
        $deleteStmt = $conexion->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();

        // Paso 3: Insertar las nuevas especialidades adicionales (si las hay)
        if (count($especialidades) > 1) { // Si hay más de una especialidad seleccionada
            $insertQuery = "INSERT INTO especialidad_agregada (id_revisor, especialidad_extra) VALUES (?, ?)";
            $insertStmt = $conexion->prepare($insertQuery);
    
            foreach (array_slice($especialidades, 1) as $topico) {
                $insertStmt->bind_param("is", $id, $topico);
                $insertStmt->execute();
            }
        }

        // Confirmar la transacción solo si todo fue exitoso
        $conexion->commit();
        
        // Guardar mensaje de éxito
        $_SESSION['mensaje'] = "Revisor actualizado correctamente";
        // Redirigir a la página de gestión
        header("Location: ../jefeRevisor/gestion_revisores.php");
        exit;

    } catch (Exception $e){
        // Si ocurre un error, revertir la transacción
        $conexion->rollback();
        
        // Guardar el mensaje de error en la sesión
        $_SESSION['error'] = "Error al modificar revisor: " . $e->getMessage();
        error_log("Error al modificar revisor: " . $e->getMessage());
        
        // Redirigir a la página de gestión
        header("Location: ../jefeRevisor/gestion_revisores.php");
        exit;
    }
} else {
    echo var_dump($_POST);
    exit;
    // Si no es un formulario válido, mostrar un mensaje de error
    $_SESSION['error'] = "Formulario no válido";
    header("Location: ../jefeRevisor/gestion_revisores.php");
    exit;
}
