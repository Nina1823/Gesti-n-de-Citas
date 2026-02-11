<?php
    session_start();
    $_SESSION = array();
    //Destruir todas las variables de sesion
    session_destroy();

    //redirigir al login
    header("Location:login.php");

    exit;

?>