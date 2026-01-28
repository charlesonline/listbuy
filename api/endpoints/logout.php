<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Sessao.php';

$database = new Database();
$db = $database->getConnection();

$sessaoModel = new Sessao($db);

$metodo = $_SERVER['REQUEST_METHOD'];

// LOGOUT
if ($metodo === 'POST') {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(400);
        echo json_encode(['erro' => 'Token não fornecido']);
        exit;
    }

    $sessaoModel->destruir($token);
    
    echo json_encode(['mensagem' => 'Logout realizado com sucesso']);
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
