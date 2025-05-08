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

// Obtener autores
$stmtAutores = $conn->query("SELECT id_usuario, nombre, email FROM usuarios WHERE subclase = 2");
$autores = $stmtAutores->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gestión de Revisores</h2>
<p><a href="../dashboard.php">← Volver al Dashboard</a></p>

<?php if (isset($_GET['noti'])): ?>
<script>
    alert(decodeURIComponent(`<?= $_GET['noti'] ?>`));
</script>
<?php endif; ?>

<h3>Agregar nuevo revisor</h3>
<form method="POST" action="../../controladores/crear_revisor.php">
    Nombre: <input type="text" name="nombre" required>
    Email: <input type="email" name="email" required>
    Password: <input type="text" name="password" required>
    <br><br>
    Especialidades:<br>
    <?php
    $areas = $conn->query("SELECT * FROM area")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($areas as $area): ?>
        <label><input type="checkbox" name="areas[]" value="<?= $area['id_area'] ?>"> <?= $area['titulo_area'] ?></label><br>
    <?php endforeach; ?>
    <br>
    <button type="submit">Agregar Revisor</button>
</form>

<h2>Autores existentes</h2>
<form method="POST" action="../../controladores/ascender_revisor.php">
    <table border="1" cellpadding="6">
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Acción</th>
        </tr>
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

<hr>

<table border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Especializaciones</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
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
    </tbody>
</table>
