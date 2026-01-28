-- Migração para corrigir constraint UNIQUE em sessoes_compra
-- Permite múltiplas sessões por lista, mas apenas uma ativa

-- 1. Criar nova tabela com a estrutura correta
CREATE TABLE IF NOT EXISTS sessoes_compra_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lista_id INTEGER NOT NULL,
    ativa BOOLEAN DEFAULT 1,
    iniciada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE
);

-- 2. Copiar dados da tabela antiga (se existir)
INSERT INTO sessoes_compra_new (id, lista_id, ativa, iniciada_em)
SELECT id, lista_id, ativa, iniciada_em
FROM sessoes_compra;

-- 3. Remover tabela antiga
DROP TABLE sessoes_compra;

-- 4. Renomear nova tabela
ALTER TABLE sessoes_compra_new RENAME TO sessoes_compra;

-- 5. Criar índice único para garantir apenas uma sessão ativa por lista
CREATE UNIQUE INDEX IF NOT EXISTS idx_sessao_ativa_unica 
ON sessoes_compra(lista_id) WHERE ativa = 1;

-- 6. Recriar itens_marcados com FK correta
CREATE TABLE IF NOT EXISTS itens_marcados_new (
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

-- 7. Copiar dados de itens_marcados
INSERT INTO itens_marcados_new (id, sessao_compra_id, item_id, marcado, marcado_por, marcado_em)
SELECT id, sessao_compra_id, item_id, marcado, marcado_por, marcado_em
FROM itens_marcados;

-- 8. Remover tabela antiga
DROP TABLE itens_marcados;

-- 9. Renomear nova tabela
ALTER TABLE itens_marcados_new RENAME TO itens_marcados;
