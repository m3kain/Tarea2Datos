<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once '../modelos/Articulo.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$resultados = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar'])) {
    $filtros = [
        'titulo' => $_GET['titulo'] ?? '',
        'resumen' => $_GET['resumen'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        'aceptacion' => $_GET['aceptacion'] ?? '',
        'autor' => $_GET['autor'] ?? '',
        'revisor' => $_GET['revisor'] ?? '',
        'topico' => $_GET['topico'] ?? ''
    ];
    $resultados = Articulo::filtrar($filtros);
}
?>

<h2>Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></h2>
<p><a href="perfil.php">Perfil</a></p>

<!-- Controles por tipo de usuario -->
<?php if (in_array($_SESSION['rol'], [2, 3, 4])): ?>
    <p><a href="autor/enviar_articulo.php">Enviar nuevo artículo</a></p>
<?php endif; ?>

<?php if (in_array($_SESSION['rol'], [3, 4])): ?>
    <p><a href="revisor/evaluar_articulos.php">Evaluar artículos</a></p>
<?php endif; ?>

<?php if ($_SESSION['rol'] == 1): ?>
    <p><a href="jefe/gestionar_revisores.php">Gestión de revisores</a></p>
    <p><a href="jefe/gestion_asignaciones.php">Gestion de asignaciones</a></p>
<?php endif; ?>

<h2>Buscar artículos</h2>
<form action="dashboard.php" method="GET">
    <input type="text" name="titulo" placeholder="Buscar por título..." style="width: 300px;">
    <button type="button" onclick="toggleFiltros()">Filtros</button>
    <button type="submit" name="buscar" value="1">Buscar</button>

    <div id="panelFiltros" style="display:none; margin-top: 10px;">
        <input type="text" name="resumen" placeholder="Buscar por resumen..."><br>
        Autor: <input type="text" name="autor" placeholder="Nombre del autor"><br>
        Revisor: <input type="text" name="revisor" placeholder="Nombre del revisor"><br>
        Tópico: <input type="text" name="topico" placeholder="Tópico relacionado"><br>
        Desde: <input type="date" name="fecha_desde">
        Hasta: <input type="date" name="fecha_hasta"><br>
        Aceptación: 
        <select name="aceptacion">
            <option value="">--</option>
            <option value="1">Aceptado</option>
            <option value="0">No aceptado</option>
        </select><br>
    </div>
</form>

<hr>

<?php if (!empty($resultados)): ?>
    <h3>Resultados:</h3>
    <div style="max-height: 300px; overflow-y: scroll;">
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Resumen</th>
                    <th>Tópicos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['titulo']) ?></td>
                        <td><?= htmlspecialchars($a['resumen']) ?></td>
                        <td><?= htmlspecialchars($a['topicos'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (isset($_GET['buscar'])): ?>
    <p>No se encontraron artículos.</p>
<?php endif; ?>

<script>
function toggleFiltros() {
    const panel = document.getElementById("panelFiltros");
    panel.style.display = panel.style.display === "none" ? "block" : "none";
}
</script>
