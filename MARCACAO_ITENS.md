# Sistema de Marcação de Itens com Persistência

## Funcionalidades Implementadas

### ✅ Marcação de Itens Persistente
- **Marcar itens durante as compras**: Clique nos checkboxes ao lado dos itens para marcá-los
- **Persistência**: As marcações ficam salvas no banco de dados e permanecem mesmo se você sair e voltar
- **Sincronização em tempo real**: Quando uma pessoa marca um item, outras pessoas veem em até 3 segundos
- **Indicador visual**: Mostra quem marcou cada item

### 🔄 Sincronização em Tempo Real
- **Polling automático**: A cada 3 segundos, o sistema verifica se houve mudanças nas marcações
- **Listas compartilhadas**: Todos os usuários com acesso à lista veem as mesmas marcações
- **Feedback visual**: Itens marcados ficam com fundo verde claro e texto riscado

### 🎯 Finalizar Compra
- **Botão "Finalizar Compra"**: Visível quando há itens marcados
- **Cria histórico**: Salva os itens marcados como uma compra realizada
- **Zera marcações**: Ao finalizar, todas as marcações são limpas para a próxima compra
- **Calcula total**: Mostra o valor total dos itens marcados

## Arquivos Modificados

### Backend
1. **database/schema.sql**
   - Removida tabela `itens_selecionados` (antiga)
   - Adicionada tabela `sessoes_compra` (controla sessão ativa por lista)
   - Adicionada tabela `itens_marcados` (armazena marcações com autor e timestamp)

2. **api/models/SessaoCompra.php** (NOVO)
   - `obterOuCriarSessao()`: Gerencia sessão de compra ativa
   - `toggleItem()`: Marca/desmarca um item
   - `obterItensMarcados()`: Retorna todas as marcações de uma lista
   - `finalizarCompra()`: Finaliza compra e limpa marcações

3. **api/endpoints/marcacoes.php** (NOVO)
   - `GET /marcacoes.php/{lista_id}`: Obtém marcações da lista
   - `POST /marcacoes.php/{lista_id}/toggle`: Marca/desmarca item
   - `POST /marcacoes.php/{lista_id}/finalizar`: Finaliza compra

### Frontend
1. **public/js/app.js**
   - Adicionado `State.itensMarcados`: Armazena marcações atuais
   - Adicionado `State.pollingInterval`: Controla polling
   - `toggleItemMarcado()`: Nova função para marcar itens
   - `carregarMarcacoes()`: Carrega marcações do servidor
   - `iniciarPolling()`: Inicia sincronização automática
   - `pararPolling()`: Para sincronização ao sair da lista
   - `finalizarCompra()`: Nova implementação com marcações

2. **public/css/style.css**
   - `.item-card.marcado`: Estilo para itens marcados
   - `.item-nome.riscado`: Texto riscado para itens marcados
   - `.item-marcado-por`: Badge mostrando quem marcou

## Como Usar

### Para Usuários
1. **Abra uma lista de compras**
2. **Clique nos checkboxes** ao lado dos itens que já pegou
3. **Veja quem marcou**: O nome aparece abaixo do item
4. **Saia e volte**: As marcações continuam lá
5. **Finalize a compra**: Clique em "Finalizar Compra" quando terminar
6. **Próxima compra**: As marcações são zeradas, mas os itens da lista permanecem

### Para Listas Compartilhadas
1. **Todos veem as mesmas marcações**
2. **Marcações sincronizam automaticamente** em até 3 segundos
3. **Cada marcação mostra quem fez**
4. **Qualquer pessoa pode finalizar** a compra

## Migração do Banco de Dados

Para aplicar as mudanças em um banco existente:

```bash
cd /Users/charlesonline/Desen/MVP/app_lista_de_compra
sqlite3 database/app.db < database/migracao_marcacao.sql
```

Ou recrie o banco do zero:

```bash
rm database/app.db
sqlite3 database/app.db < database/schema.sql
```

## Estrutura de Dados

### Tabela `sessoes_compra`
```sql
id              INTEGER     PRIMARY KEY
lista_id        INTEGER     UNIQUE (uma sessão por lista)
ativa           BOOLEAN     1 = ativa, 0 = finalizada
iniciada_em     DATETIME    Timestamp de início
```

### Tabela `itens_marcados`
```sql
id                  INTEGER     PRIMARY KEY
sessao_compra_id    INTEGER     Referência à sessão
item_id             INTEGER     Referência ao item
marcado             BOOLEAN     0 = desmarcado, 1 = marcado
marcado_por         INTEGER     ID do usuário que marcou
marcado_em          DATETIME    Timestamp da marcação
```

## Fluxo de Funcionamento

1. **Abrir Lista**: 
   - Cria sessão de compra (se não existir)
   - Carrega marcações existentes
   - Inicia polling (3s)

2. **Marcar Item**:
   - Envia para servidor
   - Atualiza localmente
   - Re-renderiza interface

3. **Polling**:
   - A cada 3s consulta servidor
   - Compara com estado local
   - Atualiza se houver mudanças

4. **Finalizar Compra**:
   - Cria registro em `compras`
   - Cria registros em `compra_itens`
   - Deleta marcações
   - Desativa sessão
   - Limpa estado local

5. **Sair da Lista**:
   - Para polling
   - Limpa estado local
   - Marcações permanecem no servidor

## API Endpoints

### GET /api/endpoints/marcacoes.php/{lista_id}
Retorna marcações da lista

**Response:**
```json
{
  "success": true,
  "marcacoes": {
    "1": {
      "marcado": true,
      "marcado_por_nome": "João Silva",
      "marcado_por_username": "joao",
      "marcado_em": "2026-01-28 10:30:00"
    }
  }
}
```

### POST /api/endpoints/marcacoes.php/{lista_id}/toggle
Marca ou desmarca um item

**Request:**
```json
{
  "item_id": 1,
  "marcado": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item marcado"
}
```

### POST /api/endpoints/marcacoes.php/{lista_id}/finalizar
Finaliza compra e limpa marcações

**Response:**
```json
{
  "success": true,
  "message": "Compra finalizada com sucesso",
  "compra_id": 5,
  "total": 45.50,
  "total_itens": 8
}
```

## Melhorias Futuras Possíveis

- [ ] WebSocket em vez de polling (mais eficiente)
- [ ] Notificações quando alguém marca/desmarca
- [ ] Histórico de quem marcou o quê
- [ ] Desfazer marcação por tempo limitado
- [ ] Marcar múltiplos itens de uma vez
- [ ] Filtrar itens marcados/não marcados
