<?php
include("conexion.php");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ver Asistencias</title>
    <link rel="stylesheet" href="style.css">
   
</head>
<body>
<?php include("navbar.php"); ?>
<h1>Listado de Asistencias</h1>
<form method="GET" class="buscar-fecha">

    Fecha:

    <input type="date" name="fecha" >

    <button type="submit">Buscar</button>

</form>

<br>
<div class="leyenda">

    <span class="presente">
        ● Presente
    </span>

    <span class="tarde">
        ● Tarde
    </span>

    <span class="ausente">
        ● Ausente
    </span>

    <span class="adelantado">
        ● Adelantado
    </span>

</div>

<br>
<table>

<tr>
    <th>Fecha</th>
    <th>Docente</th>
    <th>Materia</th>
    <th>Entrada</th>
    <th>Salida</th>
    <th>Estado</th>
    <th>Acción</th>
</tr>
<?php
if(isset($_GET['fecha']) && $_GET['fecha']!=""){

    $fecha = $_GET['fecha'];

    $sql = "SELECT id_asistencia, fecha, nombre_docente, materia, hora_ingreso, hora_egreso, estado
                FROM asistencia
                WHERE fecha='$fecha'
                ORDER BY nombre_docente";

}else{

    $sql = "SELECT id_asistencia, fecha, nombre_docente, materia, hora_ingreso, hora_egreso, estado
                FROM asistencia
                ORDER BY fecha DESC";

}


$resultado = mysqli_query($conexion,$sql);

while($fila = mysqli_fetch_assoc($resultado)){
?>

<tr>
<td><?php echo date("d/m/Y", strtotime($fila['fecha'])); ?></td>

<td><?php echo $fila['nombre_docente']; ?></td>

<td><?php echo $fila['materia']; ?></td>

<td><?php echo $fila['hora_ingreso']; ?></td>

<td><?php echo $fila['hora_egreso']; ?></td>


<td>
<?php

if($fila['estado']=="Presente"){

    echo "<span style='color:green;font-weight:bold;'>
    Presente
    </span>";

}elseif($fila['estado']=="Tarde"){

    echo "<span style='color:goldenrod;font-weight:bold;'>
    Tarde
    </span>";

}elseif($fila['estado']=="Ausente"){

    echo "<span style='color:red;font-weight:bold;'>
    Ausente
    </span>";

}elseif($fila['estado']=="Adelantado"){

    echo "<span style='color:orange;font-weight:bold;'>
    Adelantado
    </span>";

}

?>
</td>

<td><a href="editar_asistencia.php?id=<?php echo $fila['id_asistencia']; ?>">Editar</a></td>

</tr>

<?php
}
?>

</table>

</body>
</html>