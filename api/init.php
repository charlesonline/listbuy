<?php
/**
 * Script de inicialização da aplicação
 * Garante que o banco de dados seja criado automaticamente
 */

require_once __DIR__ . '/config/database.php';

try {
    // Instanciar banco de dados e obter conexão
    // Isso dispara a função initializeDatabase()
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Erro ao inicializar banco de dados: " . $e->getMessage());
}
