# Correção do Erro de Índice Parcial SQLite

## Problema
O erro ocorre quando o SQLite não suporta índices parciais (partial indexes) com cláusula `WHERE`:
```
SQLSTATE[HY000]: General error: 11 malformed database schema (idx_sessao_ativa_unica) - near "WHERE": syntax error
```

## Causa
Versões antigas do SQLite (< 3.8.0, lançado em 2013) não suportam índices parciais. Alguns ambientes de hospedagem compartilhada podem ter versões desatualizadas do SQLite.

## Solução

### Opção 1: Executar Script SQL (Recomendado)
Se você tem acesso ao phpMyAdmin ou à linha de comando:

1. Acesse o banco de dados `lista_compras.db`
2. Execute o arquivo `database/fix_partial_index.sql`

OU execute o seguinte SQL diretamente:
```sql
DROP INDEX IF EXISTS idx_sessao_ativa_unica;
CREATE INDEX IF NOT EXISTS idx_sessoes_compra_lista_ativa 
ON sessoes_compra(lista_id, ativa);
```

### Opção 2: Recriar o Banco de Dados
Se preferir começar do zero (perderá todos os dados):

1. Faça backup do arquivo `database/lista_compras.db` (se necessário)
2. Delete o arquivo `database/lista_compras.db`
3. Acesse qualquer endpoint da API - o banco será recriado automaticamente com o schema corrigido

### Opção 3: Via Script PHP
Crie um arquivo temporário `fix_db.php` na raiz do projeto:

```php
<?php
require_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Remove índice problemático
    $db->exec("DROP INDEX IF EXISTS idx_sessao_ativa_unica");
    
    // Cria novo índice
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sessoes_compra_lista_ativa ON sessoes_compra(lista_id, ativa)");
    
    echo "Banco de dados corrigido com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

Execute acessando: `https://seusite.com/fix_db.php`

**IMPORTANTE:** Delete o arquivo `fix_db.php` após a execução!

## Verificação
Após aplicar a correção, verifique se o erro foi resolvido acessando qualquer endpoint da API, como:
```
https://seusite.com/api.php?action=verificar
```

## Alterações Implementadas
1. **schema.sql**: Removida a cláusula `WHERE` do índice único
2. **SessaoCompra.php**: Adicionada lógica para garantir apenas uma sessão ativa por lista
3. A constraint de unicidade agora é gerenciada pela aplicação, não pelo banco de dados

## Compatibilidade
Esta solução é compatível com todas as versões do SQLite e mantém a funcionalidade original da aplicação.
