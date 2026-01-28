<?php
/**
 * Model para gerenciar Itens da Lista
 */

class Item {
    private $conn;
    private $table = 'itens';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar itens de uma lista
    public function listarPorLista($lista_id) {
        $query = "SELECT i.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone 
                  FROM " . $this->table . " i 
                  LEFT JOIN categorias c ON i.categoria_id = c.id 
                  WHERE i.lista_id = :lista_id 
                  ORDER BY i.ordem, i.id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lista_id', $lista_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Buscar item por ID
    public function buscarPorId($id) {
        $query = "SELECT i.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone 
                  FROM " . $this->table . " i 
                  LEFT JOIN categorias c ON i.categoria_id = c.id 
                  WHERE i.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Criar novo item
    public function criar($dados) {
        $query = "INSERT INTO " . $this->table . " 
                  (lista_id, nome, categoria_id, preco, quantidade, ordem) 
                  VALUES (:lista_id, :nome, :categoria_id, :preco, :quantidade, :ordem)";
        
        $stmt = $this->conn->prepare($query);
        
        $categoria_id = !empty($dados['categoria_id']) ? $dados['categoria_id'] : null;
        
        $stmt->bindParam(':lista_id', $dados['lista_id']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':preco', $dados['preco']);
        $stmt->bindParam(':quantidade', $dados['quantidade']);
        $stmt->bindParam(':ordem', $dados['ordem']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Atualizar item
    public function atualizar($id, $dados) {
        $query = "UPDATE " . $this->table . " 
                  SET nome = :nome, categoria_id = :categoria_id, preco = :preco, 
                      quantidade = :quantidade, ordem = :ordem 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $categoria_id = !empty($dados['categoria_id']) ? $dados['categoria_id'] : null;
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':preco', $dados['preco']);
        $stmt->bindParam(':quantidade', $dados['quantidade']);
        $stmt->bindParam(':ordem', $dados['ordem']);
        
        return $stmt->execute();
    }

    // Deletar item
    public function deletar($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
