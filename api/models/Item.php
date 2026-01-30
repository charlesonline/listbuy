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
        // Validação e valores padrão
        if (empty($dados['nome'])) {
            throw new Exception('Nome do item é obrigatório');
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  (lista_id, nome, categoria_id, preco, quantidade, ordem) 
                  VALUES (:lista_id, :nome, :categoria_id, :preco, :quantidade, :ordem)";
        
        $stmt = $this->conn->prepare($query);
        
        $categoria_id = !empty($dados['categoria_id']) ? $dados['categoria_id'] : null;
        $preco = isset($dados['preco']) ? $dados['preco'] : 0.00;
        $quantidade = isset($dados['quantidade']) ? $dados['quantidade'] : 1.00;
        $ordem = isset($dados['ordem']) ? $dados['ordem'] : 0;
        
        $stmt->bindParam(':lista_id', $dados['lista_id']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':preco', $preco);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':ordem', $ordem);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Atualizar item
    public function atualizar($id, $dados) {
        // Buscar item atual para preservar valores não enviados
        $itemAtual = $this->buscarPorId($id);
        if (!$itemAtual) {
            throw new Exception('Item não encontrado');
        }
        
        // Usar valores enviados ou manter os atuais
        $nome = isset($dados['nome']) ? $dados['nome'] : $itemAtual['nome'];
        $categoria_id = isset($dados['categoria_id']) ? 
                        (!empty($dados['categoria_id']) ? $dados['categoria_id'] : null) : 
                        $itemAtual['categoria_id'];
        $preco = isset($dados['preco']) ? $dados['preco'] : $itemAtual['preco'];
        $quantidade = isset($dados['quantidade']) ? $dados['quantidade'] : $itemAtual['quantidade'];
        $ordem = isset($dados['ordem']) ? $dados['ordem'] : $itemAtual['ordem'];
        
        // Validação
        if (empty($nome)) {
            throw new Exception('Nome do item é obrigatório');
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET nome = :nome, categoria_id = :categoria_id, preco = :preco, 
                      quantidade = :quantidade, ordem = :ordem 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':preco', $preco);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':ordem', $ordem);
        
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
