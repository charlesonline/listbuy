# Correção: Campos Nulos na Tabela Itens

## Problema Identificado

Os campos da tabela `itens` estavam ficando nulos após algumas operações, especialmente ao atualizar itens. Isso acontecia porque o código não validava os dados recebidos e não preservava valores existentes quando campos não eram enviados na requisição.

## Correções Implementadas

### 1. Validações no Model Item.php

**Método `criar()`:**
- ✅ Validação obrigatória do campo `nome`
- ✅ Valores padrão para campos opcionais:
  - `preco`: 0.00
  - `quantidade`: 1.00
  - `ordem`: 0
  - `categoria_id`: null

**Método `atualizar()`:**
- ✅ Validação obrigatória do campo `nome`
- ✅ Busca o item atual antes de atualizar
- ✅ Preserva valores existentes se não forem enviados na requisição
- ✅ Lança exceção se o item não existir

### 2. Scripts de Diagnóstico e Correção

Foram criados dois scripts SQL para ajudar:

#### `database/verificar_integridade.sql`
Executa verificações para identificar:
- Itens com nome NULL
- Itens com lista_id NULL
- Itens com campos vazios ou nulos
- Referências órfãs em itens_marcados
- Estatísticas por lista

#### `database/corrigir_itens_nulos.sql`
Corrige dados corrompidos:
- Cria backup temporário
- Deleta itens completamente corrompidos
- Define nome padrão para itens sem nome
- Define valores padrão para campos numéricos
- Limpa referências órfãs

## Como Usar

### 1. Diagnosticar o Problema

Execute o script de verificação no banco de dados:

```bash
sqlite3 database/lista_compras.db < database/verificar_integridade.sql
```

Ou no Windows PowerShell:
```powershell
Get-Content database\verificar_integridade.sql | sqlite3 database\lista_compras.db
```

### 2. Corrigir Dados Existentes (se necessário)

Se o diagnóstico mostrar itens com campos nulos:

```bash
sqlite3 database/lista_compras.db < database/corrigir_itens_nulos.sql
```

Ou no Windows PowerShell:
```powershell
Get-Content database\corrigir_itens_nulos.sql | sqlite3 database\lista_compras.db
```

### 3. Atualizar o Código

As correções no código já foram aplicadas em `api/models/Item.php`. 
Certifique-se de que o servidor PHP foi reiniciado para aplicar as mudanças.

## Prevenção

Com as correções implementadas:

1. **Validação no Backend**: Campos obrigatórios são validados antes de salvar
2. **Valores Padrão**: Campos opcionais sempre têm valores padrão
3. **Preservação de Dados**: Ao atualizar, valores não enviados são preservados
4. **Tratamento de Erros**: Exceções são lançadas para erros críticos

## Impacto na Finalização de Compras

A correção também resolve indiretamente o problema relatado na finalização de compras:
- ✅ Os itens não são deletados ao finalizar compra
- ✅ Apenas as marcações (checkboxes) são limpas
- ✅ Os itens permanecem na lista para a próxima compra
- ✅ Dados dos itens são preservados corretamente

## Verificação Pós-Correção

Após aplicar as correções:

1. Teste criar novos itens
2. Teste editar itens existentes
3. Teste finalizar uma compra
4. Verifique se os itens permanecem na lista
5. Execute novamente o script de verificação de integridade

Se tudo estiver correto, você não deve ver mais itens com campos nulos!
