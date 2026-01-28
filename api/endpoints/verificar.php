<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$database = new Database();
$db = $database->getConnection();

// Verificar autenticação
$usuario = verificarAutenticacao($db);

// Retornar dados do usuário
echo json_encode([
    'autenticado' => true,
    'usuario' => $usuario
]);
