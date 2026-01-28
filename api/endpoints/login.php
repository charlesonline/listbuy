<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Sessao.php';

$database = new Database();
$db = $database->getConnection();

$usuarioModel = new Usuario($db);
$sessaoModel = new Sessao($db);

$metodo = $_SERVER['REQUEST_METHOD'];

// LOGIN
if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['username']) || !isset($dados['senha'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Username e senha são obrigatórios']);
        exit;
    }

    // Verificar captcha
    /* if (!isset($dados['captcha']) || !isset($dados['captcha_resposta'])) {
        http_response_code(400);
        echo json_encode([
            'erro' => 'Captcha é obrigatório',
            'debug' => [
                'captcha_presente' => isset($dados['captcha']),
                'captcha_resposta_presente' => isset($dados['captcha_resposta'])
            ]
        ]);
        exit;
    }

    // Validar captcha
    $partes = explode(' ', $dados['captcha']);
    if (count($partes) !== 3) {
        http_response_code(400);
        echo json_encode(['erro' => 'Captcha inválido']);
        exit;
    }

    $num1 = intval($partes[0]);
    $operador = $partes[1];
    $num2 = intval($partes[2]);
    
    $resultado_esperado = 0;
    switch ($operador) {
        case '+':
            $resultado_esperado = $num1 + $num2;
            break;
        case '-':
            $resultado_esperado = $num1 - $num2;
            break;
        case '*':
            $resultado_esperado = $num1 * $num2;
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Operador inválido']);
            exit;
    }

    if (intval($dados['captcha_resposta']) !== $resultado_esperado) {
        http_response_code(400);
        echo json_encode(['erro' => 'Captcha incorreto']);
        exit;
    } */

    // Verificar credenciais
    $usuario = $usuarioModel->verificarCredenciais($dados['username'], $dados['senha']);
    
    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['erro' => 'Credenciais inválidas ou usuário inativo']);
        exit;
    }

    // Criar sessão
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $token = $sessaoModel->criar($usuario['id'], $ip_address, $user_agent);
    
    // Atualizar última atividade
    $usuarioModel->atualizarUltimaAtividade($usuario['id']);
    
    echo json_encode([
        'mensagem' => 'Login realizado com sucesso',
        'token' => $token,
        'usuario' => $usuario
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
