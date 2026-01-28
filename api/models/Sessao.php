<?php

class Sessao {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Criar nova sessão
    public function criar($usuario_id, $ip_address = null, $user_agent = null) {
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        
        // Sessão expira em 7 dias
        $expira_em = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->db->prepare("
            INSERT INTO sessoes (usuario_id, token, ip_address, user_agent, expira_em) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $usuario_id,
            $token,
            $ip_address,
            $user_agent,
            $expira_em
        ]);
        
        return $token;
    }

    // Validar token
    public function validar($token) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.id as usuario_id, u.username, u.nome, u.email, u.admin, u.ativo
            FROM sessoes s
            JOIN usuarios u ON s.usuario_id = u.id
            WHERE s.token = ? AND s.expira_em > CURRENT_TIMESTAMP AND u.ativo = 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Renovar sessão (extender expiração)
    public function renovar($token) {
        $expira_em = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->db->prepare("
            UPDATE sessoes 
            SET expira_em = ? 
            WHERE token = ?
        ");
        
        return $stmt->execute([$expira_em, $token]);
    }

    // Destruir sessão
    public function destruir($token) {
        $stmt = $this->db->prepare("DELETE FROM sessoes WHERE token = ?");
        return $stmt->execute([$token]);
    }

    // Destruir todas as sessões de um usuário
    public function destruirTodasDoUsuario($usuario_id) {
        $stmt = $this->db->prepare("DELETE FROM sessoes WHERE usuario_id = ?");
        return $stmt->execute([$usuario_id]);
    }

    // Limpar sessões expiradas
    public function limparExpiradas() {
        $stmt = $this->db->prepare("DELETE FROM sessoes WHERE expira_em <= CURRENT_TIMESTAMP");
        return $stmt->execute();
    }

    // Listar sessões ativas de um usuário
    public function listarDoUsuario($usuario_id) {
        $stmt = $this->db->prepare("
            SELECT id, ip_address, user_agent, criada_em, expira_em
            FROM sessoes
            WHERE usuario_id = ? AND expira_em > CURRENT_TIMESTAMP
            ORDER BY criada_em DESC
        ");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
