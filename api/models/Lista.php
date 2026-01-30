<?php
/**
 * Model para gerenciar Listas de Compras
 */

class Lista {
    private $conn;
    private $table = 'listas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar todas as listas ativas do usuário (próprias + compartilhadas)
    public function listar($usuario_id = null) {
        if ($usuario_id) {
            // Buscar listas próprias e compartilhadas
            $query = "
                SELECT l.*, 
                       CASE WHEN l.usuario_id = :usuario_id THEN 1 ELSE 0 END as eh_proprietario,
                       lc.pode_editar as pode_editar_compartilhada,
                       u.nome as proprietario_nome
                FROM " . $this->table . " l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                LEFT JOIN lista_compartilhamentos lc ON l.id = lc.lista_id AND lc.usuario_id = :usuario_id
                WHERE l.ativa = 1 
                  AND (l.usuario_id = :usuario_id OR lc.usuario_id = :usuario_id)
                ORDER BY l.atualizada_em DESC
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id);
        } else {
            $query = "SELECT * FROM " . $this->table . " WHERE ativa = 1 ORDER BY criada_em DESC";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Buscar lista por ID
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Buscar lista por ID com informações de permissão do usuário
    public function buscarPorIdComPermissoes($id, $usuario_id) {
        $query = "
            SELECT l.*, 
                   CASE WHEN l.usuario_id = :usuario_id THEN 1 ELSE 0 END as eh_proprietario,
                   lc.pode_editar as pode_editar_compartilhada,
                   u.nome as proprietario_nome
            FROM " . $this->table . " l
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            LEFT JOIN lista_compartilhamentos lc ON l.id = lc.lista_id AND lc.usuario_id = :usuario_id
            WHERE l.id = :id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Criar nova lista
    public function criar($dados) {
        $query = "INSERT INTO " . $this->table . " (usuario_id, nome, descricao) VALUES (:usuario_id, :nome, :descricao)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':usuario_id', $dados['usuario_id']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Atualizar lista
    public function atualizar($id, $dados) {
        $query = "UPDATE " . $this->table . " 
                  SET nome = :nome, descricao = :descricao, atualizada_em = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        
        return $stmt->execute();
    }

    // Deletar lista (soft delete)
    public function deletar($id) {
        $query = "UPDATE " . $this->table . " SET ativa = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Buscar lista com itens
    public function buscarComItens($id, $usuario_id = null) {
        // Se usuario_id for fornecido, buscar com permissões
        if ($usuario_id) {
            $lista = $this->buscarPorIdComPermissoes($id, $usuario_id);
        } else {
            $lista = $this->buscarPorId($id);
        }
        
        if (!$lista) return null;

        $query = "
            SELECT i.*, 
                   c.nome as categoria_nome, 
                   c.cor as categoria_cor, 
                   c.icone as categoria_icone
            FROM itens i
            LEFT JOIN categorias c ON i.categoria_id = c.id
            WHERE i.lista_id = :lista_id 
            ORDER BY i.ordem, i.id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lista_id', $id);
        $stmt->execute();
        
        $lista['itens'] = $stmt->fetchAll();
        return $lista;
    }
    
    // Verificar se usuário pode acessar uma lista (proprietário ou compartilhado)
    public function usuarioPodeAcessar($lista_id, $usuario_id) {
        $query = "
            SELECT COUNT(*) as pode_acessar
            FROM " . $this->table . " l
            LEFT JOIN lista_compartilhamentos lc ON l.id = lc.lista_id
            WHERE l.id = :lista_id 
              AND l.ativa = 1
              AND (l.usuario_id = :usuario_id OR lc.usuario_id = :usuario_id)
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lista_id', $lista_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['pode_acessar'] > 0;
    }
    
    // Verificar se usuário pode editar uma lista (proprietário ou compartilhado com permissão)
    public function usuarioPodeEditar($lista_id, $usuario_id) {
        $query = "
            SELECT l.usuario_id, lc.pode_editar
            FROM " . $this->table . " l
            LEFT JOIN lista_compartilhamentos lc ON l.id = lc.lista_id AND lc.usuario_id = :usuario_id
            WHERE l.id = :lista_id AND l.ativa = 1
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lista_id', $lista_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // É proprietário ou tem permissão de edição
        return $result['usuario_id'] == $usuario_id || $result['pode_editar'] == 1;
    }
}
