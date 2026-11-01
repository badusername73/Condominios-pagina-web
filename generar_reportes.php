<?php
require 'vendor/autoload.php';
require 'includes/conexion.php'; 

use Dompdf\Dompdf;

$reporte = $_GET['reporte'] ?? '';

$dompdf = new Dompdf();

try {
    switch ($reporte) {
        case 'morosos':
            $sql = "SELECT c.Propietario, c.Numero_Departamento, SUM(p.Monto_Restante) AS deuda
                    FROM Condominios c
                    JOIN Pagos p ON p.ID_Pagos IN (SELECT h.ID_Pagos FROM Historial h WHERE h.ID_Condominio = c.ID_Condominio)
                    WHERE p.Monto_Restante > 0
                    GROUP BY c.ID_Condominio
                    ORDER BY deuda DESC";
            
            $stmt = $pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $html = "<h1>Reporte de Condóminos Morosos</h1><table border='1' cellpadding='5' cellspacing='0'>
                    <tr><th>Propietario</th><th>Dirección</th><th>Deuda Pendiente</th></tr>";

            foreach ($result as $row) {
                // Variable de dirección
                $direccion = "Departamento #{$row['Numero_Departamento']}";
                
                $html .= "<tr>
                    <td>{$row['Propietario']}</td>
                    <td>{$direccion}</td>
                    <td>$" . number_format($row['deuda'], 2) . "</td>
                </tr>";
            }
            $html .= "</table>";
            break;

        case 'ingresos_por_concepto':
            $sql = "SELECT cu.Tipo_Cuota, SUM(p.Primer_Pago + p.Segundo_Pago) AS total_ingresos
                    FROM Pagos p
                    JOIN Cuotas cu ON p.ID_Cuota = cu.ID_Cuota
                    GROUP BY cu.Tipo_Cuota
                    ORDER BY total_ingresos DESC";

            $stmt = $pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $html = "<h1>Reporte de Ingresos por Concepto</h1><table border='1' cellpadding='5' cellspacing='0'>
                    <tr><th>Concepto</th><th>Total Ingresos</th></tr>";

            foreach ($result as $row) {
                $html .= "<tr>
                    <td>{$row['Tipo_Cuota']}</td>
                    <td>$" . number_format($row['total_ingresos'], 2) . "</td>
                </tr>";
            }
            $html .= "</table>";
            break;

        case 'ingresos_por_tiempo':
            $sql = "SELECT 
                        YEAR(p.Fecha_Primer_Pago) AS anio, 
                        MONTH(p.Fecha_Primer_Pago) AS mes, 
                        DAY(p.Fecha_Primer_Pago) AS dia, 
                        SUM(p.Primer_Pago + p.Segundo_Pago) AS total_ingresos
                    FROM Pagos p
                    GROUP BY anio, mes, dia
                    ORDER BY anio DESC, mes DESC, dia DESC";

            $stmt = $pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $html = "<h1>Reporte de Ingresos por Tiempo (Año/Mes/Día)</h1><table border='1' cellpadding='5' cellspacing='0'>
                    <tr><th>Año</th><th>Mes</th><th>Día</th><th>Total Ingresos</th></tr>";

            foreach ($result as $row) {
                $html .= "<tr>
                    <td>{$row['anio']}</td>
                    <td>{$row['mes']}</td>
                    <td>{$row['dia']}</td>
                    <td>$" . number_format($row['total_ingresos'], 2) . "</td>
                </tr>";
            }
            $html .= "</table>";
            break;

        case 'ingresos_por_condominio':
            $sql = "SELECT c.Propietario, c.Numero_Departamento, SUM(p.Primer_Pago + p.Segundo_Pago) AS total_ingresos
                    FROM Condominios c
                    JOIN Pagos p ON p.ID_Pagos IN (SELECT h.ID_Pagos FROM Historial h WHERE h.ID_Condominio = c.ID_Condominio)
                    GROUP BY c.ID_Condominio
                    ORDER BY total_ingresos DESC";

            $stmt = $pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $html = "<h1>Reporte de Ingresos por Condómino</h1><table border='1' cellpadding='5' cellspacing='0'>
                    <tr><th>Propietario</th><th>Dirección</th><th>Total Ingresos</th></tr>";

            foreach ($result as $row) {
                // Variable de dirección
                $direccion = "Departamento #{$row['Numero_Departamento']}";
                
                $html .= "<tr>
                    <td>{$row['Propietario']}</td>
                    <td>{$direccion}</td>
                    <td>$" . number_format($row['total_ingresos'], 2) . "</td>
                </tr>";
            }
            $html .= "</table>";
            break;

        default:
            $html = "<h1>Reporte no válido</h1><p>Por favor, seleccione un reporte válido.</p>";
            break;
    }
} catch (PDOException $e) {
    $html = "<h1>Error en la consulta</h1><p>{$e->getMessage()}</p>";
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_" . $reporte . ".pdf", array("Attachment" => false));

exit;
?>