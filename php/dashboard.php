<?php 
include('includes/header.php'); 
if (!isset($_SESSION['autor_id']) && !isset($_SESSION['revisor_id']) && !isset($_SESSION['jefe_id'])) {
    header('Location: sesiones.php'); // Redirigir al login si no está logueado
    exit;
}

include('db.php');

// Establecer cuántos artículos mostrar por página
$articulos_por_pagina = 10;

// Obtener el número de página actual desde la URL (si no está definido, comenzamos con la página 1)
$página_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Calcular el desplazamiento (OFFSET) para la consulta SQL
$offset = ($página_actual - 1) * $articulos_por_pagina;

// Obtener el término de búsqueda, si existe
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Si hay un término de búsqueda, hacer una consulta con LIKE
if (!empty($buscar)) {
    $query = "SELECT * FROM articulo WHERE TITULO LIKE ? LIMIT ? OFFSET ?";
    $buscar_param = "%" . $buscar . "%";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("sii", $buscar_param, $articulos_por_pagina, $offset);
} else {
    // Si no hay búsqueda, mostrar todos los artículos
    $query = "SELECT * FROM articulo LIMIT ? OFFSET ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $articulos_por_pagina, $offset);
}

$stmt->execute();
$resultado = $stmt->get_result();

// Obtener el número total de artículos para calcular las páginas
if (!empty($buscar)) {
    $query_total = "SELECT COUNT(*) FROM articulo WHERE TITULO LIKE ?";
    $stmt_total = $conexion->prepare($query_total);
    $stmt_total->bind_param("s", $buscar_param);
} else {
    $query_total = "SELECT COUNT(*) FROM articulo";
    $stmt_total = $conexion->prepare($query_total);
}

$stmt_total->execute();
$resultado_total = $stmt_total->get_result();
$total_articulos = $resultado_total->fetch_row()[0];

// Calcular el número total de páginas
$total_paginas = ceil($total_articulos / $articulos_por_pagina);
?>

<!-- Contenedor principal con Bootstrap -->
<div class="container mt-5">
    <!-- Barra de búsqueda -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form action="dashboard.php" method="GET" class="d-flex">
                <input type="text" class="form-control" name="buscar" placeholder="Buscar artículo por título" value="<?= htmlspecialchars($buscar); ?>" required>
                <button class="btn btn-primary ms-2" type="submit">Buscar</button>
            </form>
        </div>
    </div>

    <!-- Dashboard de artículos -->
    <div class="row">
        <div class="col-md-12">
            <h2>Dashboard de Artículos</h2>
            <p>A continuación, podrás ver los artículos disponibles en el sistema.</p>

            <!-- Tabla de artículos -->
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Fecha de envío</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($resultado->num_rows > 0) {
                        while ($articulo = $resultado->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $articulo['ID'] . "</td>";
                            echo "<td>" . $articulo['TITULO'] . "</td>";
                            echo "<td>" . $articulo['FECHA_ENVIO'] . "</td>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>No se encontraron artículos.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <!-- Página anterior -->
                    <li class="page-item <?= $página_actual <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?= $página_actual - 1; ?>&buscar=<?= htmlspecialchars($buscar); ?>" tabindex="-1">Anterior</a>
                    </li>

                    <!-- Páginas numeradas -->
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i == $página_actual ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?= $i; ?>&buscar=<?= htmlspecialchars($buscar); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Página siguiente -->
                    <li class="page-item <?= $página_actual >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?= $página_actual + 1; ?>&buscar=<?= htmlspecialchars($buscar); ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>

        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
