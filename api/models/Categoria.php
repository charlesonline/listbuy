<?php
/**
 * Model para gerenciar Categorias
 */

class Categoria {
    private $conn;
    private $table = 'categorias';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar todas as categorias ativas
    public function listar() {
        $query = "SELECT * FROM " . $this->table . " WHERE ativa = 1 ORDER BY nome";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Buscar categoria por ID
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Criar nova categoria
    public function criar($dados) {
        $query = "INSERT INTO " . $this->table . " (nome, cor, icone) VALUES (:nome, :cor, :icone)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cor', $dados['cor']);
        $stmt->bindParam(':icone', $dados['icone']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Atualizar categoria
    public function atualizar($id, $dados) {
        $query = "UPDATE " . $this->table . " 
                  SET nome = :nome, cor = :cor, icone = :icone 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cor', $dados['cor']);
        $stmt->bindParam(':icone', $dados['icone']);
        
        return $stmt->execute();
    }

    // Deletar categoria (soft delete)
    public function deletar($id) {
        $query = "UPDATE " . $this->table . " SET ativa = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Verificar se nome já existe
    public function nomeExiste($nome, $excluirId = null) {
        $query = "SELECT id FROM " . $this->table . " WHERE nome = :nome AND ativa = 1";
        
        if ($excluirId) {
            $query .= " AND id != :excluir_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nome', $nome);
        
        if ($excluirId) {
            $stmt->bindParam(':excluir_id', $excluirId);
        }
        
        $stmt->execute();
        return $stmt->fetch() !== false;
    }
}
