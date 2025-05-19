<?php
include('includes/header.php'); 
if (!isset($_SESSION['autor_id']) && !isset($_SESSION['revisor_id']) && !isset($_SESSION['jefe_rut'])) {
    header('Location: sesiones.php'); // Redirigir al login si no está logueado
    exit;
}

include('db.php');

$articulos_por_pagina = 20;

$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

$offset = ($pagina_actual - 1) * $articulos_por_pagina;

$idAutor = isset($_GET['autor']) ? $_GET['autor'] : '';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$topico = isset($_GET['topico']) ? $_GET['topico'] : ''; 
$idRevisor = isset($_GET['revisor']) ? $_GET['revisor'] : '';

$condiciones = [];
$parametros = [];
$tipos = "";

// Construir condiciones según los filtros
if (!empty($idAutor)) {
    $condiciones[] = "FIND_IN_SET(?, id_autores)";
    $parametros[] = $idAutor;
    $tipos .= "s";
}
if (!empty($fecha)) {
    $condiciones[] = "fecha_envio = ?";
    $parametros[] = $fecha;
    $tipos .= "s";
}
if (!empty($topico)) {
    $condiciones[] = "topicos LIKE ?";
    $parametros[] = "%$topico%";
    $tipos .= "s";
}
if (!empty($idRevisor)) {
    $condiciones[] = "FIND_IN_SET(?, id_revisores)";
    $parametros[] = $idRevisor;
    $tipos .= "s";
}

// Agregar LIMIT y OFFSET
$tipos .= "ii";
$parametros[] = $articulos_por_pagina;
$parametros[] = $offset;

// Construir consulta final
$query = "SELECT * FROM vista_filtros_busqueda";
if (!empty($condiciones)) {
    $query .= " WHERE " . implode(" AND ", $condiciones);
}
$query .= " LIMIT ? OFFSET ?";

// Preparar y ejecutar
$stmt = $conexion->prepare($query);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container mt-5">
        <!-- Dashboard de artículos -->
        <h2>Búsqueda avanzada</h2>
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex">
                <p class="me-auto">A continuación, podrás ver los artículos disponibles en el sistema como tambien filtrar los resultados.</p>
                <form method="GET" action="">
    <div class="dropdown mb-3">
        <button type="submit" class="btn btn-primary">Limpiar filtro</button>
        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Filtros de búsqueda
        </button>
        <div class="dropdown-menu p-3" style="min-width: 300px;">
        <!-- Autor -->
        <div class="mb-2">
            <label for="autor" class="form-label">Autor</label>
            <select name="autor" id="autor" class="form-select">
                <option value="">--- Selecciona un autor ---</option>
                <?php
                $autores = $conexion->query("SELECT * FROM autor")->fetch_all(MYSQLI_ASSOC);
                foreach ($autores as $a) {
                    echo "<option value='" . htmlspecialchars($a['ID']) . "'>" . htmlspecialchars($a['NOMBRE']) . "</option>";
                }
                ?>
            </select>
        </div>

        <!-- Fecha -->
        <div class="mb-2">
            <label for="fecha" class="form-label">Fecha de envío</label>
            <input type="date" class="form-control" name="fecha" id="fecha">
        </div>

        <!-- Tópico -->
        <div class="mb-2">
            <label for="topico" class="form-label">Tópico</label>
            <select name="topico" class="form-select" id="topico">
                <option value="">--- Selecciona un topico ---</option>
                <?php 
                    $topicos = $conexion->query("SELECT * FROM topico_especialidad")->fetch_all(MYSQLI_ASSOC);
                    foreach ($topicos as $t) {
                        echo "<option value='" . htmlspecialchars($t['NOMBRE']) . "'>" . htmlspecialchars($t['NOMBRE']) . "</option>";
                    }
                ?>
            </select>
        </div>

        <!-- Revisor -->
        <div class="mb-2">
            <label for="revisor" class="form-label">Revisor</label>
            <select name="revisor" id="revisor" class="form-select">
                <option value="">--- Selecciona un revisor ---</option>
                <?php
                    $revisores = $conexion->query("SELECT * FROM revisor")->fetch_all(MYSQLI_ASSOC);
                    foreach ($revisores as $r) {
                        echo "<option value='" . htmlspecialchars($r['ID']) . "'>" . htmlspecialchars($r['NOMBRE']) . "</option>";
                    }
                ?>
            </select>
        </div>

        <!-- Botón de búsqueda dentro del dropdown -->
        <button type="submit" class="btn btn-primary w-100 mt-2">Buscar</button>
        </div>
        </div>
    </form>
            </div>

            <!-- Tabla de artículos -->
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Título</th>
                        <th>Resumen</th>
                        <th>Fecha de envío</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($resultado->num_rows > 0) {
                        while ($articulo = $resultado->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $articulo['titulo'] . "</td>";
                            echo "<td>" . $articulo['resumen'] . "</td>";
                            echo "<td>" . $articulo['fecha_envio'] . "</td>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>No se encontraron artículos.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('includes/footer.php')?>