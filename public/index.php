<?php

require_once __DIR__ . '/../src/bootstrap.php';

// Enrutamiento Básico
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME']; // /ComidaToGo/public/index.php
$base_path = str_replace('/public/index.php', '', $script_name); // /ComidaToGo

// Limpiar la URI
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);

echo "<h1>ComidaToGo</h1>";
echo "<p>Ruta solicitada: " . htmlspecialchars($path) . "</p>";

// Prueba de conexión a BD
try {
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>Conexión a base de datos exitosa.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error de base de datos: " . $e->getMessage() . "</p>";
}

// Lógica básica de enrutamiento
if ($path == '/' || $path == '/public/') {
    echo "<h2>Modo Marketplace</h2>";
    echo "<p>Aquí va el buscador global.</p>";
} else {
    // Verificar si es un slug de negocio
    // TODO: Consultar BD para ver si el slug existe
    echo "<h2>Modo Tienda</h2>";
    echo "<p>Viendo perfil de: " . htmlspecialchars(substr($path, 1)) . "</p>";
}
