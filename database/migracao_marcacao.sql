-- Migração: Adicionar sistema de marcação de itens com persistência
-- Data: 28/01/2026

-- Remover tabela antiga se existir
DROP TABLE IF EXISTS itens_selecionados;

-- Criar tabela de Sessões de Compra
CREATE TABLE IF NOT EXISTS sessoes_compra (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lista_id INTEGER NOT NULL UNIQUE,
    ativa BOOLEAN DEFAULT 1,
    iniciada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE
);

-- Criar tabela de Itens Marcados
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

-- Criar índices
CREATE INDEX IF NOT EXISTS idx_sessoes_compra_lista ON sessoes_compra(lista_id);
CREATE INDEX IF NOT EXISTS idx_itens_marcados_sessao ON itens_marcados(sessao_compra_id);
CREATE INDEX IF NOT EXISTS idx_itens_marcados_item ON itens_marcados(item_id);

-- Mensagem de conclusão
SELECT 'Migração concluída com sucesso!' as status;
