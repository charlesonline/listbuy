<?php
/**
 * Entry Point da aplicação
 * Redireciona para index.html ou serve como roteador
 */

// Inicializar banco de dados
require_once __DIR__ . '/api/init.php';

// Se estiver acessando a raiz, redirecionar para index.html
if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') {
    header('Location: /index.html');
    exit;
}
