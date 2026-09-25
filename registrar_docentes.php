<?php
include("conexion.php");

if(isset($_POST['guardar'])){

    $dni = $_POST['dni'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $id_huella = $_POST['id_huella'];
    
    $sql = "INSERT INTO docentes (DNI , nombre, apellido, teléfono, email) VALUES ( '$dni' , '$nombre' , '$apellido' , '$telefono' , '$email' )";

    if(mysqli_query($conexion, $sql)){

        $id_docente_creado = mysqli_insert_id($conexion);

       if(!empty($id_huella)){
            $fecha_actual = date("Y-m-d");
            mysqli_query($conexion, "INSERT INTO huella_digital (id_huella, fecha_registro) VALUES ('$id_huella', '$fecha_actual')");
        }

        if(isset($_POST['materia']) && is_array($_POST['materia'])){
            for($i = 0; $i < count($_POST['materia']); $i++){

                $nom_materia = $_POST['materia'][$i];
                $turno = $_POST['turno'][$i];
                $curso = $_POST['curso'][$i];
                $division = $_POST['division'][$i];
                $curso_completo = $curso . " " . $division;
                $entrada = $_POST['entrada'][$i];
                $salida = $_POST['salida'][$i];

                if(!empty($nom_materia)){
                    $sql_materia = "INSERT INTO materia (nombre, turno, curso, horario_inicio, horario_finalizacion) VALUES ('$nom_materia', '$turno', '$curso_completo', '$entrada', '$salida')";

                    mysqli_query($conexion, $sql_materia);
                }
            }
        }

        echo "<p style='color: green;'>Docente, huella y horarios registrados con éxito.</p>";
    } else {
        echo "<p style='color: red;'>Error al guardar: " . mysqli_error($conexion) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Docentes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include("navbar.php"); ?>
<h1>Registrar Docente</h1>
<div class="contenedor">

<div class="formulario">

    <form method="POST">

        <div class="fila">

            <div class="campo">
                <label>Nombre</label>
                <input type="text" name="nombre">
            </div>

            <div class="campo">
                <label>Apellido</label>
                <input type="text" name="apellido">
            </div>

        </div>

        <div class="fila">

            <div class="campo">
                <label>DNI</label>
                <input type="text" name="dni">
            </div>

            <div class="campo">
                <label>Teléfono</label>
                <input type="text" name="telefono">
            </div>

        </div>

        <div class="fila">

            <div class="campo">
                <label>Email</label>
                <input type="email" name="email">
            </div>

            <div class="campo">
                <label>Código de huella</label>
                <input type="number" name="id_huella">
            </div>

        </div>
        <h2>Horarios</h2>

<div class="fila">

    <div class="campo">
        <label>Materia</label>
        <input type="text" name="materia[]">
    </div>

    <div class="campo">
        <label>Turno</label>
         <select name="turno[]">
            <option value="Mañana">Mañana</option>
            <option value="Tarde">Tarde</option>
            <option value="Vespertino">Vespertino</option>
        </select>
     </div>

    <div class="campo">
        <label>Curso</label>
        <select name="curso[]">
            <option>1°</option>
            <option>2°</option>
            <option>3°</option>
            <option>4°</option>
            <option>5°</option>
            <option>6°</option>
        </select>
    </div>

    <div class="campo">
        <label>División</label>
        <select name="division[]">
            <option>1°</option>
            <option>2°</option>
            <option>3°</option>
            <option>4°</option>
            <option>5°</option>
            <option>6°</option>
        </select>
    </div>

</div>

<div class="fila">

    <div class="campo">
        <label>Entrada</label>
        <input type="time" name="entrada[]">
    </div>

    <div class="campo">
        <label>Salida</label>
        <input type="time" name="salida[]">
    </div>

</div>

<button type="button">+ Agregar horario</button>

<br><br>

<button type="submit" name="guardar">
    Guardar docente
</button>

</div>
</form>
</div>
</body>
</html>