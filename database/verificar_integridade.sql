-- Script para verificar integridade dos dados

-- Verificar itens com campos nulos
SELECT 
    'Itens com nome NULL' as tipo,
    COUNT(*) as quantidade
FROM itens 
WHERE nome IS NULL;

SELECT 
    'Itens com lista_id NULL' as tipo,
    COUNT(*) as quantidade
FROM itens 
WHERE lista_id IS NULL;

-- Mostrar todos os itens com campos nulos
SELECT 
    id,
    lista_id,
    nome,
    categoria_id,
    preco,
    quantidade,
    ordem,
    criado_em
FROM itens
WHERE nome IS NULL OR lista_id IS NULL OR nome = '' OR preco IS NULL;

-- Verificar referências órfãs em itens_marcados
SELECT 
    'Marcações com item_id inválido' as tipo,
    COUNT(*) as quantidade
FROM itens_marcados im
LEFT JOIN itens i ON im.item_id = i.id
WHERE i.id IS NULL;

-- Verificar total de itens por lista
SELECT 
    l.id as lista_id,
    l.nome as lista_nome,
    COUNT(i.id) as total_itens,
    SUM(CASE WHEN i.nome IS NULL OR i.nome = '' THEN 1 ELSE 0 END) as itens_nulos
FROM listas l
LEFT JOIN itens i ON l.id = i.lista_id
GROUP BY l.id, l.nome
ORDER BY l.id;
