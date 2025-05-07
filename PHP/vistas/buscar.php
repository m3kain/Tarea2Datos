<?php include('header.php'); ?>

<h2>Buscar Artículos</h2>

<form method="GET" action="../controladores/BuscarArticuloController.php">
    <input type="text" name="q" placeholder="Buscar por título..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button type="submit">Buscar</button>
</form>

<?php if (isset($resultados)): ?>
    <h3>Resultados:</h3>
    <ul>
        <?php if (count($resultados) === 0): ?>
            <li>No se encontraron artículos.</li>
        <?php else: ?>
            <?php foreach ($resultados as $articulo): ?>
                <li><strong><?= htmlspecialchars($articulo['titulo']) ?></strong><br>
                    <?= htmlspecialchars($articulo['resumen']) ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<?php include('footer.php'); ?>
