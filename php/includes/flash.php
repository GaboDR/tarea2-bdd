<?php
function mostrar_mensaje_sesion($tipo = 'error') {
    if (isset($_SESSION[$tipo])) {
        $clase = match ($tipo) {
            'exito' => 'success',
            'info' => 'info',
            'error' => 'danger',
            default => 'secondary',
        };

        echo '<div class="alert alert-' . $clase . ' alert-dismissible fade show mt-2" role="alert">';
        echo htmlspecialchars($_SESSION[$tipo]);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
        echo '</div>';
        
        unset($_SESSION[$tipo]);
    }
}
?>
