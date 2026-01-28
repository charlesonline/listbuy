<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Mensagem.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    
    $mensagemModel = new Mensagem($db);
    $metodo = $_SERVER['REQUEST_METHOD'];

    // GET - Listar mensagens
    if ($metodo === 'GET') {
        if (isset($_GET['ultimo_id'])) {
            // Buscar apenas mensagens novas após o último ID
            $mensagens = $mensagemModel->listarApos($_GET['ultimo_id']);
        } else {
            // Buscar últimas 50 mensagens
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
            $mensagens = $mensagemModel->listarRecentes($limite);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $mensagens
        ]);
        exit;
    }

    // POST - Criar nova mensagem
    if ($metodo === 'POST') {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($dados['mensagem']) || empty(trim($dados['mensagem']))) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Mensagem não pode estar vazia'
            ]);
            exit;
        }

        $mensagem = $mensagemModel->criar(
            $usuario_logado['id'],
            trim($dados['mensagem'])
        );

        echo json_encode([
            'success' => true,
            'data' => $mensagem
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
