<?php

class Usuario {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Listar todos os usuários
    public function listar() {
        $stmt = $this->db->prepare("
            SELECT id, username, nome, email, admin, ativo, criado_em, ultima_atividade 
            FROM usuarios 
            ORDER BY nome
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar usuário por ID
    public function buscarPorId($id) {
        $stmt = $this->db->prepare("
            SELECT id, username, nome, email, admin, ativo, criado_em, ultima_atividade 
            FROM usuarios 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar usuário por username
    public function buscarPorUsername($username) {
        $stmt = $this->db->prepare("
            SELECT id, username, nome, email, senha, admin, ativo, criado_em, ultima_atividade 
            FROM usuarios 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Criar novo usuário
    public function criar($dados) {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (username, nome, senha, email, admin, ativo) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $dados['username'],
            $dados['nome'],
            $senha_hash,
            $dados['email'] ?? null,
            $dados['admin'] ?? 0,
            $dados['ativo'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }

    // Atualizar usuário
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        if (isset($dados['username'])) {
            $campos[] = "username = ?";
            $valores[] = $dados['username'];
        }
        if (isset($dados['nome'])) {
            $campos[] = "nome = ?";
            $valores[] = $dados['nome'];
        }
        if (isset($dados['email'])) {
            $campos[] = "email = ?";
            $valores[] = $dados['email'];
        }
        if (isset($dados['senha'])) {
            $campos[] = "senha = ?";
            $valores[] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }
        if (isset($dados['admin'])) {
            $campos[] = "admin = ?";
            $valores[] = $dados['admin'];
        }
        if (isset($dados['ativo'])) {
            $campos[] = "ativo = ?";
            $valores[] = $dados['ativo'];
        }

        $valores[] = $id;

        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }

    // Deletar usuário
    public function deletar($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Verificar se username já existe (exceto para um ID específico)
    public function usernameExiste($username, $exceto_id = null) {
        if ($exceto_id) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$username, $exceto_id]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
        }
        return $stmt->fetchColumn() > 0;
    }

    // Atualizar última atividade
    public function atualizarUltimaAtividade($id) {
        $stmt = $this->db->prepare("UPDATE usuarios SET ultima_atividade = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Verificar credenciais
    public function verificarCredenciais($username, $senha) {
        $usuario = $this->buscarPorUsername($username);
        
        if (!$usuario) {
            return false;
        }

        if (!$usuario['ativo']) {
            return false;
        }

        if (password_verify($senha, $usuario['senha'])) {
            unset($usuario['senha']);
            return $usuario;
        }

        return false;
    }
}
