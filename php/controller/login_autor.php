<?php
include('../db.php');

if (empty($_POST['email']) || empty($_POST['password'])) {
    header("Location: ../login/login_autor.php?error=campos_vacios");
    exit;
}

$email = $_POST['email'];
$contrasena = $_POST['password'];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Buscar al autor por su email
    $stmt = $conexion->prepare("SELECT id, nombre, email, contrasena FROM AUTOR WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificar contraseña
        if (password_verify($contrasena, $usuario['contrasena'])) {
            // Iniciar sesión
            session_start();
            $_SESSION['autor_id'] = $usuario['id'];
            $_SESSION['autor_nombre'] = $usuario['nombre'];

            header("Location: ../autor/perfil.php"); // o donde quieras redirigir
            exit;
        } else {
            header("Location: ../login/login_autor.php?error=contrasena_incorrecta");
            exit;
        }
    } else {
        header("Location: ../login/login_autor.php?error=usuario_no_encontrado");
        exit;
    }

} catch (mysqli_sql_exception $e) {
    error_log("Error SQL en login_autor: " . $e->getMessage());
    header("Location: ../login/login_autor.php?error=sql_error");
    exit;
}
?>