<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ListaCompartilhamento.php';
require_once __DIR__ . '/../models/Lista.php';
require_once __DIR__ . '/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar autenticação
    $usuario_logado = verificarAutenticacao($db);
    
    $compartilhamentoModel = new ListaCompartilhamento($db);
    $listaModel = new Lista($db);
    $metodo = $_SERVER['REQUEST_METHOD'];

    // GET - Listar usuários com acesso à lista
    if ($metodo === 'GET') {
        if (!isset($_GET['lista_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID da lista é obrigatório'
            ]);
            exit;
        }

        $lista_id = intval($_GET['lista_id']);
        
        // Verificar se o usuário é proprietário da lista
        $lista = $listaModel->buscarPorId($lista_id);
        if (!$lista || $lista['usuario_id'] != $usuario_logado['id']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para ver os compartilhamentos desta lista'
            ]);
            exit;
        }

        $usuarios = $compartilhamentoModel->listarUsuariosComAcesso($lista_id);
        
        echo json_encode([
            'success' => true,
            'data' => $usuarios
        ]);
        exit;
    }

    // POST - Compartilhar lista com usuário
    if ($metodo === 'POST') {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($dados['lista_id']) || !isset($dados['usuario_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID da lista e do usuário são obrigatórios'
            ]);
            exit;
        }

        $lista_id = intval($dados['lista_id']);
        $usuario_id_compartilhar = intval($dados['usuario_id']);
        $pode_editar = isset($dados['pode_editar']) ? intval($dados['pode_editar']) : 1;

        // Verificar se o usuário é proprietário da lista
        $lista = $listaModel->buscarPorId($lista_id);
        if (!$lista || $lista['usuario_id'] != $usuario_logado['id']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para compartilhar esta lista'
            ]);
            exit;
        }

        // Não permitir compartilhar consigo mesmo
        if ($usuario_id_compartilhar == $usuario_logado['id']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Você não pode compartilhar a lista consigo mesmo'
            ]);
            exit;
        }

        $compartilhamentoModel->compartilhar($lista_id, $usuario_id_compartilhar, $pode_editar);

        echo json_encode([
            'success' => true,
            'message' => 'Lista compartilhada com sucesso'
        ]);
        exit;
    }

    // DELETE - Remover compartilhamento
    if ($metodo === 'DELETE') {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($dados['lista_id']) || !isset($dados['usuario_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID da lista e do usuário são obrigatórios'
            ]);
            exit;
        }

        $lista_id = intval($dados['lista_id']);
        $usuario_id_remover = intval($dados['usuario_id']);

        // Verificar se o usuário é proprietário da lista
        $lista = $listaModel->buscarPorId($lista_id);
        if (!$lista || $lista['usuario_id'] != $usuario_logado['id']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para remover compartilhamentos desta lista'
            ]);
            exit;
        }

        $compartilhamentoModel->remover($lista_id, $usuario_id_remover);

        echo json_encode([
            'success' => true,
            'message' => 'Compartilhamento removido com sucesso'
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
