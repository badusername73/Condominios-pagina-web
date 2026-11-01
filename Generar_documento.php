<?php 
require 'vendor/autoload.php';
require 'includes/conexion.php';

use Dompdf\Dompdf;

// Obtener parámetros
$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? 0;
$pago_id = isset($_GET['pago_id']) ? intval($_GET['pago_id']) : null;

// Si el tipo es recibo_pago, obtener el idCondominio vía pago_id
$idCondominio = null;
if ($tipo === 'recibo_pago' && $pago_id) {
    $stmt = $pdo->prepare("SELECT h.ID_Condominio FROM Historial h WHERE h.ID_Pagos = ?");
    $stmt->execute([$pago_id]);
    $idCondominio = $stmt->fetchColumn();
}

// Validación inicial para mostrar el formulario cuando falten datos
if (!$tipo || (!is_numeric($id) && $tipo !== 'recibo_pago')) {
    echo "<h1>Generar Documento PDF</h1>
    <form method='get'>
        <label for='tipo'>Selecciona el tipo de documento:</label><br>
        <select name='tipo' id='tipo' required>
            <option value=''>-- Elige una opción --</option>
            <option value='recibo_pago' ".($tipo === 'recibo_pago' ? 'selected' : '').">Recibo de Pago</option>
            <option value='permiso_construccion' ".($tipo === 'permiso_construccion' ? 'selected' : '').">Permiso de Construcción</option>
            <option value='constancia_no_adeudo' ".($tipo === 'constancia_no_adeudo' ? 'selected' : '').">Constancia de No Adeudo</option>
            <option value='ingresos' ".($tipo === 'ingresos' ? 'selected' : '').">Reporte de Ingresos</option>
        </select><br><br>

        <label for='pago_id'>ID de Pago (solo para recibo de pago):</label><br>
        <input type='number' name='pago_id' id='pago_id' value='".htmlspecialchars($pago_id)."' ".($tipo === 'recibo_pago' ? 'required' : '')."><br><br>

        <input type='submit' value='Generar PDF'>
    </form>";
    exit;
}

// Funciones para obtener datos
function fetchDatosPago($pdo, $idPago) {
    $sql = "SELECT p.*, c.Propietario, c.Numero_Departamento, cu.Tipo_Cuota
            FROM Pagos p
            JOIN Historial h ON h.ID_Pagos = p.ID_Pagos
            JOIN Condominios c ON c.ID_Condominio = h.ID_Condominio
            JOIN Cuotas cu ON p.ID_Cuota = cu.ID_Cuota
            WHERE p.ID_Pagos = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idPago]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchDatosCondominio($pdo, $idCondominio) {
    $sql = "SELECT c.Propietario, c.Numero_Departamento, c.Fecha_Adquisicion
            FROM Condominios c
            WHERE c.ID_Condominio = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idCondominio]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Validaciones de IDs específicas
if ($tipo === 'recibo_pago' && (!$pago_id || !is_numeric($pago_id))) {
    die("Debe especificar un pago_id válido para generar el recibo de pago.");
} elseif ($tipo !== 'recibo_pago' && (!$id || !is_numeric($id))) {
    die("Debe especificar un ID de condominio válido para generar el documento.");
}

$dompdf = new Dompdf();
$html = "";

switch ($tipo) {
    case 'recibo_pago':
        $datos = fetchDatosPago($pdo, $pago_id);
        if (!$datos) die("Pago no encontrado.");

        $montoPagado = $datos['Primer_Pago'] + $datos['Segundo_Pago'];
        $fechaActual = date("d/m/Y");
        
        // Variable de dirección
        $direccion = "Departamento #{$datos['Numero_Departamento']}";

        $html = "
        <style>
            body { font-family: Arial, sans-serif; font-size: 14px; }
            h1 { color: #2E86C1; text-align: center; }
            .resumen { margin-top: 20px; }
            .resumen p { margin: 4px 0; }
            .footer { margin-top: 30px; text-align: center; font-style: italic; }
        </style>
        <h1>Recibo de Pago</h1>
        <p><strong>Fecha de emisión:</strong> {$fechaActual}</p>
        <div class='resumen'>
            <p><strong>No. de Recibo:</strong> #{$datos['ID_Pagos']}</p>
            <p><strong>Propietario:</strong> {$datos['Propietario']}</p>
            <p><strong>Dirección:</strong> {$direccion}</p>
            <p><strong>Concepto:</strong> {$datos['Tipo_Cuota']}</p>
            <p><strong>Primer Pago:</strong> $" . number_format($datos['Primer_Pago'], 2) . "</p>
            <p><strong>Segundo Pago:</strong> $" . number_format($datos['Segundo_Pago'], 2) . "</p>
            <p><strong>Total Pagado:</strong> $" . number_format($montoPagado, 2) . "</p>
            <p><strong>Fecha del Pago:</strong> {$datos['Fecha_Primer_Pago']}</p>
            <p><strong>Saldo Restante:</strong> $" . number_format($datos['Monto_Restante'], 2) . "</p>
        </div>
        <div class='footer'>
            <p>Gracias por su pago.</p>
        </div>";
        break;

    case 'permiso_construccion':
        $sqlDeudaPermiso = "SELECT SUM(Monto_Restante) AS deuda 
                           FROM Pagos p
                           JOIN Historial h ON p.ID_Pagos = h.ID_Pagos
                           JOIN Cuotas cu ON p.ID_Cuota = cu.ID_Cuota
                           WHERE h.ID_Condominio = ? AND cu.Tipo_Cuota = 'Permiso de Construcción' AND Monto_Restante > 0";
        $stmtPermiso = $pdo->prepare($sqlDeudaPermiso);
        $stmtPermiso->execute([$id]);
        $deudaPermiso = $stmtPermiso->fetch(PDO::FETCH_ASSOC)['deuda'] ?? 0;

        if ($deudaPermiso > 0) {
            die("<h2>No se puede generar el Permiso de Construcción porque existen adeudos pendientes de este concepto.</h2>");
        }

        $datos = fetchDatosCondominio($pdo, $id);
        if (!$datos) die("Condominio no encontrado.");

        // Variable de dirección
        $direccion = "Departamento #{$datos['Numero_Departamento']}";

        $html = "
        <style>
            body { font-family: Arial, sans-serif; font-size: 14px; }
            h1 { color: #28B463; }
            p { margin: 5px 0; }
        </style>
        <h1>Permiso de Construcción</h1>
        <p><strong>Propietario:</strong> {$datos['Propietario']}</p>
        <p><strong>Dirección:</strong> {$direccion}</p>
        <p><strong>Fecha de Adquisición:</strong> {$datos['Fecha_Adquisicion']}</p>
        <p>Se autoriza la construcción conforme a las normativas vigentes.</p>";
        break;

    case 'constancia_no_adeudo':
        $datos = fetchDatosCondominio($pdo, $id);
        if (!$datos) die("Condominio no encontrado.");

        $sqlDeudaTotal = "SELECT SUM(Monto_Restante) as deuda FROM Pagos p 
                          JOIN Historial h ON p.ID_Pagos = h.ID_Pagos 
                          WHERE h.ID_Condominio = ? AND Monto_Restante > 0";
        $stmtTotal = $pdo->prepare($sqlDeudaTotal);
        $stmtTotal->execute([$id]);
        $deudaTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC)['deuda'] ?? 0;

        if ($deudaTotal > 0) {
            die("<h2>No se puede generar la Constancia de No Adeudo porque existen adeudos pendientes.</h2>");
        }

        // Variable de dirección
        $direccion = "Departamento #{$datos['Numero_Departamento']}";

        $html = "
        <style>body { font-family: Arial, sans-serif; font-size: 14px; }</style>
        <h1>Constancia de No Adeudo</h1>
        <p>Se certifica que el propietario <strong>{$datos['Propietario']}</strong> del condominio ubicado en <strong>{$direccion}</strong> no tiene adeudos pendientes hasta la fecha.</p>";
        break;

    case 'ingresos':
        $sqlIngresos = "SELECT cu.Tipo_Cuota, SUM(p.Primer_Pago + p.Segundo_Pago) AS total_ingresos
                        FROM Pagos p
                        JOIN Cuotas cu ON p.ID_Cuota = cu.ID_Cuota
                        GROUP BY cu.Tipo_Cuota";
        $stmtIngresos = $pdo->query($sqlIngresos);
        $ingresos = $stmtIngresos->fetchAll(PDO::FETCH_ASSOC);

        $html = "
        <style>body { font-family: Arial, sans-serif; font-size: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3498DB; color: white; }
        </style>
        <h1>Reporte de Ingresos</h1>
        <table>
            <thead>
                <tr><th>Concepto</th><th>Total Ingresos</th></tr>
            </thead>
            <tbody>";
        foreach ($ingresos as $fila) {
            $html .= "<tr><td>{$fila['Tipo_Cuota']}</td><td>$" . number_format($fila['total_ingresos'], 2) . "</td></tr>";
        }
        $html .= "</tbody></table>";
        break;

    default:
        die("Tipo de documento no válido.");
}

// Renderizar y mostrar PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("documento_{$tipo}.pdf", ["Attachment" => false]);
exit;