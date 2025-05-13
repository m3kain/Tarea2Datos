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


$mejores = Articulo::mejoresCalificados();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema Congreso</title>
    <link rel="stylesheet" href="../public/css/dashboard.css">
</head>
<body>
    <header style="background: #007bff; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
        <div><strong>Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></strong></div>
        <nav>
            <a href="perfil.php" style="color: white; margin-right: 15px;">Perfil</a>
            <?php if (in_array($_SESSION['rol'], [2, 3, 4])): ?>
                <a href="autor/enviar_articulo.php" style="color: white; margin-right: 15px;">Enviar artículo</a>
            <?php endif; ?>
            <?php if (in_array($_SESSION['rol'], [3, 4])): ?>
                <a href="revisor/evaluar_articulos.php" style="color: white; margin-right: 15px;">Evaluar artículos</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 1): ?>
                <a href="jefe/gestionar_revisores.php" style="color: white; margin-right: 15px;">Gestión de revisores</a>
                <a href="jefe/gestion_asignaciones.php" style="color: white;">Gestión de asignaciones</a>
            <?php endif; ?>
        </nav>
    </header>

    <main style="padding: 20px; max-width: 1000px; margin: auto;">
        <section>
            <h2>Buscar artículos</h2>
            <form action="dashboard.php" method="GET">
                <input type="text" name="titulo" placeholder="Buscar por título...">
                <button type="button" onclick="toggleFiltros()">Filtros</button>
                <button type="submit" name="buscar" value="1">Buscar</button>

                <div id="panelFiltros" style="display:none; margin-top: 10px;">
                    Resumen: <input type="text" name="resumen" placeholder="Buscar por resumen..."><br>
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
        </section>

        <hr>

        <?php if (isset($_GET['buscar'])): ?>
            <?php if (!empty($resultados)): ?>
                <h3>Resultados:</h3>
                <div class="scrollable-table">
                    <table>
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
            <?php else: ?>
                <p>No se encontraron artículos.</p>
            <?php endif; ?>
        <?php endif; ?>

        <hr>

        <?php if (!empty($mejores)): ?>
            <h3>Top Artículos Mejor Calificados</h3>
            <div class="cards-container">
                <?php foreach ($mejores as $i => $m): ?>
                    <div class="articulo-card">
                        <h4><?= htmlspecialchars($m['titulo']) ?></h4>
                        <p><strong>Resumen:</strong> <?= htmlspecialchars($m['resumen']) ?></p>
                        <button class="ver-mas-btn" onclick="toggleDetalles('detalle<?= $i ?>')">Ver más</button>
                        <div id="detalle<?= $i ?>" class="card-extra">
                            <p><strong>Tópicos:</strong> <?= htmlspecialchars($m['topicos']) ?></p>
                            <p><strong>Autores:</strong> <?= htmlspecialchars($m['autores']) ?></p>
                            <p><strong>Calidad Técnica:</strong> <?= number_format($m['promedio_tecnica'], 2) ?></p>
                            <p><strong>Valoración Global:</strong> <?= number_format($m['promedio_valoracion'], 2) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleFiltros() {
            const panel = document.getElementById("panelFiltros");
            panel.style.display = panel.style.display === "none" ? "block" : "none";
        }

        function toggleDetalles(id) {
            const el = document.getElementById(id);
            el.style.display = el.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>