<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../conexion.php');

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 1) {
    header("Location: ../dashboard.php");
    exit();
}

// Obtener revisores
$stmt = $conn->query("SELECT * FROM usuarios WHERE subclase IN (3,4)");
$revisores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener especializaciones por revisor
$especializaciones = [];
$stmt = $conn->query("SELECT e.id_usuario, a.titulo_area FROM especializacion e JOIN area a ON e.id_area = a.id_area");
foreach ($stmt as $esp) {
    $especializaciones[$esp['id_usuario']][] = $esp['titulo_area'];
}
$autores = $conn->query("SELECT id_usuario, nombre, email FROM usuarios WHERE subclase = 2")->fetchAll(PDO::FETCH_ASSOC);
$areas = $conn->query("SELECT * FROM area")->fetchAll(PDO::FETCH_ASSOC);

$view = $_GET['view'] ?? 'autores';
?>
<link rel="stylesheet" href="../../public/css/gestionar_revisores.css">

<h2>Gestión de Revisores</h2>
<p><a href="../dashboard.php">← Volver al Dashboard</a></p>

<div class="agregar-revisor-panel">
    <h3>Agregar nuevo revisor</h3>
    <form method="POST" action="../../controladores/crear_revisor.php">
        <div class="input-row">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="password" placeholder="Contraseña" required>
        </div>

        <label>Especialidades:</label>
        <div class="checkbox-group">
            <?php foreach ($areas as $area): ?>
                <label><input type="checkbox" name="areas[]" value="<?= $area['id_area'] ?>"> <?= $area['titulo_area'] ?></label>
            <?php endforeach; ?>
        </div>

        <button type="submit">Agregar Revisor</button>
    </form>
</div>


<p>
    <a href="?view=autores">Vista Autores</a> |
    <a href="?view=revisores">Vista Revisores</a>
</p>

<?php if ($view === 'autores'): ?>
    <h3>Autores Existentes</h3>
    <form method="POST" action="../../controladores/ascender_revisor.php">
        <table border="1" cellpadding="6">
            <tr><th>Nombre</th><th>Email</th><th>Acción</th></tr>
            <?php foreach ($autores as $autor): ?>
                <tr>
                    <td><?= htmlspecialchars($autor['nombre']) ?></td>
                    <td><?= htmlspecialchars($autor['email']) ?></td>
                    <td>
                        <button type="submit" name="id_usuario" value="<?= $autor['id_usuario'] ?>">Autor a Revisor</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>
<?php elseif ($view === 'revisores'): ?>
    <h3>Revisores Actuales</h3>
    <table border="1" cellpadding="6">
        <tr><th>Nombre</th><th>Email</th><th>Especializaciones</th><th>Acciones</th></tr>
        <?php foreach ($revisores as $rev): ?>
            <tr>
                <td><?= htmlspecialchars($rev['nombre']) ?></td>
                <td><?= htmlspecialchars($rev['email']) ?></td>
                <td><?= isset($especializaciones[$rev['id_usuario']]) ? implode(', ', $especializaciones[$rev['id_usuario']]) : 'Sin especialización' ?></td>
                <td>
                    <a href="editar_revisor.php?id=<?= $rev['id_usuario'] ?>">Editar</a> |
                    <a href="../../controladores/eliminar_revisor.php?id=<?= $rev['id_usuario'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este revisor?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
