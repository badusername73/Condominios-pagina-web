<?php
require 'includes/conexion.php';

// Indicar que la respuesta será un documento XML
header('Content-Type: text/xml; charset=utf-8');

// Recibir el XML del cliente
$xmlInput = file_get_contents('php://input');

if (!$xmlInput) {
    die('<error>No se recibió ninguna solicitud XML</error>');
}

// Convertir el string XML a un objeto
$request = simplexml_load_string($xmlInput);
$id_condominio = (int)$request->ID_Condominio;

try {
    // Llamar al Stored Procedure
    $stmt = $pdo->prepare("CALL ConsultarAdeudoCondominio(?)");
    $stmt->execute([$id_condominio]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generar el XML de respuesta
    $xmlResponse = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ConsultaAdeudoResponse/>');
    
    if ($resultado) {
        $xmlResponse->addChild('Propietario', htmlspecialchars($resultado['Propietario'] ?? 'Sin asignar'));
        $xmlResponse->addChild('Numero_Departamento', $resultado['Numero_Departamento']);
        $xmlResponse->addChild('Deuda_Total', $resultado['Deuda_Total']);
    } else {
        $xmlResponse->addChild('Error', 'Departamento no encontrado');
    }

    echo $xmlResponse->asXML();

} catch (PDOException $e) {
    echo '<error>Error de base de datos: ' . htmlspecialchars($e->getMessage()) . '</error>';
}
?>