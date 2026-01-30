<?php
/**
 * Model para gerenciar Compras realizadas
 */

class Compra {
    private $conn;
    private $table = 'compras';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Finalizar compra com itens selecionados
    public function finalizar($lista_id, $itens_selecionados) {
        try {
            $this->conn->beginTransaction();

            // Calcular total
            $total = 0;
            $total_itens = 0;
            
            foreach ($itens_selecionados as $item) {
                $subtotal = $item['preco'] * $item['quantidade'];
                $total += $subtotal;
                $total_itens++;
            }

            // Criar registro de compra
            $query = "INSERT INTO " . $this->table . " (lista_id, total, total_itens) 
                      VALUES (:lista_id, :total, :total_itens)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lista_id', $lista_id);
            $stmt->bindParam(':total', $total);
            $stmt->bindParam(':total_itens', $total_itens);
            $stmt->execute();
            
            $compra_id = $this->conn->lastInsertId();

            // Salvar itens da compra
            $query_item = "INSERT INTO compra_itens (compra_id, nome, categoria, preco, quantidade, subtotal) 
                          VALUES (:compra_id, :nome, :categoria, :preco, :quantidade, :subtotal)";
            $stmt_item = $this->conn->prepare($query_item);

            foreach ($itens_selecionados as $item) {
                $subtotal = $item['preco'] * $item['quantidade'];
                
                $stmt_item->bindParam(':compra_id', $compra_id);
                $stmt_item->bindParam(':nome', $item['nome']);
                $stmt_item->bindParam(':categoria', $item['categoria']);
                $stmt_item->bindParam(':preco', $item['preco']);
                $stmt_item->bindParam(':quantidade', $item['quantidade']);
                $stmt_item->bindParam(':subtotal', $subtotal);
                $stmt_item->execute();
            }

            $this->conn->commit();
            return $compra_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erro ao finalizar compra: " . $e->getMessage());
            return false;
        }
    }

    // Listar histórico de compras (somente das listas que o usuário possui ou tem acesso)
    public function listarHistorico($usuario_id, $lista_id = null, $limit = 10) {
        // Query para buscar compras de listas que o usuário é dono ou tem compartilhamento
        $query = "SELECT c.* FROM " . $this->table . " c
                  INNER JOIN listas l ON c.lista_id = l.id
                  WHERE (l.usuario_id = :usuario_id 
                     OR EXISTS (
                         SELECT 1 FROM lista_compartilhamentos lc 
                         WHERE lc.lista_id = l.id AND lc.usuario_id = :usuario_id
                     ))";
        
        if ($lista_id) {
            $query .= " AND c.lista_id = :lista_id";
        }
        
        $query .= " ORDER BY c.realizada_em DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        
        if ($lista_id) {
            $stmt->bindParam(':lista_id', $lista_id, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar compra com itens (valida se usuário tem permissão)
    public function buscarComItens($compra_id, $usuario_id = null) {
        // Se usuario_id foi fornecido, valida permissão
        if ($usuario_id !== null) {
            $query = "SELECT c.* FROM " . $this->table . " c
                      INNER JOIN listas l ON c.lista_id = l.id
                      WHERE c.id = :id 
                        AND (l.usuario_id = :usuario_id 
                         OR EXISTS (
                             SELECT 1 FROM lista_compartilhamentos lc 
                             WHERE lc.lista_id = l.id AND lc.usuario_id = :usuario_id
                         ))";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $compra_id);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        } else {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $compra_id);
        }
        
        $stmt->execute();
        $compra = $stmt->fetch();
        if (!$compra) return null;

        $query_itens = "SELECT * FROM compra_itens WHERE compra_id = :compra_id";
        $stmt_itens = $this->conn->prepare($query_itens);
        $stmt_itens->bindParam(':compra_id', $compra_id);
        $stmt_itens->execute();
        
        $compra['itens'] = $stmt_itens->fetchAll();
        return $compra;
    }
}
