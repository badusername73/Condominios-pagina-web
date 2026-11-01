<?php require 'includes/header.php'; ?>

<div class="container">
    <h1>Sistema de Gestión de Condominios</h1>

    <!-- Buscador -->
    <div class="search-box">
        <h2>Buscar Condominio</h2>
        <form id="buscarCondominio" action="condominio.php" method="get">
            <select name="id" id="condominioSelect" required>
                <option value="">Seleccione un condominio</option>
                <?php
                // Se actualizó la consulta para usar Numero_Departamento
                $stmt = $pdo->query("SELECT ID_Condominio, Numero_Departamento FROM Condominios WHERE ID_Estado = 1 ORDER BY Numero_Departamento");
                while ($row = $stmt->fetch()) {
                    // Se agregó "Departamento " antes del número
                    echo "<option value='{$row['ID_Condominio']}'>Departamento {$row['Numero_Departamento']}</option>";
                }
                ?>
            </select>
            <button type="submit">Buscar</button>
        </form>

        <h2>Generar Reporte</h2>
        <select id="reporteSelect" required>
            <option value="">Seleccione un reporte</option>
            <option value="morosos">Reporte de Condóminos Morosos</option>
            <option value="ingresos_por_concepto">Ingresos por Concepto</option>
            <option value="ingresos_por_tiempo">Ingresos por Día/Mes/Año</option>
            <option value="ingresos_por_condominio">Ingresos por Condómino</option>
        </select>
        <button id="btnGenerarReporte" disabled>Generar Reporte PDF</button>
    </div>

    <!-- Acciones adicionales -->
    <div class="actions">
        <a href="agregar_precios.php" class="btn">Gestionar Precios de Cuotas</a>
        <a href="registrar_propietario.php" class="btn">Registrar Nuevo Propietario</a>
    </div>
</div>

<script>
    const selectReporte = document.getElementById('reporteSelect');
    const btnGenerar = document.getElementById('btnGenerarReporte');

    function actualizarBoton() {
        btnGenerar.disabled = !selectReporte.value;
    }

    selectReporte.addEventListener('change', actualizarBoton);

    btnGenerar.addEventListener('click', () => {
        const reporte = selectReporte.value;

        if (!reporte) {
            alert('Seleccione un tipo de reporte.');
            return;
        }

        // Redirige sin necesidad del ID de condominio
        window.location.href = `generar_reportes.php?reporte=${reporte}`;
    });
</script>

<?php require 'includes/footer.php'; ?>