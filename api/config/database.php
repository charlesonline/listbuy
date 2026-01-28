<?php
/**
 * Configuração do banco de dados SQLite
 */

class Database {
    private $db_file = __DIR__ . '/../../database/lista_compras.db';
    private $conn = null;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                // Criar diretório se não existir
                $dir = dirname($this->db_file);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                // Conectar ao SQLite
                $this->conn = new PDO("sqlite:" . $this->db_file);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Habilitar foreign keys no SQLite
                $this->conn->exec('PRAGMA foreign_keys = ON;');
                
                // Inicializar banco se necessário
                $this->initializeDatabase();
                
            } catch(PDOException $e) {
                error_log("Erro de conexão: " . $e->getMessage());
                throw $e;
            }
        }
        
        return $this->conn;
    }

    private function initializeDatabase() {
        // Verificar se já está inicializado
        $result = $this->conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='listas'");
        
        if ($result->fetch() === false) {
            // Executar schema
            $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
            $this->conn->exec($schema);
        }
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
