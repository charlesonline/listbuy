<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Sessao.php';
require_once __DIR__ . '/auth.php';

$database = new Database();
$db = $database->getConnection();

$usuarioModel = new Usuario($db);

// Verificar autenticação
$usuario_logado = verificarAutenticacao($db);

// Apenas admins podem gerenciar usuários (exceto visualizar próprio perfil)
$metodo = $_SERVER['REQUEST_METHOD'];

// GET - Listar usuários ou buscar por ID
if ($metodo === 'GET') {
    // Qualquer usuário autenticado pode listar
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $usuario = $usuarioModel->buscarPorId($id);
        
        if (!$usuario) {
            http_response_code(404);
            echo json_encode(['erro' => 'Usuário não encontrado']);
            exit;
        }
        
        echo json_encode($usuario);
    } else {
        $usuarios = $usuarioModel->listar();
        echo json_encode($usuarios);
    }
    exit;
}

// Apenas admins para operações de escrita
if (!$usuario_logado['admin']) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado. Apenas administradores podem gerenciar usuários']);
    exit;
}

// POST - Criar novo usuário
if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['username']) || !isset($dados['nome']) || !isset($dados['senha'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Username, nome e senha são obrigatórios']);
        exit;
    }

    // Verificar se username já existe
    if ($usuarioModel->usernameExiste($dados['username'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Username já está em uso']);
        exit;
    }

    try {
        $id = $usuarioModel->criar($dados);
        $usuario = $usuarioModel->buscarPorId($id);
        
        http_response_code(201);
        echo json_encode([
            'mensagem' => 'Usuário criado com sucesso',
            'usuario' => $usuario
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao criar usuário: ' . $e->getMessage()]);
    }
    exit;
}

// PUT - Atualizar usuário
if ($metodo === 'PUT') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do usuário é obrigatório']);
        exit;
    }

    $id = intval($dados['id']);
    
    // Verificar se usuário existe
    if (!$usuarioModel->buscarPorId($id)) {
        http_response_code(404);
        echo json_encode(['erro' => 'Usuário não encontrado']);
        exit;
    }

    // Verificar se username já existe para outro usuário
    if (isset($dados['username']) && $usuarioModel->usernameExiste($dados['username'], $id)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Username já está em uso por outro usuário']);
        exit;
    }

    try {
        $usuarioModel->atualizar($id, $dados);
        $usuario = $usuarioModel->buscarPorId($id);
        
        echo json_encode([
            'mensagem' => 'Usuário atualizado com sucesso',
            'usuario' => $usuario
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao atualizar usuário: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE - Deletar usuário
if ($metodo === 'DELETE') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do usuário é obrigatório']);
        exit;
    }

    $id = intval($dados['id']);
    
    // Não permitir deletar próprio usuário
    if ($id === $usuario_logado['id']) {
        http_response_code(400);
        echo json_encode(['erro' => 'Você não pode deletar seu próprio usuário']);
        exit;
    }

    // Verificar se usuário existe
    if (!$usuarioModel->buscarPorId($id)) {
        http_response_code(404);
        echo json_encode(['erro' => 'Usuário não encontrado']);
        exit;
    }

    try {
        $usuarioModel->deletar($id);
        echo json_encode(['mensagem' => 'Usuário deletado com sucesso']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao deletar usuário: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
