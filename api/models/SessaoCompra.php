<?php
/**
 * Model para gerenciar Sessões de Compra
 * Controla a marcação de itens durante a compra
 */

class SessaoCompra {
    private $conn;
    private $table_sessoes = 'sessoes_compra';
    private $table_marcados = 'itens_marcados';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obter ou criar sessão de compra ativa para uma lista
    public function obterOuCriarSessao($lista_id) {
        try {
            // Verificar se já existe sessão ativa
            $query = "SELECT * FROM " . $this->table_sessoes . " 
                      WHERE lista_id = :lista_id AND ativa = 1
                      ORDER BY id DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lista_id', $lista_id);
            $stmt->execute();
            
            $sessao = $stmt->fetch();
            
            if (!$sessao) {
                // Desativar todas as sessões anteriores (garantia adicional)
                $query = "UPDATE " . $this->table_sessoes . " 
                          SET ativa = 0 
                          WHERE lista_id = :lista_id AND ativa = 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':lista_id', $lista_id);
                $stmt->execute();
                
                // Criar nova sessão
                $query = "INSERT INTO " . $this->table_sessoes . " (lista_id) 
                          VALUES (:lista_id)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':lista_id', $lista_id);
                $stmt->execute();
                
                $sessao_id = $this->conn->lastInsertId();
                
                // Retornar a nova sessão
                $query = "SELECT * FROM " . $this->table_sessoes . " WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $sessao_id);
                $stmt->execute();
                $sessao = $stmt->fetch();
            }
            
            return $sessao;
        } catch (Exception $e) {
            error_log("Erro ao obter/criar sessão de compra: " . $e->getMessage());
            return false;
        }
    }

    // Marcar ou desmarcar um item
    public function toggleItem($lista_id, $item_id, $usuario_id, $marcar = true) {
        try {
            // Obter sessão ativa
            $sessao = $this->obterOuCriarSessao($lista_id);
            if (!$sessao) return false;
            
            $sessao_id = $sessao['id'];
            
            // Verificar se já existe registro deste item
            $query = "SELECT * FROM " . $this->table_marcados . " 
                      WHERE sessao_compra_id = :sessao_id AND item_id = :item_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sessao_id', $sessao_id);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->execute();
            
            $registro = $stmt->fetch();
            
            if ($registro) {
                // Atualizar registro existente
                $query = "UPDATE " . $this->table_marcados . " 
                          SET marcado = :marcado, 
                              marcado_por = :usuario_id,
                              marcado_em = CURRENT_TIMESTAMP
                          WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':marcado', $marcar, PDO::PARAM_BOOL);
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->bindParam(':id', $registro['id']);
                $stmt->execute();
            } else {
                // Criar novo registro
                $query = "INSERT INTO " . $this->table_marcados . " 
                          (sessao_compra_id, item_id, marcado, marcado_por) 
                          VALUES (:sessao_id, :item_id, :marcado, :usuario_id)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':sessao_id', $sessao_id);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->bindParam(':marcado', $marcar, PDO::PARAM_BOOL);
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao marcar/desmarcar item: " . $e->getMessage());
            return false;
        }
    }

    // Obter todos os itens marcados de uma lista
    public function obterItensMarcados($lista_id) {
        try {
            $sessao = $this->obterOuCriarSessao($lista_id);
            if (!$sessao) return [];
            
            $query = "SELECT 
                        im.item_id,
                        im.marcado,
                        im.marcado_em,
                        u.nome as marcado_por_nome,
                        u.username as marcado_por_username
                      FROM " . $this->table_marcados . " im
                      LEFT JOIN usuarios u ON im.marcado_por = u.id
                      WHERE im.sessao_compra_id = :sessao_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sessao_id', $sessao['id']);
            $stmt->execute();
            
            $marcacoes = $stmt->fetchAll();
            
            // Criar array associativo para fácil acesso
            $resultado = [];
            foreach ($marcacoes as $marcacao) {
                $resultado[$marcacao['item_id']] = [
                    'marcado' => (bool)$marcacao['marcado'],
                    'marcado_em' => $marcacao['marcado_em'],
                    'marcado_por_nome' => $marcacao['marcado_por_nome'],
                    'marcado_por_username' => $marcacao['marcado_por_username']
                ];
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Erro ao obter itens marcados: " . $e->getMessage());
            return [];
        }
    }

    // Finalizar sessão de compra e criar registro de compra
    public function finalizarCompra($lista_id, $usuario_id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter sessão ativa
            $sessao = $this->obterOuCriarSessao($lista_id);
            if (!$sessao) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Sessão não encontrada'];
            }
            
            // Obter itens marcados com detalhes
            $query = "SELECT 
                        i.id,
                        i.nome,
                        i.preco,
                        i.quantidade,
                        c.nome as categoria
                      FROM " . $this->table_marcados . " im
                      INNER JOIN itens i ON im.item_id = i.id
                      LEFT JOIN categorias c ON i.categoria_id = c.id
                      WHERE im.sessao_compra_id = :sessao_id AND im.marcado = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sessao_id', $sessao['id']);
            $stmt->execute();
            
            $itens_marcados = $stmt->fetchAll();
            
            if (count($itens_marcados) === 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Nenhum item marcado para finalizar'];
            }
            
            // Calcular totais
            $total = 0;
            $total_itens = 0;
            foreach ($itens_marcados as $item) {
                $subtotal = $item['preco'] * $item['quantidade'];
                $total += $subtotal;
                $total_itens++;
            }
            
            // Criar registro de compra
            $query = "INSERT INTO compras (lista_id, total, total_itens) 
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
            
            foreach ($itens_marcados as $item) {
                $subtotal = $item['preco'] * $item['quantidade'];
                
                $stmt_item->bindParam(':compra_id', $compra_id);
                $stmt_item->bindParam(':nome', $item['nome']);
                $stmt_item->bindParam(':categoria', $item['categoria']);
                $stmt_item->bindParam(':preco', $item['preco']);
                $stmt_item->bindParam(':quantidade', $item['quantidade']);
                $stmt_item->bindParam(':subtotal', $subtotal);
                $stmt_item->execute();
            }
            
            // Limpar marcações (deletar todos os itens marcados)
            $query = "DELETE FROM " . $this->table_marcados . " 
                      WHERE sessao_compra_id = :sessao_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sessao_id', $sessao['id']);
            $stmt->execute();
            
            // Desativar sessão
            $query = "UPDATE " . $this->table_sessoes . " 
                      SET ativa = 0 
                      WHERE id = :sessao_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sessao_id', $sessao['id']);
            $stmt->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true, 
                'message' => 'Compra finalizada com sucesso',
                'compra_id' => $compra_id,
                'total' => $total,
                'total_itens' => $total_itens
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erro ao finalizar compra: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao finalizar compra'];
        }
    }
}
