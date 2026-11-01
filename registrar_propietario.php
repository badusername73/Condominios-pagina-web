<?php
require 'includes/conexion.php';

// Obtener condominios en venta
$condominios = $pdo->query("SELECT c.ID_Condominio, c.Numero_Departamento
                           FROM Condominios c
                           WHERE c.ID_Estado = 2")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $condominio_id = $_POST['condominio'];
    $propietario = $_POST['propietario'];
    $fecha = $_POST['fecha'] ?: null;
    
    try {
        $stmt = $pdo->prepare("UPDATE Condominios SET Propietario = ?, Fecha_Adquisicion = ?, ID_Estado = 1 WHERE ID_Condominio = ?");
        $stmt->execute([$propietario, $fecha, $condominio_id]);
        $success = "Propietario registrado correctamente";
    } catch (PDOException $e) {
        $error = "Error al registrar el propietario: " . $e->getMessage();
    }
}
?>

<?php require 'includes/header.php'; ?>

<div class="container">
    <a href="index.php" class="back-btn">← Volver</a>
    
    <h1>Registrar Nuevo Propietario</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label>Condominio:</label>
            <select name="condominio" required>
                <option value="">Seleccione un condominio</option>
                <?php foreach ($condominios as $c): ?>
                <option value="<?= $c['ID_Condominio'] ?>">
                    Departamento #<?= $c['Numero_Departamento'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Nombre del Propietario:</label>
            <input type="text" name="propietario" required>
        </div>
        
        <div class="form-group">
            <label>Fecha de Adquisición (opcional):</label>
            <input type="date" name="fecha">
        </div>
        
        <button type="submit" class="btn">Registrar Propietario</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>