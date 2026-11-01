<?php
require 'includes/conexion.php';

if (isset($_POST['tipo_cuota'])) {
    $tipo = $_POST['tipo_cuota'];

    $stmt = $pdo->prepare("SELECT Precio FROM Cuotas WHERE Tipo_Cuota = ? ORDER BY Año DESC LIMIT 1");
    $stmt->execute([$tipo]);
    $cuota = $stmt->fetch();

    if ($cuota) {
        echo json_encode(['precio' => $cuota['Precio']]);
    } else {
        echo json_encode(['precio' => null]);
    }
}
