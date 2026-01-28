<?php
/**
 * API REST - Endpoint de Categorias
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
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    $db = $database->getConnection();
    $categoria = new Categoria($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            // GET /api/categorias.php - Listar todas
            // GET /api/categorias.php?id=1 - Buscar uma específica
            
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $resultado = $categoria->buscarPorId($id);
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'data' => $resultado]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Categoria não encontrada']);
                }
            } else {
                $resultado = $categoria->listar();
                echo json_encode(['success' => true, 'data' => $resultado]);
            }
            break;

        case 'POST':
            // POST /api/categorias.php - Criar nova categoria
            if (!isset($input['nome']) || empty($input['nome'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                break;
            }

            // Verificar se nome já existe
            if ($categoria->nomeExiste($input['nome'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Já existe uma categoria com este nome']);
                break;
            }

            $dados = [
                'nome' => $input['nome'],
                'cor' => $input['cor'] ?? '#8B5CF6',
                'icone' => $input['icone'] ?? '📦'
            ];

            $id = $categoria->criar($dados);
            
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Categoria criada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao criar categoria']);
            }
            break;

        case 'PUT':
            // PUT /api/categorias.php?id=1 - Atualizar categoria
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                break;
            }

            $id = $_GET['id'];

            // Verificar se nome já existe (excluindo a própria categoria)
            if (isset($input['nome']) && $categoria->nomeExiste($input['nome'], $id)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Já existe uma categoria com este nome']);
                break;
            }

            $dados = [
                'nome' => $input['nome'] ?? '',
                'cor' => $input['cor'] ?? '#8B5CF6',
                'icone' => $input['icone'] ?? '📦'
            ];

            if ($categoria->atualizar($id, $dados)) {
                echo json_encode(['success' => true, 'message' => 'Categoria atualizada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar categoria']);
            }
            break;

        case 'DELETE':
            // DELETE /api/categorias.php?id=1 - Deletar categoria
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                break;
            }

            $id = $_GET['id'];
            
            if ($categoria->deletar($id)) {
                echo json_encode(['success' => true, 'message' => 'Categoria deletada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao deletar categoria']);
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
