<?php
/**
 * Roteador de requisições de API
 * Redireciona requisições para /api/* para /api/endpoints/*
 */

// Obter o caminho solicitado
$request_uri = $_SERVER['REQUEST_URI'];

// Extrair o endpoint da query string se existir
if (isset($_GET['endpoint'])) {
    $endpoint = $_GET['endpoint'];
} else {
    // Fallback: extrair do REQUEST_URI
    $base_path = '/api/';
    if (strpos($request_uri, $base_path) === 0) {
        $endpoint = substr($request_uri, strlen($base_path));
        
        // Remover query string se existir
        if (strpos($endpoint, '?') !== false) {
            $endpoint = substr($endpoint, 0, strpos($endpoint, '?'));
        }
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Endpoint não encontrado']);
        exit;
    }
}

// Remover .php do endpoint se existir (evitar .php.php)
if (substr($endpoint, -4) === '.php') {
    $endpoint = substr($endpoint, 0, -4);
}

// Construir o caminho do arquivo
$file_path = __DIR__ . '/../api/endpoints/' . $endpoint . '.php';

// Verificar se o arquivo existe
if (file_exists($file_path)) {
    // Incluir o arquivo
    include $file_path;
    exit;
}

// Se não encontrar, retornar 404
http_response_code(404);
echo json_encode(['erro' => 'Endpoint não encontrado']);
