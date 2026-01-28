<?php

class Mensagem {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Listar mensagens recentes (últimas N mensagens)
    public function listarRecentes($limite = 50) {
        $stmt = $this->db->prepare("
            SELECT m.id, m.mensagem, m.criada_em,
                   u.id as usuario_id, u.username, u.nome
            FROM mensagens m
            JOIN usuarios u ON m.usuario_id = u.id
            ORDER BY m.criada_em DESC
            LIMIT ?
        ");
        $stmt->execute([$limite]);
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Inverter para ordem cronológica (mais antiga primeiro)
        return array_reverse($mensagens);
    }

    // Listar mensagens após um determinado ID
    public function listarApos($ultimo_id) {
        $stmt = $this->db->prepare("
            SELECT m.id, m.mensagem, m.criada_em,
                   u.id as usuario_id, u.username, u.nome
            FROM mensagens m
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.id > ?
            ORDER BY m.criada_em ASC
        ");
        $stmt->execute([$ultimo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Criar nova mensagem
    public function criar($usuario_id, $mensagem) {
        $stmt = $this->db->prepare("
            INSERT INTO mensagens (usuario_id, mensagem) 
            VALUES (?, ?)
        ");
        
        $stmt->execute([$usuario_id, $mensagem]);
        $id = $this->db->lastInsertId();
        
        // Retornar a mensagem completa criada
        $stmt = $this->db->prepare("
            SELECT m.id, m.mensagem, m.criada_em,
                   u.id as usuario_id, u.username, u.nome
            FROM mensagens m
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Deletar mensagens antigas (mais de X dias)
    public function limparAntigas($dias = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM mensagens 
            WHERE criada_em < datetime('now', '-' || ? || ' days')
        ");
        return $stmt->execute([$dias]);
    }
}
