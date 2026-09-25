<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
include("conexion.php");

if(isset($_POST['registrar'])){

   $codigo = $_POST['id_huella'];

    $buscar = mysqli_query($conexion, "SELECT * FROM docentes WHERE codigo='$codigo'");

    if(mysqli_num_rows($buscar) > 0){

        $docente = mysqli_fetch_assoc($buscar);

        $docente_id = $docente['id'];

        $fecha = date("Y-m-d");
        $hora = date("H:i:s");

       $turno_id = $docente['turno_id'];

            $consulta_turno = mysqli_query(
         $conexion,
       "SELECT * FROM turnos WHERE id='$turno_id'"
        );

         $turno = mysqli_fetch_assoc($consulta_turno);

        $hora_tolerancia = $turno['hora_tolerancia'];

        if($hora > $hora_tolerancia){
         $estado = "Tarde";
    }else{
    $estado = "Presente";
}

        //mysqli_query($conexion,
        $buscar_asistencia = mysqli_query(
    $conexion,
    "SELECT * FROM asistencias
    WHERE docente_id='$docente_id'
    AND fecha='$fecha'"
);

if(mysqli_num_rows($buscar_asistencia)==0){

    mysqli_query($conexion,
    "INSERT INTO asistencias
    (docente_id,fecha,entrada,estado)
    VALUES
    ('$docente_id','$fecha','$hora','$estado')");

    echo "<h3>Entrada registrada</h3>";

}else{

    $asistencia = mysqli_fetch_assoc($buscar_asistencia);

    if($asistencia['salida']==""){

        mysqli_query(
            $conexion,
            "UPDATE asistencias
            SET salida='$hora'
            WHERE id=".$asistencia['id']
        );

        echo "<h3>Salida registrada</h3>";

    }else{

        echo "<h3>Ya registró entrada y salida hoy</h3>";

    }

}

        echo "<h3>Asistencia registrada correctamente</h3>";

        echo "Docente: ".
        $docente['nombre']." ".
        $docente['apellido'];

        echo "<br>";

        echo "Hora: ".$hora;

        echo "<br>";

        echo "Estado: ".$estado;

    }else{

        echo "<h3>ID de huella no encontrado</h3>";

    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrar Asistencia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include("navbar.php"); ?>
<h1>Registrar Asistencia</h1>

<form method="POST">

    ID Huella:

    <input type="number" name="id_huella" required>

    <br><br>

    <button type="submit" name="registrar"> Registrar huella </button>

</form>

</body>
</html>