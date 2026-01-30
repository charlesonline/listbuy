<?php
/**
 * API REST - Endpoint de Itens
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
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    $db = $database->getConnection();
    $item = new Item($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            // GET /api/itens.php?lista_id=1 - Listar itens da lista
            // GET /api/itens.php?id=1 - Buscar item específico
            
            if (isset($_GET['lista_id'])) {
                $lista_id = $_GET['lista_id'];
                $resultado = $item->listarPorLista($lista_id);
                echo json_encode(['success' => true, 'data' => $resultado]);
            } elseif (isset($_GET['id'])) {
                $id = $_GET['id'];
                $resultado = $item->buscarPorId($id);
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'data' => $resultado]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'lista_id ou id é obrigatório']);
            }
            break;

        case 'POST':
            // POST /api/itens.php - Criar novo item
            if (!isset($input['lista_id']) || !isset($input['nome'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'lista_id e nome são obrigatórios']);
                break;
            }

            $dados = [
                'lista_id' => $input['lista_id'],
                'nome' => $input['nome'],
                'categoria_id' => $input['categoria_id'] ?? null,
                'preco' => $input['preco'] ?? 0.00,
                'quantidade' => $input['quantidade'] ?? 1.00,
                'ordem' => $input['ordem'] ?? 0
            ];

            $id = $item->criar($dados);
            
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Item criado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao criar item']);
            }
            break;

        case 'PUT':
            // PUT /api/itens.php?id=1 - Atualizar item
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                break;
            }

            $id = $_GET['id'];
            $dados = [];
            
            // Apenas incluir campos que foram enviados
            if (isset($input['nome'])) $dados['nome'] = $input['nome'];
            if (isset($input['categoria_id'])) $dados['categoria_id'] = $input['categoria_id'];
            if (isset($input['preco'])) $dados['preco'] = $input['preco'];
            if (isset($input['quantidade'])) $dados['quantidade'] = $input['quantidade'];
            if (isset($input['ordem'])) $dados['ordem'] = $input['ordem'];

            if ($item->atualizar($id, $dados)) {
                echo json_encode(['success' => true, 'message' => 'Item atualizado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar item']);
            }
            break;

        case 'DELETE':
            // DELETE /api/itens.php?id=1 - Deletar item
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                break;
            }

            $id = $_GET['id'];
            
            if ($item->deletar($id)) {
                echo json_encode(['success' => true, 'message' => 'Item deletado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao deletar item']);
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
