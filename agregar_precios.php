<?php
require 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $precio = (float)$_POST['precio'];
    
    $existe = $pdo->prepare("SELECT ID_Cuota FROM Cuotas WHERE Tipo_Cuota = ?");
    $existe->execute([$tipo]);
    
    try {
        if ($existe->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE Cuotas SET Precio = ? WHERE Tipo_Cuota = ?");
            $stmt->execute([$precio, $tipo]);
            $success = "Precio de $tipo actualizado correctamente";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Cuotas (Tipo_Cuota, Precio) VALUES (?, ?)");
            $stmt->execute([$tipo, $precio]);
            $success = "Precio registrado correctamente";
        }
    } catch (PDOException $e) {
        $error = "Error al procesar el precio: " . $e->getMessage();
    }
}

$cuotas = $pdo->query("SELECT * FROM Cuotas ORDER BY Tipo_Cuota")->fetchAll();
?>

<?php require 'includes/header.php'; ?>

<div class="container">
    <a href="index.php" class="back-btn">← Volver</a>
    
    <h1>Gestión de Precios de Cuotas</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label>Tipo de Cuota:</label>
            <select name="tipo" required>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Mantenimiento Extemporanea">Mantenimiento Extemporáneo</option>
                <option value="Extraordinario">Extraordinario</option>
                <option value="Extraordinario Extemporanea">Extraordinario Extemporáneo</option>
                <option value="Permiso Construccion">Permiso Construcción</option>
                <option value="Constancia">Constancia</option>
                <option value="Multa">Multa</option>
                <option value="Reincidencia">Reincidencia</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Precio:</label>
            <input type="number" step="0.01" name="precio" required>
        </div>
        
        <button type="submit" class="btn">Registrar Precio</button>
    </form>
    
    <h2>Precios Registrados</h2>
    <table class="precios-table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuotas as $cuota): ?>
            <tr>
                <td><?= htmlspecialchars($cuota['Tipo_Cuota']) ?></td>
                <td>$<?= number_format($cuota['Precio'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/footer.php'; ?>