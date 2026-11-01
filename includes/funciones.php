<?php
require 'conexion.php';

/**
 * Calcula el monto a pagar considerando recargos por extemporaneidad
 */
function calcularMontoPago($tipoCuota, $precioBase, $fechaPago, $fechaLimite) {
    $recargo = 0;
    
    if (strpos($tipoCuota, 'Mantenimiento') !== false && 
        strtotime($fechaPago) > strtotime($fechaLimite)) {
        $recargo = $precioBase * 0.05;
    }
    
    if (strpos($tipoCuota, 'Extraordinario') !== false && 
        strtotime($fechaPago) > strtotime($fechaLimite)) {
        $recargo = $precioBase * 0.05;
    }
    
    return [
        'total' => $precioBase + $recargo,
        'recargo' => $recargo,
        'esExtemporaneo' => $recargo > 0
    ];
}

/**
 * Verifica si un condominio tiene adeudos
 */
function tieneAdeudos($condominioId, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(Monto_Restante) FROM Pagos 
                          JOIN Historial ON Pagos.ID_Pagos = Historial.ID_Pagos
                          WHERE Historial.ID_Condominio = ?");
    $stmt->execute([$condominioId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Genera un número de comprobante único
 */
function generarNumeroComprobante() {
    return 'CBH-' . date('Ymd') . '-' . strtoupper(uniqid());
}
function obtenerPrecioCuota($pdo, $tipoCuota) {
    $stmt = $pdo->prepare("SELECT Precio FROM Cuotas WHERE Tipo_Cuota = ? ORDER BY Año DESC LIMIT 1");
    $stmt->execute([$tipoCuota]);
    $cuota = $stmt->fetch();
    
    return $cuota ? $cuota['Precio'] : 0;
}
?>