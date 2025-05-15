<?php
include('../db.php');

if (empty($_POST['email']) || empty($_POST['password'])) {
    header("Location: ../login/login_jefe.php?error=campos_vacios");
    exit;
}

$email = $_POST['email'];
$contrasena = $_POST['password'];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Buscar al autor por su email
    $stmt = $conexion->prepare("SELECT revisor.rut, nombre, email, contrasena FROM revisor LEFT JOIN jefe_comite ON jefe_comite.rut = revisor.rut WHERE revisor.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificar contraseña
        if (password_verify($contrasena, $usuario['contrasena'])) {
            // Iniciar sesión
            session_start();
            $_SESSION['jefe_rut'] = $usuario['rut'];
            $_SESSION['jefe_nombre'] = $usuario['nombre'];

            header("Location: ../jefeRevisor/mainJefe.php"); // o donde quieras redirigir
            exit;
        } else {
            header("Location: ../login/login_jefe.php?error=contrasena_incorrecta");
            exit;
        }
    } else {
        header("Location: ../login/login_jefe.php?error=usuario_no_encontrado");
        exit;
    }

} catch (mysqli_sql_exception $e) {
    error_log("Error SQL en login_autor: " . $e->getMessage());
    header("Location: ../login/login_jefe.php?error=sql_error");
    exit;
}
?>