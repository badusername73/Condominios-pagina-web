<?php
session_start();
require_once 'conexion.php'; 

$pagina_actual = basename($_SERVER['PHP_SELF']);
$titulo_sistema = "Gestión de Condominios";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_sistema ?> | <?= ucfirst(str_replace('.php', '', $pagina_actual)) ?></title>
    
    <!-- Fuente corporativa limpia y moderna -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ===== PALETA DE COLORES TIPO CLIENTE DE ESCRITORIO ===== */
        :root {
            --bg-app: #171a21; /* Fondo oscuro principal */
            --bg-panel: #1b2838; /* Fondo de tarjetas */
            --bg-panel-gradient: linear-gradient(135deg, #1b2838 0%, #2a475e 100%);
            --text-main: #c7d5e0; /* Texto gris azulado */
            --text-light: #ffffff; /* Texto blanco puro */
            --brand-blue: #66c0f4; /* Azul brillante para detalles */
            --border-color: #2a475e; /* Bordes sutiles */
            --input-bg: #10141b; /* Fondo de inputs más oscuro */
        }

        /* ===== ESTILOS BASE ===== */
        body {
            background-color: var(--bg-app);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        h1, h2, h3 {
            color: var(--text-light);
            font-weight: 600;
            margin-top: 0;
            letter-spacing: -0.5px;
        }

        a { color: var(--text-light); text-decoration: none; transition: color 0.2s; }
        a:hover { color: var(--brand-blue); }

        /* ===== HEADER Y NAVEGACIÓN ===== */
        .header {
            background-color: #171a21;
            border-bottom: 1px solid #000000;
            box-shadow: 0 2px 6px rgba(0,0,0,0.5);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            margin: auto;
            padding: 0 20px;
        }

        .nav h1 { 
            font-size: 20px; 
            margin: 0; 
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-links a {
            color: var(--text-main);
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            margin-left: 5px;
            border-radius: 3px;
            text-transform: uppercase;
            transition: all 0.2s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--text-light);
            background-color: rgba(102, 192, 244, 0.15);
        }

        /* ===== CONTENEDORES Y PANELES ===== */
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .search-box, .actions, .seccion-deuda, .seccion-historial, .seccion-pago, .condominio-info, .card {
            background: var(--bg-panel-gradient);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        /* ===== FORMULARIOS ===== */
        .form-group { margin-bottom: 15px; }
        
        label {
            display: block;
            color: var(--brand-blue);
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        select, input[type="text"], input[type="number"], input[type="date"], textarea {
            background-color: var(--input-bg);
            border: 1px solid #000;
            color: var(--text-light);
            font-family: inherit;
            font-size: 14px;
            padding: 10px 12px;
            width: 100%;
            border-radius: 3px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        select:focus, input:focus, textarea:focus {
            border-color: var(--brand-blue);
            background-color: #1a222e;
        }

        /* ===== BOTONES (Estilo dinámico) ===== */
        button, .btn {
            background: linear-gradient(to right, #47bfff 5%, #1a44c2 60%);
            background-position: 25%;
            background-size: 330% 100%;
            border: none;
            border-radius: 3px;
            color: white;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        button:hover, .btn:hover {
            background-position: 100%;
            color: white;
        }

        button:disabled {
            background: #2a475e;
            color: #5c7e99;
            cursor: not-allowed;
            box-shadow: none;
        }

        .back-btn {
            font-size: 13px;
            margin-bottom: 20px;
            display: inline-block;
            color: var(--text-main);
            background: rgba(0,0,0,0.3);
            padding: 8px 15px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .back-btn:hover { 
            background: rgba(255,255,255,0.1); 
            color: var(--text-light); 
        }

        /* ===== TABLAS ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: rgba(0,0,0,0.25);
            border-radius: 4px;
            overflow: hidden;
        }

        th {
            background-color: rgba(0,0,0,0.4);
            color: var(--brand-blue);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px;
            color: var(--text-main);
        }

        tr:hover td { 
            background-color: rgba(255,255,255,0.05); 
            color: var(--text-light); 
        }

        /* ===== ALERTAS ===== */
        .alert, .error, .success {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
            font-size: 14px;
        }
        .error { 
            background-color: rgba(184, 38, 38, 0.2); 
            border-left: 4px solid #b82626; 
            color: #ff6666; 
        }
        .success { 
            background-color: rgba(92, 172, 38, 0.2); 
            border-left: 4px solid #5cac26; 
            color: #88d44c; 
        }
        
        /* Scrollbar oscuro */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-app); }
        ::-webkit-scrollbar-thumb { background: #3c546a; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #5c7e99; }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav">
            <h1>SGC - Condominios</h1>
            <div class="nav-links">
    <a href="index.php" class="<?= $pagina_actual == 'index.php' ? 'active' : '' ?>">Inicio</a>
    <a href="agregar_precios.php" class="<?= $pagina_actual == 'agregar_precios.php' ? 'active' : '' ?>">Precios</a>
    <a href="registrar_propietario.php" class="<?= $pagina_actual == 'registrar_propietario.php' ? 'active' : '' ?>">Propietarios</a>
    
    <!-- Enlace para acceder y probar el Web Service -->
    <a href="cliente_ws.php" class="<?= $pagina_actual == 'cliente_ws.php' ? 'active' : '' ?>">Web Service</a>
    
    <?php if(isset($_SESSION['usuario_id'])): ?>
        <a href="logout.php">Salir</a>
    <?php endif; ?>
</div>
        </div>
    </header>
    <main class="container">