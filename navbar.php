<?php
    $pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<div class="navbar">
    <div class="nav-brand">
        <img src="logo.png" alt="Epet N 20" class="nav-logo">
        <div class="nav-title">
            <h2>Epet N 20</h2>
            <span>Sistema de asistencia</span>
        </div>
    </div>

    <div class="nav-links">
        <a href="index.php" class="<?php echo ($pagina_actual == 'index.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <a href="registrar_docentes.php" class="<?php echo ($pagina_actual == 'registrar_docentes.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-plus"></i> Registrar docente
        </a>
        <a href="ver_asistencia.php" class="<?php echo ($pagina_actual == 'ver_asistencia.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-circle-plus"></i> Ver asistencias
        </a>
    </div>
</div>