<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- BOOTSTRAP 4 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <!-- FONT AWESOME -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" crossorigin="anonymous">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/tarea2/php/dashboard.php">
      <img src="/tarea2/img/GESCONblanco.png" style="width: 35px; height: auto;" alt="Logo GESCON">
    </a>


    <?php if (isset($_SESSION['autor_id']) || isset($_SESSION['revisor_id']) || isset($_SESSION['jefe_rut'])): ?>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          
          <?php if (isset($_SESSION['autor_id'])): ?>
            <!-- Opciones para AUTOR -->
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/autor/perfil.php">Mi Perfil</a></li>
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/autor/ver_art.php">Mis artículos</a></li>
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/autor/crear_articulo.php">Subir artículo</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="/tarea2/php/logout.php">Cerrar sesión</a></li>
          
          <?php elseif (isset($_SESSION['revisor_id'])): ?>
            <!-- Opciones para REVISOR -->
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/revisor/perfil.php">Mi Perfil</a></li>

            <li class="nav-item"><a class="nav-link" href="/tarea2/php/revisor/articulos_revisar.php">Artículos a revisar</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="/tarea2/php/logout.php">Cerrar sesión</a></li>
          
          <?php elseif (isset($_SESSION['jefe_rut'])): ?>
            <!-- Opciones para JEFE DE COMITÉ -->
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/jefeRevisor/mainJefe.php">Mi Perfil</a></li>
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/jefeRevisor/asignar_revisores.php">Asignar revisores</a></li>
            <li class="nav-item"><a class="nav-link" href="/tarea2/php/jefeRevisor/gestion_revisores.php">Gestionar revisores</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="/tarea2/php/logout.php">Cerrar sesión</a></li>
          <?php endif; ?>

          <li class="nav-item"><a class="nav-link" href="/tarea2/php/index.php">Revisiones</a></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</nav>
