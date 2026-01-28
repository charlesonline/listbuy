<?php
// Middleware de autenticação

function verificarAutenticacao($db) {
    require_once __DIR__ . '/../models/Sessao.php';
    require_once __DIR__ . '/../models/Usuario.php';
    
    $sessaoModel = new Sessao($db);
    $usuarioModel = new Usuario($db);
    
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['erro' => 'Token não fornecido', 'requer_login' => true]);
        exit;
    }

    $sessao = $sessaoModel->validar($token);

    if (!$sessao) {
        http_response_code(401);
        echo json_encode(['erro' => 'Token inválido ou expirado', 'requer_login' => true]);
        exit;
    }

    // Renovar sessão automaticamente
    $sessaoModel->renovar($token);
    
    // Atualizar última atividade
    $usuarioModel->atualizarUltimaAtividade($sessao['usuario_id']);

    return [
        'id' => $sessao['usuario_id'],
        'username' => $sessao['username'],
        'nome' => $sessao['nome'],
        'email' => $sessao['email'],
        'admin' => $sessao['admin']
    ];
}
