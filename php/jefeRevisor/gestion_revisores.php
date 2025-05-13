<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar revisores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="../../css/jefeRevisor/gestionRevisores.css">
</head>

<?php
include('../includes/header.php'); 
include('../db.php');

if (!isset($_SESSION['jefe_id'])){
    header("Location: ../login/login_jefe.php");
    exit;
}

$revisoresPorPagina = 10;

$actualPage = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Calcular el desplazamiento (OFFSET) para la consulta SQL
$offset = ($actualPage - 1) * $revisoresPorPagina;

// Obtener el término de búsqueda, si existe
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

$query_topicos = "SELECT nombre FROM topico_especialidad";
$result_topicos = $conexion->query($query_topicos);

// Si hay un término de búsqueda, hacer una consulta con LIKE
if (!empty($buscar)) {
    $query = "SELECT
    id,
	nombre,
    rut,
    email,
    GROUP_CONCAT(CONCAT(topico_especialidad, ', ', IFNULL(aux, '')) SEPARATOR ', ') AS especialidadesRevisor
    FROM (
        SELECT
            id,
            nombre,
            rut,
            email,
            topico_especialidad,
            GROUP_CONCAT(ESPECIALIDAD_EXTRA SEPARATOR ', ') AS aux
        FROM 
            revisor
        LEFT JOIN
            especialidad_agregada ON revisor.ID = especialidad_agregada.ID_REVISOR
        GROUP BY 
            ID_REVISOR
    ) as subQuery WHERE nombre LIKE ? GROUP BY RUT LIMIT ? OFFSET ?;";
    $buscar_param = "%" . $buscar . "%";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("sii", $buscar_param, $revisoresPorPagina, $offset);
} else {
    // Consulta cuando no hay búsqueda
    $query = "SELECT
        id,
        nombre,
        rut,
        email,
        GROUP_CONCAT(CONCAT(topico_especialidad, ', ', IFNULL(aux, '')) SEPARATOR ', ') AS especialidadesRevisor
        FROM (
            SELECT
                id,
                nombre,
                rut,
                email,
                topico_especialidad,
                GROUP_CONCAT(ESPECIALIDAD_EXTRA SEPARATOR ', ') AS aux
            FROM 
                revisor
            LEFT JOIN
                especialidad_agregada ON revisor.ID = especialidad_agregada.ID_REVISOR
            GROUP BY 
                ID_REVISOR
        ) as subQuery GROUP BY RUT LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $revisoresPorPagina, $offset);
}

$stmt->execute();
$resultado = $stmt->get_result();
$revisores = $resultado->fetch_all(MYSQLI_ASSOC);

// Obtener el número total de artículos para calcular las páginas
if (!empty($buscar)) {
    $query_total = "SELECT COUNT(*) FROM revisor WHERE nombre LIKE ?";
    $stmt_total = $conexion->prepare($query_total);
    $stmt_total->bind_param("s", $buscar_param);
} else {
    $query_total = "SELECT COUNT(*) FROM revisor";
    $stmt_total = $conexion->prepare($query_total);
}

$stmt_total->execute();
$resultado_total = $stmt_total->get_result();
$total_revisores = $resultado_total->fetch_row()[0];

// Calcular el número total de páginas
$total_paginas = ceil($total_revisores / $revisoresPorPagina);
?>

<!-- Contenedor principal con Bootstrap -->
<div class="container mt-5">
    <!-- Barra de búsqueda -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form action="gestion_revisores.php" method="GET" class="d-flex">
                <input type="text" class="form-control" name="buscar" placeholder="Buscar revisor por nombre" value="<?= htmlspecialchars($buscar); ?>">
                <button class="btn btn-primary ms-2" type="submit">Buscar</button>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-success show">
        <?= $_SESSION['mensaje']; ?>
    </div>
    <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger show">
        <?= $_SESSION['error']; ?>
    </div>
    <?php unset($_SESSION['error']); ?> 
    <?php endif; ?>

    <!-- Dashboard de artículos -->
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex containerTitleAndButton align-items-center">
                <h2 class="me-auto">Revisores registrados</h2>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#appendModal">Agregar revisor</button>
            </div>
            <!-- Tabla de artículos -->
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Rut</th>
                        <th>E-mail</th>
                        <th>Especialidades</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($revisores)): ?> 
                        <?php foreach ($revisores as $revisor): ?>
                            <tr>
                                <td><?= htmlspecialchars($revisor['nombre']) ?></td>
                                <td><?= htmlspecialchars($revisor['rut']) ?></td>
                                <td><?= htmlspecialchars($revisor['email']) ?></td>
                                <td><?= htmlspecialchars($revisor['especialidadesRevisor']) ?></td>
                                <td>
                                    <div class="d-flex gap-3">
                                        <button type='button' class='btn btn-primary bossAction'
                                        data-bs-toggle='modal' 
                                        data-bs-target='#modifierModal'
                                        data-rut="<?= htmlspecialchars($revisor['rut']) ?>"
                                        data-nombre="<?= htmlspecialchars($revisor['nombre']) ?>"
                                        data-email="<?= htmlspecialchars($revisor['email']) ?>"
                                        data-especialidades="<?= htmlspecialchars($revisor['especialidadesRevisor']) ?>"
                                        data-id="<?= htmlspecialchars($revisor['id']) ?>">
                                        Modificar datos
                                        </button>
                                        <button type="button" class="btn btn-danger bossAction"
                                        data-bs-toggle="modal" 
                                        data-bs-target='#deleteModal'
                                        data-id="<?= htmlspecialchars($revisor['id']) ?>">Eliminar revisor</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='5' class='text-center'>No se encontraron revisores.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal modificar datos -->
            <div class="modal fade" id="modifierModal" tabindex="-1" aria-labelledby="modifierModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modifierModalLabel">Modificar datos</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formRevisor" action="../controller/modificarRevisores.php" method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="inputName" class="form-label">Nombre</label>
                                    <input type="text" name="name" id="inputName" class="form-control" required>
                                    <input type="hidden" name="id" id="inputID">
                                </div>
                                <div class="mb-3">
                                    <label for="inputRut" class="form-label">Rut</label>
                                    <input type="text" id="inputRut" class="form-control" name="rut" maxlength="10" required>
                                </div>
                                <div class="mb-3">
                                    <label for="inputEmail" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="inputEmail" name="email" value="<?php echo isset($_POST["email"]) ? htmlspecialchars($_POST["email"]) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tópicos</label>
                                    <div class="especialidadesContainer" id="especialidadesContainer">
                                        <?php
                                        $result_topicos->data_seek(0);
                                        while ($row_topico = $result_topicos->fetch_assoc()):
                                            $nombreTopico = htmlspecialchars($row_topico['nombre']);
                                        ?>
                                            <div class="form-check">
                                                <input type="checkbox" name="topicos[]" 
                                                id="topico<?= $nombreTopico ?>"
                                                value="<?= $nombreTopico ?>"
                                                class="form-check-input">
                                                <label for="topico<?= $nombreTopico ?>" class="form-check-label">
                                                    <?= $nombreTopico ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" name="btnchangedata">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal borrar registro -->
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="deleteModalLabel">Eliminar revisor</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="../controller/eliminar_revisor.php" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="inputDeleteID">
                                <h3>¿Estas seguro de eliminar este revisor?</h3>
                                <h5>Esta acción no puede revertirse</h5>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger" name="btndelete">Eliminar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal agregar revisor -->
            <div class="modal fade" id="appendModal" tabindex="-1" aria-labelledby="appendModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="appendModalLabel">Agregar revisor</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formAppend" action="../controller/agregar_revisor.php" method="post">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="inputRut" class="form-label">Rut</label>
                                    <input type="text" id="inputRutAppend" class="form-control" name="rut" required>
                                </div>
                                <div class="mb-3">
                                    <label for="inputName" class="form-label">Nombre</label>
                                    <input type="text" id="inputNameAppend" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="inputEmail" class="form-label">Email</label>
                                    <input type="email" id="inputEmailAppend" class="form-control" name="email" required>
                                </div>
                                <label class="form-label">Tópicos</label>
                                    <div class="especialidadesContainer" id="especialidadesContainerAppend">
                                        <?php
                                        $result_topicos->data_seek(0);
                                        while ($row_topico = $result_topicos->fetch_assoc()):
                                            $nombreTopico = htmlspecialchars($row_topico['nombre']);
                                        ?>
                                            <div class="form-check">
                                                <input type="checkbox" name="topicos[]" 
                                                id="topicoAppend<?= $nombreTopico ?>"
                                                value="<?= $nombreTopico ?>"
                                                class="form-check-input">
                                                <label for="topicoAppend<?= $nombreTopico ?>" class="form-check-label">
                                                    <?= $nombreTopico ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" name="btnappend">Agregar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <!-- Página anterior -->
                    <li class="page-item <?= $actualPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?= $actualPage - 1; ?>&buscar=<?= htmlspecialchars($buscar); ?>" tabindex="-1">Anterior</a>
                    </li>

                    <!-- Páginas numeradas -->
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i == $actualPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?= $i; ?>&buscar=<?= htmlspecialchars($buscar); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Página siguiente -->
                    <li class="page-item <?= $actualPage >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?= $actualPage + 1; ?>&buscar=<?= htmlspecialchars($buscar); ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <img src="tarea2/img/GESCONiconoNEGROmini.svg" class="rounded me-2" style="width: 20px; height: auto;" alt="Logo GESCON">
            <strong class="me-auto">GESCON</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?= isset($_SESSION['correo']) ? $_SESSION['correo'] : '' ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<?php if (isset($_SESSION['correo'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toastEl = document.getElementById('liveToast');
    if (toastEl) {
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
});
</script>
<?php unset($_SESSION['correo']); endif;?>
<script>
    // Espera a que el DOM esté cargado
    document.addEventListener('DOMContentLoaded', function () {
        // Selecciona todas las alertas de Bootstrap
        const alerts = document.querySelectorAll('.alert');

        // Recorre cada alerta
        alerts.forEach(function (alert) {
            // Espera 3 segundos (3000 ms) y luego oculta la alerta
            setTimeout(function () {
                // Usa una animación de desvanecimiento si quieres
                alert.classList.add('fade');
                alert.classList.remove('show');

                // Luego de un momento más, la quita del DOM completamente
                setTimeout(function () {
                    alert.remove();
                }, 500); // tiempo extra para que termine la animación
            }, 4000); // Tiempo que permanece visible
        });
    });

    
    document.getElementById('formRevisor').addEventListener('submit', function(e) {
        const especialidadesContainer = document.getElementById('especialidadesContainer');
        const checkboxes = especialidadesContainer.querySelectorAll('input[name="topicos[]"]:checked');
        let errorElement = document.getElementById('especialidadesError')
        
        if(checkboxes.length === 0) {
            e.preventDefault();
            
            // Configurar mensaje de error si no existe
            if(!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = 'especialidadesError';
                errorElement.className = 'text-danger mt-2';
                errorElement.textContent = 'Debes seleccionar al menos una especialidad';
                especialidadesContainer.parentNode.insertBefore(errorElement, especialidadesContainer.nextSibling);
            }
            
            // Estilo de error
            especialidadesContainer.classList.add('border', 'border-danger', 'p-2', 'rounded');
            
            // Enfocar el contenedor de especialidades
            especialidadesContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            return false;
        }
        
        especialidadesContainer.classList.remove('border', 'border-danger', 'p-2', 'rounded');
        if (errorElement){
            errorElement.remove();
        }
        
        return true;
    });
    
document.getElementById('modifierModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var rut = button.getAttribute('data-rut');
    var nombre = button.getAttribute('data-nombre');
    var email = button.getAttribute('data-email');
    var especialidadesStr = button.getAttribute('data-especialidades');
    var id = button.getAttribute('data-id');
    
    // Actualizar campos básicos
    document.getElementById('inputRut').value = rut;
    document.getElementById('inputName').value = nombre;
    document.getElementById('inputEmail').value = email;
    document.getElementById('inputID').value = id;
    
    // Procesar especialidades
    if (especialidadesStr) {
        // Dividir las especialidades y limpiar espacios
        var especialidades = especialidadesStr.split(',').map(function(item) {
            return item.trim();
        });
        
        // Marcar los checkboxes correspondientes
        var checkboxes = document.querySelectorAll('input[name="topicos[]"]');
        checkboxes.forEach(function(checkbox) {
            // Verificar si el valor del checkbox está en las especialidades del revisor
            checkbox.checked = especialidades.includes(checkbox.value);
        });

        const especialidadesContainer = document.getElementById('especialidadesContainer');
        especialidadesContainer.classList.remove('border', 'border-danger', 'p-2', 'rounded');
        const errorElement = document.getElementById('especialidadesError');
        if(errorElement) {
            errorElement.remove();
        }
    }
});
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    
    document.getElementById('inputDeleteID').value = id;
});

document.getElementById('formAppend').addEventListener('submit', function(e) {
    const especialidadesContainer = document.getElementById('especialidadesContainerAppend');
    const checkboxes = especialidadesContainer.querySelectorAll('input[name="topicos[]"]:checked');
    let errorElement = document.getElementById('especialidadesErrorAppend');
    
    if(checkboxes.length === 0) {
        e.preventDefault();
        
        // Configurar mensaje de error si no existe
        if(!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = 'especialidadesErrorAppend';
            errorElement.className = 'text-danger mt-2';
            errorElement.textContent = 'Debes seleccionar al menos una especialidad';
            especialidadesContainer.parentNode.insertBefore(errorElement, especialidadesContainer.nextSibling);
        }
        
        // Estilo de error
        especialidadesContainer.classList.add('border', 'border-danger', 'p-2', 'rounded');
        
        // Enfocar el contenedor de especialidades
        especialidadesContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        return false;
    }

    especialidadesContainer.classList.remove('border', 'border-danger', 'p-2', 'rounded');
    if (errorElement){
        errorElement.remove();
    }

    return true;
});

document.getElementById('appendModal').addEventListener('show.bs.modal', function() {
    const especialidadesContainer = document.getElementById('especialidadesContainerAppend');
    const errorElement = document.getElementById('especialidadesErrorAppend');
    
    // Limpiar errores visuales
    especialidadesContainer.classList.remove('border', 'border-danger', 'p-2', 'rounded');
    
    // Eliminar mensaje de error si existe
    if(errorElement) {
        errorElement.remove();
    }
    
    // Opcional: Desmarcar todos los checkboxes al abrir el modal
    const checkboxes = especialidadesContainer.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
});
</script>