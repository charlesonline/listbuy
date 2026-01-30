<?php
/**
 * API REST - Endpoint de Compras
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    $db = $database->getConnection();
    $compra = new Compra($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            // GET /api/compras.php?lista_id=1 - Histórico de compras
            // GET /api/compras.php?id=1 - Buscar compra específica com itens
            
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $resultado = $compra->buscarComItens($id, $usuario_logado['id']);
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'data' => $resultado]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Compra não encontrada ou sem permissão']);
                }
            } else {
                $lista_id = $_GET['lista_id'] ?? null;
                $limit = $_GET['limit'] ?? 10;
                
                $resultado = $compra->listarHistorico($usuario_logado['id'], $lista_id, $limit);
                echo json_encode(['success' => true, 'data' => $resultado]);
            }
            break;

        case 'POST':
            // POST /api/compras.php - Finalizar compra
            if (!isset($input['lista_id']) || !isset($input['itens'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'lista_id e itens são obrigatórios']);
                break;
            }

            if (empty($input['itens'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nenhum item selecionado']);
                break;
            }

            $compra_id = $compra->finalizar($input['lista_id'], $input['itens']);
            
            if ($compra_id) {
                http_response_code(201);
                
                // Retornar compra completa
                $resultado = $compra->buscarComItens($compra_id);
                echo json_encode([
                    'success' => true, 
                    'id' => $compra_id, 
                    'data' => $resultado,
                    'message' => 'Compra finalizada com sucesso!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao finalizar compra']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor', 'error' => $e->getMessage()]);
}
