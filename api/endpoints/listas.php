<?php
/**
 * API REST - Endpoint de Listas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Lista.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    
    $lista = new Lista($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            // GET /api/listas.php - Listar todas
            // GET /api/listas.php?id=1 - Buscar uma específica
            // GET /api/listas.php?id=1&itens=1 - Buscar com itens
            
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                
                if (isset($_GET['itens'])) {
                    $resultado = $lista->buscarComItens($id);
                } else {
                    $resultado = $lista->buscarPorId($id);
                }
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'data' => $resultado]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Lista não encontrada']);
                }
            } else {
                $resultado = $lista->listar($usuario_logado['id']);
                echo json_encode(['success' => true, 'data' => $resultado]);
            }
            break;

        case 'POST':
            // POST /api/listas.php - Criar nova lista
            if (!isset($input['nome']) || empty($input['nome'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                break;
            }

            $dados = [
                'usuario_id' => $usuario_logado['id'],
                'nome' => $input['nome'],
                'descricao' => $input['descricao'] ?? ''
            ];

            $id = $lista->criar($dados);
            
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Lista criada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao criar lista']);
            }
            break;

        case 'PUT':
            // PUT /api/listas.php?id=1 - Atualizar lista
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                break;
            }

            $id = $_GET['id'];
            $dados = [
                'nome' => $input['nome'] ?? '',
                'descricao' => $input['descricao'] ?? ''
            ];

            if ($lista->atualizar($id, $dados)) {
                echo json_encode(['success' => true, 'message' => 'Lista atualizada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar lista']);
            }
            break;

        case 'DELETE':
            // DELETE /api/listas.php?id=1 - Deletar lista
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                break;
            }

            $id = $_GET['id'];
            
            if ($lista->deletar($id)) {
                echo json_encode(['success' => true, 'message' => 'Lista deletada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao deletar lista']);
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
