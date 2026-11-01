<?php  
require 'includes/conexion.php';
require 'includes/funciones.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener información del condominio
$stmt = $pdo->prepare("SELECT c.*, e.Estado 
                       FROM Condominios c
                       JOIN Estado e ON c.ID_Estado = e.ID_Estado
                       WHERE c.ID_Condominio = ?");
$stmt->execute([$id]);
$condominio = $stmt->fetch();

if (!$condominio) {
    header("Location: index.php?error=Condominio no encontrado");
    exit;
}

// Procesar creación de nueva deuda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_deuda'])) {
    $tipo_cuota = $_POST['tipo_cuota'];
    $fecha_limite = $_POST['fecha_limite'];
    $detalles = $_POST['detalles'] ?? null;

    try {
        $pdo->beginTransaction();

        // Obtener cuota base y fecha correspondiente
        $stmt = $pdo->prepare("SELECT c.*, fc.ID_FechaPago, fc.Fecha_Final
                              FROM Cuotas c
                              LEFT JOIN Fecha_Cuotas fc ON c.ID_Cuota = fc.ID_Cuota
                              WHERE c.Tipo_Cuota = ? 
                              
                              LIMIT 1");
        $stmt->execute([$tipo_cuota]);
        $cuota = $stmt->fetch();

        if (!$cuota) {
            throw new Exception("No se encontró tarifa para este tipo de cuota");
        }

        $monto_total = (float)$cuota['Precio'];

        // Calcular recargo si se pasó la fecha límite
        $fecha_actual = date('Y-m-d');
        if (strtotime($fecha_limite) < strtotime($fecha_actual)) {
            $monto_total *= 1.05; // Recargo del 5%
        }

        // Insertar en tablas especiales si es necesario
        $id_especial = null;
        if ($tipo_cuota === 'Multa') {
            if (empty($detalles)) {
                throw new Exception("Debe proporcionar detalles para la multa");
            }

            // Revisar reincidencia: si ya tiene multas con la misma razón
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Historial h
                                   JOIN Pagos p ON h.ID_Pagos = p.ID_Pagos
                                   JOIN Multas m ON p.ID_Multa = m.ID_Multa
                                   WHERE h.ID_Condominio = ? AND m.Razon = ?");
            $stmt->execute([$id, $detalles]);
            $reincidencias = $stmt->fetchColumn();

            if ($reincidencias >= 1) {
                $monto_total *= 1.5; // 50% adicional por reincidencia
            }

            $stmt = $pdo->prepare("INSERT INTO Multas (Razon) VALUES (?)");
            $stmt->execute([$detalles]);
            $id_especial = $pdo->lastInsertId();
        }

        // Registrar deuda (sin pago inicial)
        $stmt = $pdo->prepare("INSERT INTO Pagos 
                              (Primer_Pago, Fecha_Primer_Pago, Segundo_Pago, Fecha_Segundo_Pago, 
                              Monto_Restante, ID_Cuota, ID_FechaPago" .
                              ($id_especial ? ", ID_Multa" : "") . ")
                              VALUES (0, NULL, 0, NULL, ?, ?, ?" . ($id_especial ? ", ?" : "") . ")");

        $params = [$monto_total, $cuota['ID_Cuota'], $cuota['ID_FechaPago']];
        if ($id_especial) $params[] = $id_especial;

        $stmt->execute($params);
        $pago_id = $pdo->lastInsertId();

        // Registrar en historial
        $stmt = $pdo->prepare("INSERT INTO Historial 
                              (ID_Condominio, ID_Cuota, ID_Pagos, ID_FechaPago) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $cuota['ID_Cuota'], $pago_id, $cuota['ID_FechaPago']]);

        $pdo->commit();

        header("Location: condominio.php?id=$id&success=Deuda registrada correctamente");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Procesar pagos si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago'])) {
    $pago_id = (int)$_POST['pago_id'];
    $monto = (float)$_POST['monto'];
    $fecha = $_POST['fecha'];
    $metodo_pago = $_POST['metodo_pago'];
    
    try {
        $pdo->beginTransaction();
        
        // Obtener información del pago
        $stmt = $pdo->prepare("SELECT * FROM Pagos WHERE ID_Pagos = ?");
        $stmt->execute([$pago_id]);
        $pago = $stmt->fetch();
        
        if (!$pago) {
            throw new Exception("Pago no encontrado");
        }
        
        // Validar monto
        if ($monto <= 0 || $monto > $pago['Monto_Restante']) {
            throw new Exception("Monto inválido. Debe ser entre $0 y $" . number_format($pago['Monto_Restante'], 2));
        }
        
        // Actualizar pago (primer o segundo pago)
        if ($pago['Primer_Pago'] == 0) {
            // Primer pago
            $stmt = $pdo->prepare("UPDATE Pagos SET 
                                 Primer_Pago = ?, 
                                 Fecha_Primer_Pago = ?,
                                 ID_Tipo_Primer_Pago = ?,
                                 Monto_Restante = Monto_Restante - ?
                                 WHERE ID_Pagos = ?");
        } else {
            // Segundo pago
            $stmt = $pdo->prepare("UPDATE Pagos SET 
                                 Segundo_Pago = ?, 
                                 Fecha_Segundo_Pago = ?,
                                 ID_Tipo_Segundo_Pago = ?,
                                 Monto_Restante = Monto_Restante - ?
                                 WHERE ID_Pagos = ?");
        }
        $stmt->execute([$monto, $fecha, $metodo_pago, $monto, $pago_id]);
        
        $pdo->commit();
        
        header("Location: condominio.php?id=$id&success=Pago registrado correctamente");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Obtener historial de pagos con detalles de multa (si aplica)
$historial = $pdo->prepare("
    SELECT h.*, p.*, c.Tipo_Cuota, c.Precio, m.Razon,
           tp1.Tipo_Pago AS Metodo_Primer_Pago,
           tp2.Tipo_Pago AS Metodo_Segundo_Pago,
           CASE 
               WHEN p.Monto_Restante <= 0 THEN 'Completado'
               ELSE 'Pendiente'
           END AS Estado,
           fc.Fecha_Inicial
    FROM Historial h
    JOIN Pagos p ON h.ID_Pagos = p.ID_Pagos
    JOIN Cuotas c ON h.ID_Cuota = c.ID_Cuota
    LEFT JOIN Fecha_Cuotas fc ON h.ID_FechaPago = fc.ID_FechaPago
    LEFT JOIN Multas m ON p.ID_Multa = m.ID_Multa
    LEFT JOIN Tipo_Pago tp1 ON p.ID_Tipo_Primer_Pago = tp1.ID_Tipo_Pago
    LEFT JOIN Tipo_Pago tp2 ON p.ID_Tipo_Segundo_Pago = tp2.ID_Tipo_Pago
    WHERE h.ID_Condominio = ?
    ORDER BY COALESCE(p.Fecha_Primer_Pago, p.Fecha_Segundo_Pago, fc.Fecha_Inicial) DESC
");
$historial->execute([$id]);

// Obtener tipos de cuota disponibles
// Obtener tipos de cuota disponibles
$tipos_cuota = $pdo->query("SELECT Tipo_Cuota, Precio FROM Cuotas ORDER BY Tipo_Cuota")->fetchAll();

// Obtener métodos de pago
$metodos_pago = $pdo->query("SELECT * FROM Tipo_Pago")->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <a href="index.php" class="back-btn">← Volver</a>

    <h1>Departamento #<?= htmlspecialchars($condominio['Numero_Departamento']) ?></h1>
    
    <div class="condominio-info">
        <p><strong>Propietario:</strong> <?= htmlspecialchars($condominio['Propietario'] ?? 'No asignado') ?></p>
        <p><strong>Ubicación:</strong> Edificio Principal</p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($condominio['Estado']) ?></p>
        <p><strong>Fecha de Adquisición:</strong> <?= $condominio['Fecha_Adquisicion'] ?? 'No registrada' ?></p>
    </div>
   
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <!-- Formulario para crear deuda -->
     <div class="seccion-deuda">
        <h2>Registrar Nueva Deuda</h2>
        <form method="post" class="form-deuda">
            <input type="hidden" name="crear_deuda" value="1">
            <label>Tipo de Cuota:
                <select name="tipo_cuota" id="tipo_cuota" required>
                    <option value="">Seleccione un tipo</option>
                    <?php foreach ($tipos_cuota as $tipo): ?>
                        <option value="<?= htmlspecialchars($tipo['Tipo_Cuota']) ?>" 
                            data-precio="<?= htmlspecialchars($tipo['Precio']) ?>">
                            <?= htmlspecialchars($tipo['Tipo_Cuota']) ?> ($<?= number_format($tipo['Precio'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <label>Monto Total: 
                <input type="number" name="monto_total" id="monto_total" min="0.01" step="0.01" required readonly>
            </label><br>
            <label>Fecha Límite: 
                <input type="date" name="fecha_limite" required>
            </label><br>
            <div id="detalles_container" style="display:none;">
                <label>Detalles (obligatorio para multas):
                    <textarea name="detalles" rows="2" required></textarea>
                </label><br>
            </div>
            <button type="submit">Registrar Deuda</button>
        </form>
    </div>
    
<!-- Mini menú para seleccionar tipo de documento a generar -->
<form action="generar_documento.php" method="get" style="display:inline-block;">
    <input type="hidden" name="id" value="<?= $id ?>">
    <label for="tipo_documento">Selecciona el documento a generar:</label>
    <select name="tipo" id="tipo_documento" required>
        <option value="">-- Elige un documento --</option>
        <option value="constancia_no_adeudo">Constancia de No Adeudo</option>
        <option value="permiso_construccion">Permiso de Construcción</option>
        <!-- Puedes agregar más tipos aquí -->
    </select>
    <button type="submit" class="btn btn-success">Generar Documento (PDF)</button>
</form>
</div>
    <!-- Historial de pagos y deudas -->
    <div class="seccion-historial">
        <h2>Historial de Pagos y Deudas</h2>
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
            <thead>
                <tr>
                    <th>Tipo Cuota</th>
                    <th>Fecha Límite</th>
                    <th>Monto Total</th>
                    <th>Monto 1er pago</th>
                    <th>Fecha 1er Pago</th>
                    <th>Monto 2do Pago</th>
                    <th>Fecha 2do Pago</th>
                    <th>Estado</th>
                    <th>Detalles (Multa)</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
<?php while ($row = $historial->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
        <td><?= htmlspecialchars($row['Tipo_Cuota']) ?></td>
        <td><?= htmlspecialchars($row['Fecha_Inicial']) ?></td>
        <td>$<?= number_format($row['Precio'], 2) ?></td>
        <td>$<?= number_format($row['Primer_Pago'] ?? 0, 2) ?></td>
        <td><?= htmlspecialchars($row['Fecha_Primer_Pago'] ?? '-') ?></td>
        <td>$<?= number_format($row['Segundo_Pago'] ?? 0, 2) ?></td>
        <td><?= htmlspecialchars($row['Fecha_Segundo_Pago'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['Estado']) ?></td>
    <td><?= htmlspecialchars($row['Detalles_Multa'] ?? '-') ?></td>
    <td>
        <!-- Botón para pagar e imprimir recibo en una sola celda -->
        <?php if (($row['Monto_Restante'] ?? 0) > 0): ?>
            <button 
                type="button" 
                class="btn-pagar" 
                data-pago-id="<?= $row['ID_Pagos'] ?>" 
                data-tipo-cuota="<?= htmlspecialchars($row['Tipo_Cuota']) ?>" 
                data-monto-restante="<?= $row['Monto_Restante'] ?>"
            >
                Pagar
            </button>
        <?php endif; ?>

        <?php if (($row['Primer_Pago'] ?? 0) > 0 || ($row['Segundo_Pago'] ?? 0) > 0): ?>
            <a href="generar_documento.php?pago_id=<?= $row['ID_Pagos'] ?>" target="_blank" class="btn btn-secondary">🧾 Imprimir Recibo</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
        </table>
    </div>

    <!-- Formulario para registrar pago -->
    <div class="seccion-pago" style="margin-top:20px;">
        <h2>Registrar Pago</h2>
        <form method="post" id="formPago" class="form-pago">
            <input type="hidden" name="registrar_pago" value="1">
            <input type="hidden" name="pago_id" id="pago_id" required>
            <label>Tipo de Cuota:
                <input type="text" id="tipo_cuota_pago" readonly>
            </label><br>
            <label>Monto a Pagar (máx <span id="max_monto_display">0.00</span>):
                <input type="number" name="monto" id="monto_pago" min="0.01" step="0.01" required>
            </label><br>
            <label>Fecha de Pago:
                <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </label><br>
            <label>Método de Pago:
                <select name="metodo_pago" required>
                    <?php foreach ($metodos_pago as $mp): ?>
                        <option value="<?= $mp['ID_Tipo_Pago'] ?>"><?= htmlspecialchars($mp['Tipo_Pago']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <button type="submit">Registrar Pago</button>
        </form>
    </div>
</div>

<script>
// Lógica para llenar el formulario de pago con los datos de la fila
document.getElementById('tipo_cuota').addEventListener('change', function() {
    const tipoCuota = this.value;
    const montoTotal = this.options[this.selectedIndex].getAttribute('data-precio');
    const detallesContainer = document.getElementById('detalles_container');
    
    // Actualizar monto total
    document.getElementById('monto_total').value = montoTotal;
    
    // Mostrar/ocultar detalles solo para multas
    if (tipoCuota === 'Multa') {
        detallesContainer.style.display = 'block';
        document.querySelector('#detalles_container textarea').required = true;
    } else {
        detallesContainer.style.display = 'none';
        document.querySelector('#detalles_container textarea').required = false;
    }
});

// Lógica para mostrar el formulario de pago al hacer clic en "Pagar"
document.querySelectorAll('.btn-pagar').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('pago_id').value = this.getAttribute('data-pago-id');
        document.getElementById('tipo_cuota_pago').value = this.getAttribute('data-tipo-cuota');
        const maxMonto = parseFloat(this.getAttribute('data-monto-restante'));
        document.getElementById('monto_pago').max = maxMonto;
        document.getElementById('max_monto_display').textContent = maxMonto.toFixed(2);
        document.getElementById('formPago').scrollIntoView({behavior: 'smooth'});
    });
});
</script>

<?php require 'includes/footer.php'; ?>