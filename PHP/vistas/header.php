
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<?php
$current = basename($_SERVER['PHP_SELF']);
$tituloPagina = $tituloPagina ?? 'Sistema de Congreso';

function linkItem($href, $label, $current) {
    if (basename($href) === $current) return ''; // no mostrar si es la actual
    return "<a href=\"$href\" style=\"color: white; margin-right: 15px;\">$label</a>";
}
?>
<link rel="stylesheet" href="/Proyecto/PHP/public/css/styles.css">


<header style="background: #007bff; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
    <div style="font-size: 20px; font-weight: bold; color: white;">
        <?= htmlspecialchars($tituloPagina) ?>
    </div>
    <nav>
    <style>
        header nav a {
            position: relative;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        header nav a::after {
            content: "";
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #fff;
            transform-origin: bottom right;
            transition: transform 0.3s ease-out;
        }

        header nav a:hover {
            color: #ffc107;
        }

        header nav a:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        body.fade-in {
            opacity: 1;
            transition: opacity 0.3s ease-in;
        }

        body.fade-out {
            opacity: 0;
            transition: opacity 0.3s ease-out;
        }
        
    </style>

        <?= linkItem('/Proyecto/PHP/vistas/dashboard.php', 'Inicio', $current) ?>
        <?= linkItem('/Proyecto/PHP/vistas/perfil.php', 'Perfil', $current) ?>
        <?php if (in_array($_SESSION['rol'], [2, 3, 4])): ?>
            <?= linkItem('/Proyecto/PHP/vistas/autor/enviar_articulo.php', 'Enviar artículo', $current) ?>
        <?php endif; ?>
        <?php if (in_array($_SESSION['rol'], [3, 4])): ?>
            <?= linkItem('/Proyecto/PHP/vistas/revisor/evaluar_articulos.php', 'Evaluar artículos', $current) ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['rol']) && $_SESSION['rol'] == 1): ?>
            <?= linkItem('/Proyecto/PHP/vistas/jefe/gestionar_revisores.php', 'Gestión de revisores', $current) ?>
            <?= linkItem('/Proyecto/PHP/vistas/jefe/gestion_asignaciones.php', 'Gestión de asignaciones', $current) ?>
        <?php endif; ?>
    </nav>
    <script src="/Proyecto/PHP/public/js/global.js"></script>

</header>

