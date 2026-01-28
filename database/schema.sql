-- Schema do banco de dados SQLite para App Lista de Compras
-- Criado em 27/01/2026

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    admin BOOLEAN DEFAULT 0,
    ativo BOOLEAN DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_atividade DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Sessões
CREATE TABLE IF NOT EXISTS sessoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    criada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de Mensagens do Chat
CREATE TABLE IF NOT EXISTS mensagens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    mensagem TEXT NOT NULL,
    criada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de Listas
CREATE TABLE IF NOT EXISTS listas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativa BOOLEAN DEFAULT 1,
    criada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de Compartilhamento de Listas
CREATE TABLE IF NOT EXISTS lista_compartilhamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lista_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    pode_editar BOOLEAN DEFAULT 1,
    compartilhado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE(lista_id, usuario_id)
);

-- Tabela de Categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(50) NOT NULL UNIQUE,
    cor VARCHAR(7) NOT NULL DEFAULT '#8B5CF6',
    icone VARCHAR(10) DEFAULT '📦',
    ativa BOOLEAN DEFAULT 1,
    criada_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Itens (template de itens que compõem a lista)
CREATE TABLE IF NOT EXISTS itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lista_id INTEGER NOT NULL,
    nome VARCHAR(100) NOT NULL,
    categoria_id INTEGER,
    preco DECIMAL(10, 2) DEFAULT 0.00,
    quantidade DECIMAL(10, 2) DEFAULT 1.00,
    ordem INTEGER DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabela de Compras Realizadas
CREATE TABLE IF NOT EXISTS compras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lista_id INTEGER NOT NULL,
    total DECIMAL(10, 2) DEFAULT 0.00,
    total_itens INTEGER DEFAULT 0,
    realizada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE
);

-- Tabela de Itens da Compra (snapshot dos itens marcados)
CREATE TABLE IF NOT EXISTS compra_itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    compra_id INTEGER NOT NULL,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50),
    preco DECIMAL(10, 2) DEFAULT 0.00,
    quantidade DECIMAL(10, 2) DEFAULT 1.00,
    subtotal DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE
);

-- Tabela de Sessões de Compra (controla a sessão de compra ativa de cada lista)
CREATE TABLE IF NOT EXISTS sessoes_compra (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lista_id INTEGER NOT NULL,
    ativa BOOLEAN DEFAULT 1,
    iniciada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE
);

-- Índice único para garantir apenas uma sessão ativa por lista
CREATE UNIQUE INDEX IF NOT EXISTS idx_sessao_ativa_unica 
ON sessoes_compra(lista_id) WHERE ativa = 1;

-- Tabela de Itens Marcados (controla quais itens foram marcados na sessão de compra)
CREATE TABLE IF NOT EXISTS itens_marcados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sessao_compra_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    marcado BOOLEAN DEFAULT 0,
    marcado_por INTEGER,
    marcado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sessao_compra_id) REFERENCES sessoes_compra(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES itens(id) ON DELETE CASCADE,
    FOREIGN KEY (marcado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE(sessao_compra_id, item_id)
);

-- Índices para melhor performance
CREATE INDEX IF NOT EXISTS idx_sessoes_token ON sessoes(token);
CREATE INDEX IF NOT EXISTS idx_sessoes_usuario ON sessoes(usuario_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_username ON usuarios(username);
CREATE INDEX IF NOT EXISTS idx_mensagens_criada_em ON mensagens(criada_em);
CREATE INDEX IF NOT EXISTS idx_listas_usuario ON listas(usuario_id);
CREATE INDEX IF NOT EXISTS idx_compartilhamentos_lista ON lista_compartilhamentos(lista_id);
CREATE INDEX IF NOT EXISTS idx_compartilhamentos_usuario ON lista_compartilhamentos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_itens_lista ON itens(lista_id);
CREATE INDEX IF NOT EXISTS idx_itens_categoria ON itens(categoria_id);
CREATE INDEX IF NOT EXISTS idx_compras_lista ON compras(lista_id);
CREATE INDEX IF NOT EXISTS idx_compra_itens_compra ON compra_itens(compra_id);
CREATE INDEX IF NOT EXISTS idx_sessoes_compra_lista ON sessoes_compra(lista_id);
CREATE INDEX IF NOT EXISTS idx_itens_marcados_sessao ON itens_marcados(sessao_compra_id);
CREATE INDEX IF NOT EXISTS idx_itens_marcados_item ON itens_marcados(item_id);

-- Criar usuário admin padrão (senha: admin123)
-- Senha hash gerado com password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (username, nome, senha, email, admin, ativo) VALUES 
    ('admin', 'Administrador', '$2y$10$5Mqk1Cz108LBSZ5wZnFWb.fpzDlUCj.tQPdStO4U54MsAPQmob3ae', 'admin@listacompras.com', 1, 1);

-- Dados iniciais de categorias
INSERT INTO categorias (nome, cor, icone) VALUES 
    ('Grãos', '#F59E0B', '🌾'),
    ('Massas', '#FB923C', '🍝'),
    ('Laticínios', '#60A5FA', '🥛'),
    ('Padaria', '#F87171', '🍞'),
    ('Bebidas', '#3B82F6', '☕'),
    ('Frutas', '#34D399', '🍌'),
    ('Verduras', '#10B981', '🥬'),
    ('Carnes', '#EF4444', '🥩'),
    ('Higiene', '#A78BFA', '🧼'),
    ('Limpeza', '#818CF8', '🧹'),
    ('Outros', '#9CA3AF', '📦');

-- Dados iniciais de exemplo
INSERT INTO listas (usuario_id, nome, descricao) VALUES 
    (1, 'Supermercado', 'Compras semanais do supermercado');

INSERT INTO itens (lista_id, nome, categoria_id, preco, quantidade, ordem) VALUES 
    (1, 'Arroz', 1, 5.50, 1, 1),
    (1, 'Feijão', 1, 7.00, 1, 2),
    (1, 'Macarrão', 2, 3.50, 2, 3),
    (1, 'Leite', 3, 4.20, 3, 4),
    (1, 'Pão', 4, 8.00, 1, 5),
    (1, 'Café', 5, 12.00, 1, 6),
    (1, 'Banana', 6, 5.00, 1, 7),
    (1, 'Tomate', 7, 6.50, 1, 8);
