<?php

$conexion = mysqli_connect(
    "localhost",
    "root",
    "",
    "asistencia_docente"
);

if(!$conexion){
    die("Error de conexión");
}

?>