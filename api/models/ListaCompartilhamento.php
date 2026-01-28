<?php

class ListaCompartilhamento {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Compartilhar lista com usuário
    public function compartilhar($lista_id, $usuario_id, $pode_editar = 1) {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO lista_compartilhamentos (lista_id, usuario_id, pode_editar) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$lista_id, $usuario_id, $pode_editar]);
    }

    // Remover compartilhamento
    public function remover($lista_id, $usuario_id) {
        $stmt = $this->db->prepare("
            DELETE FROM lista_compartilhamentos 
            WHERE lista_id = ? AND usuario_id = ?
        ");
        return $stmt->execute([$lista_id, $usuario_id]);
    }

    // Listar usuários com acesso à lista
    public function listarUsuariosComAcesso($lista_id) {
        $stmt = $this->db->prepare("
            SELECT lc.id, lc.pode_editar, lc.compartilhado_em,
                   u.id as usuario_id, u.username, u.nome, u.email
            FROM lista_compartilhamentos lc
            JOIN usuarios u ON lc.usuario_id = u.id
            WHERE lc.lista_id = ?
            ORDER BY u.nome
        ");
        $stmt->execute([$lista_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verificar se usuário tem acesso à lista
    public function temAcesso($lista_id, $usuario_id) {
        $stmt = $this->db->prepare("
            SELECT id, pode_editar 
            FROM lista_compartilhamentos 
            WHERE lista_id = ? AND usuario_id = ?
        ");
        $stmt->execute([$lista_id, $usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Listar todas as listas compartilhadas com um usuário
    public function listarCompartilhadasComUsuario($usuario_id) {
        $stmt = $this->db->prepare("
            SELECT l.*, lc.pode_editar, lc.compartilhado_em,
                   u.nome as proprietario_nome, u.username as proprietario_username
            FROM lista_compartilhamentos lc
            JOIN listas l ON lc.lista_id = l.id
            JOIN usuarios u ON l.usuario_id = u.id
            WHERE lc.usuario_id = ? AND l.ativa = 1
            ORDER BY l.atualizada_em DESC
        ");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Atualizar permissão de edição
    public function atualizarPermissao($lista_id, $usuario_id, $pode_editar) {
        $stmt = $this->db->prepare("
            UPDATE lista_compartilhamentos 
            SET pode_editar = ? 
            WHERE lista_id = ? AND usuario_id = ?
        ");
        return $stmt->execute([$pode_editar, $lista_id, $usuario_id]);
    }
}
