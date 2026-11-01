<?php
// Configuración de la aplicación
define('DIAS_GRACIA_MANTENIMIENTO', 7); // Hasta el día 7 sin recargo
define('PORCENTAJE_EXTEMPORANEO', 0.05); // 5%
define('PORCENTAJE_REINCIDENCIA', 0.50); // 50%
define('RUTA_DOCUMENTOS', __DIR__ . '/../documentos/');

// Configuración de fechas para cuotas extraordinarias
define('FECHA_LIMITE_EXTRAORDINARIA_JUNIO', '2023-06-15');
define('FECHA_LIMITE_EXTRAORDINARIA_DICIEMBRE', '2023-12-15');

// Crear directorio para documentos si no existe
if (!file_exists(RUTA_DOCUMENTOS)) {
    mkdir(RUTA_DOCUMENTOS, 0777, true);
}
?>