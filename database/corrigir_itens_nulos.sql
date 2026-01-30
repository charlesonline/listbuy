-- Script para corrigir itens com campos nulos
-- ATENÇÃO: Execute primeiro o script verificar_integridade.sql para ver o problema

-- Backup: Criar tabela temporária com os dados atuais
CREATE TABLE IF NOT EXISTS itens_backup_temp AS 
SELECT * FROM itens;

-- Deletar itens completamente corrompidos (sem nome e sem lista_id)
DELETE FROM itens 
WHERE (nome IS NULL OR nome = '') AND lista_id IS NULL;

-- Para itens com lista_id válido mas nome nulo, definir um nome padrão
UPDATE itens 
SET nome = 'Item sem nome (ID: ' || id || ')'
WHERE nome IS NULL OR nome = '';

-- Para campos numéricos nulos, definir valores padrão
UPDATE itens 
SET preco = 0.00
WHERE preco IS NULL;

UPDATE itens 
SET quantidade = 1.00
WHERE quantidade IS NULL;

UPDATE itens 
SET ordem = 0
WHERE ordem IS NULL;

-- Limpar referências órfãs em itens_marcados
DELETE FROM itens_marcados
WHERE item_id NOT IN (SELECT id FROM itens);

-- Mostrar resultado
SELECT 
    'Itens após correção' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN nome LIKE 'Item sem nome%' THEN 1 ELSE 0 END) as corrigidos
FROM itens;
