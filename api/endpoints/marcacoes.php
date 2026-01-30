<?php
/**
 * Endpoint para gerenciar marcação de itens durante compra
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SessaoCompra.php';
require_once __DIR__ . '/../models/Lista.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    
    $sessaoCompra = new SessaoCompra($db);
    $lista = new Lista($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Extrair parâmetros da URI
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    
    // Remover o prefixo /api/marcacoes.php/ para pegar os parâmetros
    $path = str_replace('/api/marcacoes.php/', '', $path);
    $path = str_replace('/api/marcacoes.php', '', $path);
    
    $uri_parts = array_filter(explode('/', trim($path, '/')));
    $uri_parts = array_values($uri_parts); // Reindexar
    
    // GET /marcacoes.php/{lista_id} - Obter itens marcados
    if ($method === 'GET' && isset($uri_parts[0]) && is_numeric($uri_parts[0])) {
        $lista_id = intval($uri_parts[0]);
        
        // Verificar permissão
        if (!$lista->usuarioPodeAcessar($lista_id, $usuario_logado['id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit();
        }
        
        $marcacoes = $sessaoCompra->obterItensMarcados($lista_id);
        
        echo json_encode([
            'success' => true,
            'marcacoes' => $marcacoes
        ]);
        exit();
    }
    
    // POST /marcacoes.php/{lista_id}/toggle - Marcar/desmarcar item
    if ($method === 'POST' && isset($uri_parts[0]) && is_numeric($uri_parts[0]) && isset($uri_parts[1]) && $uri_parts[1] === 'toggle') {
        $lista_id = intval($uri_parts[0]);
        
        // Verificar permissão
        if (!$lista->usuarioPodeAcessar($lista_id, $usuario_logado['id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit();
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['item_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'item_id é obrigatório']);
            exit();
        }
        
        $item_id = intval($data['item_id']);
        $marcar = isset($data['marcado']) ? (bool)$data['marcado'] : true;
        
        try {
            $resultado = $sessaoCompra->toggleItem($lista_id, $item_id, $usuario_logado['id'], $marcar);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => $marcar ? 'Item marcado' : 'Item desmarcado'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao marcar item']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
        exit();
    }
    
    // POST /marcacoes.php/{lista_id}/finalizar - Finalizar compra
    if ($method === 'POST' && isset($uri_parts[0]) && is_numeric($uri_parts[0]) && isset($uri_parts[1]) && $uri_parts[1] === 'finalizar') {
        $lista_id = intval($uri_parts[0]);
        
        // Verificar permissão
        if (!$lista->usuarioPodeAcessar($lista_id, $usuario_logado['id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit();
        }
        
        try {
            $resultado = $sessaoCompra->finalizarCompra($lista_id, $usuario_logado['id']);
            
            if ($resultado['success']) {
                http_response_code(200);
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao finalizar compra',
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    // Rota não encontrada
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Rota não encontrada']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}
