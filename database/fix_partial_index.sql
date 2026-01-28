-- Script para corrigir o índice parcial que causa erro em versões antigas do SQLite
-- Executar este script se você receber o erro:
-- "malformed database schema (idx_sessao_ativa_unica) - near WHERE: syntax error"

-- Remove o índice problemático
DROP INDEX IF EXISTS idx_sessao_ativa_unica;

-- Cria um índice simples (sem cláusula WHERE)
CREATE INDEX IF NOT EXISTS idx_sessoes_compra_lista_ativa 
ON sessoes_compra(lista_id, ativa);

-- Nota: A constraint de apenas uma sessão ativa por lista 
-- será gerenciada pela lógica da aplicação
