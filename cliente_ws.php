<?php
require 'includes/header.php'; // Hereda tu nuevo diseño corporativo oscuro
require 'includes/conexion.php'; 

// Obtener los departamentos disponibles para llenar el selector
$stmt = $pdo->query("SELECT ID_Condominio, Numero_Departamento FROM Condominios WHERE ID_Estado = 1 ORDER BY Numero_Departamento");
$departamentos = $stmt->fetchAll();

$xmlRequest = "";
$xmlResponse = "";

// Si el usuario envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_condominio'])) {
    $id_a_consultar = (int)$_POST['id_condominio'];
    
    // 1. Armar el Request en XML con el ID dinámico
    $xmlRequest = "<?xml version='1.0' encoding='UTF-8'?>
<ConsultaAdeudoRequest>
    <ID_Condominio>{$id_a_consultar}</ID_Condominio>
</ConsultaAdeudoRequest>";

    // 2. Configurar cURL hacia tu End Point
    $url = 'http://localhost/CONDOMINIOS/ws_adeudos.php'; 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);

    // 3. Ejecutar y obtener el Response
    $xmlResponse = curl_exec($ch);
    curl_close($ch);
}
?>

<div class="card">
    <h2>Cliente Web Service (Consultas Dinámicas)</h2>
    <p style="color: var(--text-main); margin-bottom: 20px;">
        Selecciona un departamento para enviar la petición XML al servidor y consultar sus adeudos en tiempo real.
    </p>
    
    <form method="POST">
        <div class="form-group">
            <label>Departamento a consultar:</label>
            <select name="id_condominio" required>
                <option value="">-- Selecciona un departamento --</option>
                <?php foreach($departamentos as $depto): ?>
                    <option value="<?= $depto['ID_Condominio'] ?>">
                        Departamento <?= $depto['Numero_Departamento'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Ejecutar Petición XML</button>
    </form>
</div>

<?php if ($xmlRequest && $xmlResponse): ?>
<div class="card" style="margin-top: 20px;">
    <h3>1. Petición Enviada al Servidor (Request)</h3>
    <pre style="background: var(--input-bg); color: var(--brand-blue); padding: 15px; border-radius: 4px; border: 1px solid var(--border-color); overflow-x: auto;">
<?= htmlspecialchars($xmlRequest) ?>
    </pre>
    
    <h3 style="margin-top: 25px;">2. Respuesta del Web Service (Response)</h3>
    <pre style="background: var(--input-bg); color: #88d44c; padding: 15px; border-radius: 4px; border: 1px solid var(--border-color); overflow-x: auto;">
<?= htmlspecialchars($xmlResponse) ?>
    </pre>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>