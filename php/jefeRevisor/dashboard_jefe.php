<?php
include('../includes/header.php'); 
include('../db.php');

if (!isset($_SESSION['jefe_rut'])){
    header("Location: ../login/login_jefe.php");
    exit;
}


?>