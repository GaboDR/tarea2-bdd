<?php
include('../includes/header.php'); 
include('../db.php');

if (!isset($_SESSION['jefe_rut'])){
    header("Location: ../login/login_jefe.php");
    exit;
}

$query = "SELECT * FROM vista_articulos_autores_revisores";
$stmt = $conexion->prepare($query);

$stmt->execute();
$resultado = $stmt->get_result();
$datArticulos = $resultado->fetch_all(MYSQLI_ASSOC);

$queryAux = "SELECT * FROM vista_revisores_completa";
$queryStmt = $conexion->query($queryAux);
$viewRevisores = $queryStmt->fetch_all(MYSQLI_ASSOC);

$autorPorArticuloQuery = "SELECT 
                            a.id as id_articulo,
                            aut.id as id_autor,
                            aut.nombre
                        FROM articulo a 
                        LEFT JOIN (
                            SELECT ID, nombre FROM Autor
                            ) aut ON aut.ID = a.autor_contacto OR aut.ID IN (
                            SELECT ID_AUTOR FROM Autor_participante WHERE ID_ARTICULO = a.ID
                        )";
$autor = $conexion->query($autorPorArticuloQuery);
$autoresPorArticulo = $autor->fetch_all(MYSQLI_ASSOC);

$revisorPorArticuloQuery = "SELECT 
                            a.id as id_articulo,
                            r.id as id_revisor,
                            r.nombre
                        FROM articulo a 
                        LEFT JOIN Articulo_revisor ar ON ar.ID_ARTICULO = a.ID
                        LEFT JOIN Revisor r ON r.ID = ar.ID_REVISOR
                        ";
$revisor = $conexion->query($revisorPorArticuloQuery);
$revisoresPorArticulo = $revisor->fetch_all(MYSQLI_ASSOC);

$articuloPorRevisorQuery = "SELECT
                            a.id AS id_articulo,
                            a.titulo,
                            r.id AS id_revisor
                            FROM revisor r
                            LEFT JOIN Articulo_revisor ar ON ar.ID_revisor = r.id
                            LEFT JOIN articulo a ON a.id = ar.id_articulo";
$articulo = $conexion->query($articuloPorRevisorQuery);
$articulosPorRevisor = $articulo->fetch_all(MYSQLI_ASSOC);

$totalRevisoresQuery = "SELECT id, nombre FROM revisor";
$total = $conexion->query($totalRevisoresQuery);
$totalRevisores = $total->fetch_all(MYSQLI_ASSOC);

$totalArticulosQuery = "SELECT id, titulo FROM articulo";
$totalAr = $conexion->query($totalArticulosQuery);
$totalArticulos = $totalAr->fetch_all(MYSQLI_ASSOC);

$dataRevisorQuery = "SELECT
    r.id,
    r.nombre,
    IFNULL(ar_rel.cantidadAsignados, 0) AS cantidadAsignados,
    GROUP_CONCAT(DISTINCT todas_especialidades.nombre ORDER BY todas_especialidades.nombre SEPARATOR ', ') AS especialidades
FROM 
    revisor r
LEFT JOIN (
    SELECT 
        ID_REVISOR,
        COUNT(*) AS cantidadAsignados
    FROM articulo_revisor
    GROUP BY ID_REVISOR
) AS ar_rel ON ar_rel.ID_REVISOR = r.ID
LEFT JOIN (
    SELECT 
        r1.id AS id_revisor,
        r1.topico_especialidad as nombre
    FROM
        revisor r1
    WHERE r1.topico_especialidad IS NOT NULL

    UNION

    SELECT 
        ea.id_revisor,
        te.nombre
    FROM 
        especialidad_agregada ea
    INNER JOIN topico_especialidad te ON te.NOMBRE = ea.ESPECIALIDAD_EXTRA
) AS todas_especialidades ON todas_especialidades.id_revisor = r.id
GROUP BY r.id;";
$datosRev = $conexion->query($dataRevisorQuery);
$dataRevisor = $datosRev->fetch_all(MYSQLI_ASSOC);

$datos = [];

foreach ($dataRevisor as $row){
    $id = $row['id'];
    $datos[$id] = [
        'cantidadAsignados' => $row['cantidadAsignados'],
        'especialidades' => $row['especialidades']
    ];
}

$dataArticulosQuery = "SELECT 
    a.ID AS id_articulo,
    a.Titulo,
    COUNT(ar.ID_REVISOR) AS cantidadDeRevisoresAsignadosAlArticulo,
    CONCAT_WS(', ', 
        a.Topico_principal,
        (SELECT GROUP_CONCAT(te.TOPICO_EXTRA SEPARATOR ', ')
            FROM Topicos_extra te
            WHERE te.ID_ARTICULO = a.ID)
    ) AS topicosArticulo
FROM 
    Articulo a
LEFT JOIN 
    Articulo_revisor ar ON a.ID = ar.ID_ARTICULO
GROUP BY 
    a.ID, a.Titulo, a.Topico_principal";
$dataArti = $conexion->query($dataArticulosQuery);
$dataArticulos = $dataArti->fetch_all(MYSQLI_ASSOC);

$datosArticulos = [];

foreach ($dataArticulos as $row){
    $id = $row['id_articulo'];
    $datosArticulos[$id] = [
        'cantidadRevisoresAsignados' => $row['cantidadDeRevisoresAsignadosAlArticulo'],
        'topicos' => $row['topicosArticulo']
    ];
}

function contarRevisores($id_articulo, $conexion){
    $query = "SELECT COUNT(id_revisor) AS revisores, id_articulo FROM articulo_revisor GROUP BY id_articulo";
    $queryStmt = $conexion->query($query);
    $total = $queryStmt->fetch_all(MYSQLI_ASSOC);

    foreach ($total as $t){
        if ($t['id_articulo'] == $id_articulo){
            return $t['revisores'];
        }
    }
    return 0;
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar revisores</title>
</head>

<script>
    let mostrandoTabla1 = true;

    function alternarTablas() {
        mostrandoTabla1 = !mostrandoTabla1;

        document.getElementById('tabla1').style.display = mostrandoTabla1 ? 'table' : 'none';
        document.getElementById('tabla2').style.display = mostrandoTabla1 ? 'none' : 'table';

        document.getElementById('btnToggle').innerText = mostrandoTabla1
        ? 'Asignar artículo a revisor'
        : 'Asignar revisor a artículo';

        document.getElementById('titleToggle').innerText = mostrandoTabla1
        ? 'Lista de artículos'
        : 'Lista de revisores';
    }
</script>

<div class="container mt-5">
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
                <h2 class="me-auto" id="titleToggle">Lista de artículos</h2>
                <button id="btnToggle" onclick="alternarTablas()" class="btn btn-success">Asignar artículo a revisor</button>
            </div>
            <!-- Tabla de artículos -->
            <table class="table table-bordered table-striped" id="tabla1">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Tópicos</th>
                        <th>Autores</th>
                        <th>Revisores</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($datArticulos)): ?>
                        <?php foreach ($datArticulos as $data): ?>
                            <tr class="<?=  (contarRevisores(htmlspecialchars($data['articulo_id']), $conexion) < 3) ? 'table-warning' : '' ?>">
                                <td><?= htmlspecialchars($data['articulo_id']) ?></td>
                                <td><?= htmlspecialchars($data['titulo_articulo']) ?></td>
                                <td><?= htmlspecialchars($data['topicos']) ?></td>
                                <td>
                                    <table>
                                        <?php foreach ($autoresPorArticulo as $autor): ?>
                                            <?php if ($autor['id_articulo'] == $data['articulo_id']): ?>
                                                <tr><td>•<?= htmlspecialchars($autor['nombre']) ?></td></tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                                <td>
                                    <table>
                                        <?php if (strlen($data['revisores']) > 0): ?>
                                            <?php foreach ($revisoresPorArticulo as $revisor): ?>
                                                <?php if ($revisor['id_articulo'] == $data['articulo_id']): ?>
                                                    <tr><td>•<?= htmlspecialchars($revisor['nombre']) ?></td></tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>Sin revisores asignados.</tr>
                                        <?php endif ?>
                                    </table>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center" style="gap: 1rem;">
                                        <button type='button' class='btn btn-primary'
                                        data-bs-toggle ='modal'
                                        data-bs-target="#appendModal"
                                        data-articulo-id="<?= $data['articulo_id'] ?>"
                                        data-topicos="<?= htmlspecialchars($data['topicos']) ?>">
                                            Asignar revisor
                                        </button>
                                        <button type="button" class="btn btn-danger"
                                        data-bs-toggle ='modal'
                                        data-bs-target="#deleteModal"
                                        data-articulo-id='<?= $data['articulo_id'] ?>'>
                                            Quitar revisor
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='5' class='text-center'>No se encontraron artículos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal agregar revisor -->
            <div class="modal fade" id="appendModal" tabindex="-1" aria-labelledby="appendModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="appendModalLabel">Agregar revisor</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formulario" action="../controller/asignar_revisor.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_articulo" id="id_articulo_input">
                                <input type="hidden" name="topicos" id="topicosArticulo" >
                                <div class="mb-3">
                                    <label for="opciones" class="form-label">Selecciona un revisor</label>
                                    <select class="form-select" id="opciones" name="eleccion">
                                        <option value="" selected>Seleccionar</option>
                                    </select>
                                </div>
                                <div id="contenido" class="border p-3 rounded bg-light">
                                    Seleccione un revisor.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" name="btnchangedata">Añadir revisor</button>
                                <button type="submit" class="btn btn-success" name="btnrandom">Asignar 3 revisores al azar</button>
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
                            <h1 class="modal-title fs-5" id="deleteModalLabel">Quitar revisor</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="../controller/quitar_revisor.php" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="id_articulo" id="delete_id_articulo">
                                <label for="select_revisor_delete" class="form-label">Selecciona el revisor a quitar</label>
                                <select class="form-select" id="select_revisor_delete" name="id_revisor">
                                    <option value="" selected>Seleccionar</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger" name="btndelete">Quitar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla 2 (oculta por defecto) -->
            <table id="tabla2" class="table table-bordered table-striped" style="display: none;">
                <thead class="thead-dark">
                    <tr>
                        <th>Rut</th>
                        <th>Miembro</th>
                        <th>Especialidades</th>
                        <th>Artículos asignados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($viewRevisores)): ?>
                        <?php foreach ($viewRevisores as $rev): 
                            $especialidadesArray = explode(', ', $rev['Todas_Especialidades']);
                            $articulosArray = explode('| ', $rev['Articulos_Asignados']);
                            $idArticulosArray = explode('| ', $rev['id_Articulos_Asignados']);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($rev['Revisor_Rut']) ?></td>
                                <td><?= htmlspecialchars($rev['Revisor_Nombre']) ?></td>
                                <td>
                                    <table>
                                        <?php foreach ($especialidadesArray as $especialidad): ?>
                                            <tr><td>•<?= htmlspecialchars($especialidad) ?></td></tr>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                                <td>
                                    <table>
                                        <?php foreach ($articulosArray as $art): ?>
                                            <?php if($art != ''): ?>
                                                <tr><td>•<?= htmlspecialchars($art) ?></td></tr>
                                            <?php else: ?>
                                                Sin artículos asignado.
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center" style="gap: 1rem;">
                                        <button type='button' class='btn btn-primary'
                                        data-bs-toggle ='modal'
                                        data-bs-target="#asignModal"
                                        data-id-revisor='<?= $rev['Revisor_ID'] ?>'
                                        data-especialidades='<?= $rev['Todas_Especialidades'] ?>'>
                                            Asignar articulo
                                        </button>
                                        <button type="button" class="btn btn-danger"
                                        data-bs-toggle ='modal'
                                        data-bs-target="#desasignModal"
                                        data-id-revisor='<?= $rev['Revisor_ID'] ?>'>
                                            Quitar articulo
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal asignar articulo -->
            <div class="modal fade" id="asignModal" tabindex="-1" aria-labelledby="asignModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="asignModalLabel">Asignar artículo</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formularioAsign" action="../controller/asignar_articulo.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_revisor" id="id_revisor_input">
                                <input type="hidden" name="especialidad" id="especialidadesRevisor" >
                                <div class="mb-3">
                                    <label for="opcionesAsign" class="form-label">Selecciona un revisor</label>
                                    <select class="form-select" id="opcionesAsign" name="eleccionAsign">
                                        <option value="" selected>Seleccionar</option>
                                    </select>
                                </div>
                                <div id="contenidoAsign" class="border p-3 rounded bg-light">
                                    Seleccione un revisor.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" name="btnasign">Asignar artículo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal desasignar revisor -->
            <div class="modal fade" id="desasignModal" tabindex="-1" aria-labelledby="desasignModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="desasignModalLabel">Quitar revisor</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="../controller/quitar_revisor.php" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="id_revisor" id="delete_id_revisor">
                                <label for="select_articulo_delete" class="form-label">Selecciona el articulo a quitar</label>
                                <select class="form-select" id="select_articulo_delete" name="id_articulo">
                                    <option value="" selected>Seleccionar</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger" name="btndelete">Quitar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
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
// Variables pasadas desde PHP a JS
const revisoresPorArticulo = <?php echo json_encode($revisoresPorArticulo); ?>;
const articulosPorRevisor = <?php echo json_encode($articulosPorRevisor); ?>;
const totalRevisores = <?php echo json_encode($totalRevisores); ?>;
const totalArticulos = <?php echo json_encode($totalArticulos); ?>;
const datos = <?php
    $datosFormateados = [];
    foreach ($dataRevisor as $item) {
        $datosFormateados[$item['id']] = [
            'cantidadAsignados' => $item['cantidadAsignados'],
            'especialidades' => $item['especialidades']
        ];
    }
    echo json_encode($datosFormateados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>;
const datosArticulos = <?php
    $datosFormat = [];
    foreach ($dataArticulos as $item){
        $datosFormat[$item['id_articulo']] = [
            'cantidadRevisoresAsignados' => $item['cantidadDeRevisoresAsignadosAlArticulo'],
            'topicos' => $item['topicosArticulo']
        ];
    }
    echo json_encode($datosFormat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>;

const appendModal = document.getElementById('appendModal');
const deleteModal = document.getElementById('deleteModal');
const asignModal = document.getElementById('asignModal');
const desasignModal = document.getElementById('desasignModal');
const selectRevisores = document.getElementById('opciones');
const selectRevisorDelete = document.getElementById('select_revisor_delete');
const selectArticulo = document.getElementById('opcionesAsign');
const selectArticuloDesasign = document.getElementById('select_articulo_delete');
const inputIdArticulo = document.getElementById('id_articulo_input');
const inputTopicosArticulo = document.getElementById('topicosArticulo');
const inputIdRevisor = document.getElementById('id_revisor_input');
const inputEspecialidadesRevisor = document.getElementById('especialidadesRevisor');
const inputDeleteArticulo = document.getElementById('delete_id_articulo');
const inputDesasignRevisor = document.getElementById('delete_id_revisor');
const contenido = document.getElementById('contenido');
const formulario = document.getElementById('formulario');
const contenidoAsign = document.getElementById('contenidoAsign');
const formularioAsign = document.getElementById('formularioAsign');

if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const articuloId = button.getAttribute('data-articulo-id');

        // Setear id_articulo en input oculto
        inputDeleteArticulo.value = articuloId;

        // Limpiar opciones previas
        selectRevisorDelete.innerHTML = '';

        // Filtrar revisores asignados a este artículo
        const revisoresAsignados = revisoresPorArticulo.filter(r => r.id_articulo == articuloId && r.id_revisor != null);

        if (revisoresAsignados.length === 0) {
            const option = document.createElement('option');
            option.text = 'No hay revisores asignados';
            option.disabled = true;
            option.selected = true;
            selectRevisorDelete.appendChild(option);
            selectRevisorDelete.disabled = true;
        } else {
            // Llenar select con revisores asignados
            revisoresAsignados.forEach(revisor => {
            const option = document.createElement('option');
            option.value = revisor.id_revisor;
            option.text = revisor.nombre;
            selectRevisorDelete.appendChild(option);
        });
        selectRevisorDelete.disabled = false;
        }
    });
}

if (desasignModal) {
    desasignModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const revisorId = button.getAttribute('data-id-revisor');

        inputDesasignRevisor.value = revisorId;

        selectArticuloDesasign.innerHTML = '';

        const articulosAsignados = articulosPorRevisor.filter(r => r.id_revisor == revisorId && r.id_articulo != null);

        if (articulosAsignados.length == 0){
            const option = document.createElement('option');
            option.text = 'No hay articulos asignados.';
            option.disabled = true;
            option.selected = true;
            selectArticuloDesasign.appendChild(option);
            selectArticuloDesasign.disabled = true;
        } else {
            articulosAsignados.forEach(arti => {
                const option = document.createElement('option');
                option.value = arti.id_articulo;
                option.text = arti.titulo;
                selectArticuloDesasign.appendChild(option);
            });
            selectArticuloDesasign.disabled = false;
        }
    });
}

if (appendModal) {
    appendModal.addEventListener('shown.bs.modal', function(event) {
        const button = event.relatedTarget;
        const articuloId = button.getAttribute('data-articulo-id');
        const topicos = button.getAttribute('data-topicos');

        inputIdArticulo.value = articuloId;
        inputTopicosArticulo.value = topicos;

        const revisoresAsignados = revisoresPorArticulo
            .filter(r => r.id_articulo == articuloId)   
            .map(r => r.id_revisor);                     

        selectRevisores.innerHTML = '<option value="" selected>Seleccione un revisor</option>';

        totalRevisores.forEach(revisor => {
            const option = document.createElement('option');
            option.value = revisor.id;
            option.text = revisoresAsignados.includes(revisor.id) 
                ? `${revisor.nombre} (Ya asignado)` 
                : revisor.nombre;
            option.disabled = revisoresAsignados.includes(revisor.id);
            selectRevisores.appendChild(option);
        });

        contenido.innerText = "Seleccione un revisor.";
    });
}

if (asignModal) {
    asignModal.addEventListener('shown.bs.modal', function(event) {
        const button = event.relatedTarget;
        const revisorId = button.getAttribute('data-id-revisor');
        const especialidades = button.getAttribute('data-especialidades');

        inputIdRevisor.value = revisorId;
        inputEspecialidadesRevisor.value = especialidades;

        const articulosAsignados = articulosPorRevisor
            .filter(a => a.id_revisor == revisorId)
            .map(a => a.id_articulo);

        selectArticulo.innerHTML = '<option value="" selected>Seleccione un artículo</option>';

        totalArticulos.forEach(articulo => {
            const option = document.createElement('option');
            option.value = articulo.id;
            option.text = articulosAsignados.includes(articulo.id)
                ? `${articulo.titulo} (Ya asignado)`
                : articulo.titulo;
            option.disabled = articulosAsignados.includes(articulo.id);
            selectArticulo.appendChild(option);
        });

        contenido.innerText = "Seleccione un artículo";
    });
}

// Mostrar información adicional del revisor al cambiar selección
selectRevisores.addEventListener('change', function() {
  const valor = selectRevisores.value;
  const info = datos[valor];

  if (info) {
    contenido.innerText =
      `• Cantidad de artículos asignados: ${info.cantidadAsignados}\n` +
      `• Especialidades: ${info.especialidades}`;
  } else {
    contenido.innerText = "Seleccione un revisor.";
  }
});

// Validación al enviar el formulario
formulario.addEventListener('submit', function(event) {
  const revisorId = selectRevisores.value;
  const info = datos[revisorId];
  
  if (!info) {
    alert("Debe seleccionar un revisor válido.");
    event.preventDefault();
    return;
  }

  const especialidades = info.especialidades.toLowerCase().split(',').map(e => e.trim());
  const topicos = inputTopicosArticulo.value.toLowerCase().split(',').map(t => t.trim());

  const hayCoincidencia = topicos.some(topico => especialidades.includes(topico));

  if (!hayCoincidencia) {
    const confirmar = confirm("El revisor no tiene coincidencias con los tópicos del artículo. ¿Desea continuar?");
    if (!confirmar) {
      event.preventDefault();
    }
  }
});

selectArticulo.addEventListener('change', function(){
    const valor = selectArticulo.value;
    const info = datosArticulos[valor];
    console.log(info);

    if (info) {
        contenidoAsign.innerText = 
            `• Cantidad de revisores asignados: ${info.cantidadRevisoresAsignados}\n` +
            `• Tópicos: ${info.topicos}`;
    } else {
        contenidoAsign.innerText = "Seleccione un artículo.";
    }
});

formularioAsign.addEventListener('submit', function(event) {
    const articuloId = selectArticulo.value;
    const info = datosArticulos[articuloId];

    if (!info) {
        alert("Debe seleccionar un articulo valido.");
        event.preventDefault();
        return;
    }

    const topicos = info.topicos.toLowerCase().split(',').map(e => e.trim());
    const especialidades = inputEspecialidadesRevisor.toLowerCase().split(',').map(t => t.trim());

    const hayCoincidencia = especialidades.some(especialidad => topicos.includes(especialidad));

    if (!hayCoincidencia) {
        const confirmar = confirm("El articulo no tiene coincidencias con las especialidades del revisor. ¿Desea continuar?");
        if (!confirmar) {
            event.preventDefault();
        }
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>