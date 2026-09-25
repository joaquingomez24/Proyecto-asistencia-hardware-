<?php
include("conexion.php");

$id = $_GET['id'];

if(isset($_POST['guardar'])){

    $entrada = $_POST['entrada'];
    $salida = $_POST['salida'];
    $estado = $_POST['estado'];

    mysqli_query(
        $conexion,
        "UPDATE asistencia
        SET hora_ingreso='$entrada', hora_egreso='$salida', estado='$estado'
        WHERE id_asistencia='$id' "
    );

   header("Location: ver_asistencia.php");
   exit;
}

$sql = "SELECT a.*, d.nombre, d.apellido
            FROM asistencia a
            INNER JOIN docentes d
            ON a.docentes_id=d.id
            WHERE a.id='$id' ";

$resultado = mysqli_query($conexion,$sql);

$fila = mysqli_fetch_assoc($resultado);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Asistencia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Editar Asistencia</h1>

<p>

<?php
echo $fila['nombre']." ".$fila['apellido'];
?>

</p>

<form method="POST">

    Entrada:

    <input type="time" name="entrada" value="<?php echo $fila['hora_ingreso']; ?>">

    <br><br>

    Salida:

    <input type="time" name="salida" value="<?php echo $fila['hora_egreso']; ?>">

    <br><br>

    Estado:

    <select name="estado">

        <option <?php if($fila['estado']=="Presente") echo "selected"; ?>>
        Presente
        </option>

        <option <?php if($fila['estado']=="Tarde") echo "selected"; ?>>
        Tarde
        </option>

        <option <?php if($fila['estado']=="Ausente") echo "selected"; ?>>
        Ausente
        </option>

        <option <?php if($fila['estado']=="Adelantado") echo "selected"; ?>>
        Adelantado
        </option>

    </select>

    <br><br>

    <button type="submit" name="guardar"> Guardar </button>

</form>

</body>
</html>