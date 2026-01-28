# Guia de Teste - Sistema de Marcação de Itens

## Como Testar a Nova Funcionalidade

### 1. Acesse a aplicação
Abra o navegador em: http://localhost:8080

### 2. Faça login
- Usuário: `admin`
- Senha: `admin123`

### 3. Teste Básico (um usuário)

#### a) Criar ou abrir uma lista
1. Clique em uma lista existente (ex: "Supermercado")
2. Você verá os itens da lista com checkboxes ao lado

#### b) Marcar itens
1. Clique no checkbox de um item (ex: "Arroz")
2. O item deve:
   - Ficar com fundo verde claro
   - Ter o nome riscado
   - Mostrar "✓ Administrador" (seu nome)
3. Marque mais alguns itens

#### c) Sair e voltar
1. Clique em "← Voltar"
2. Abra a mesma lista novamente
3. **Verificar**: Os itens devem continuar marcados!

#### d) Finalizar compra
1. Com itens marcados, role até o fim da página
2. Você verá um card mostrando:
   - Quantidade de itens marcados
   - Total em R$
   - Botão "Finalizar Compra"
3. Clique em "Finalizar Compra"
4. Confirme a ação
5. **Verificar**: 
   - Mensagem de sucesso com o total
   - Todos os itens devem ficar desmarcados
   - Os itens continuam na lista para próxima compra

### 4. Teste Avançado (múltiplos usuários)

#### Preparação
1. Abra dois navegadores diferentes (ex: Chrome e Firefox)
2. OU use modo anônimo em abas diferentes
3. Faça login com o mesmo usuário em ambos
4. OU crie outro usuário e compartilhe a lista

#### Teste de Sincronização
1. **Navegador 1**: Abra uma lista
2. **Navegador 2**: Abra a mesma lista
3. **Navegador 1**: Marque um item
4. **Navegador 2**: Aguarde até 3 segundos
5. **Verificar**: O item deve aparecer marcado no Navegador 2!
6. **Navegador 2**: Marque outro item
7. **Navegador 1**: Aguarde até 3 segundos
8. **Verificar**: O novo item deve aparecer marcado no Navegador 1!

#### Teste de Finalização Compartilhada
1. Com ambos navegadores na mesma lista
2. **Navegador 1**: Marque vários itens
3. **Navegador 2**: Aguarde sincronizar e veja os itens marcados
4. **Navegador 2**: Clique em "Finalizar Compra"
5. **Navegador 1**: Aguarde até 3 segundos
6. **Verificar**: Todos os itens devem ficar desmarcados em ambos!

### 5. Casos de Teste Específicos

#### Teste 1: Persistência após logout
1. Marque alguns itens
2. Faça logout
3. Faça login novamente
4. Abra a mesma lista
5. **Verificar**: Itens continuam marcados

#### Teste 2: Desmarcar itens
1. Marque um item
2. Clique novamente no checkbox
3. **Verificar**: Item deve ficar desmarcado
4. Em outro navegador/aba
5. **Verificar**: Item também fica desmarcado (após 3s)

#### Teste 3: Finalizar sem itens
1. Certifique-se de que nenhum item está marcado
2. **Verificar**: Botão "Finalizar Compra" não aparece

#### Teste 4: Editar item marcado
1. Marque um item
2. Clique no botão de editar (✏️)
3. Altere o nome ou preço
4. Salve
5. **Verificar**: Item continua marcado após edição

#### Teste 5: Deletar item marcado
1. Marque um item
2. Clique no botão de deletar (🗑️)
3. Confirme
4. **Verificar**: Item é removido normalmente

### 6. Comportamentos Esperados

✅ **CORRETO**:
- Itens marcados persistem ao sair e voltar
- Sincronização entre usuários em até 3 segundos
- Nome do usuário que marcou aparece no item
- Ao finalizar, itens são desmarcados
- Após finalizar, lista continua com os mesmos itens

❌ **INCORRETO (reportar se acontecer)**:
- Marcações desaparecem ao sair da lista
- Sincronização não acontece
- Ao finalizar, itens desaparecem da lista
- Não consegue marcar/desmarcar itens
- Erro ao finalizar compra

### 7. Verificar Console do Navegador

Para debug, abra o Console (F12):
- Não deve ter erros em vermelho
- Mensagens de polling devem aparecer a cada 3s
- Ao marcar item, deve mostrar requisição bem-sucedida

### 8. Verificar API Diretamente (Opcional)

#### Obter marcações
```bash
curl -X GET http://localhost:8080/api/endpoints/marcacoes.php/1 \
  -H "Authorization: Bearer SEU_TOKEN"
```

#### Marcar item
```bash
curl -X POST http://localhost:8080/api/endpoints/marcacoes.php/1/toggle \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"item_id": 1, "marcado": true}'
```

#### Finalizar compra
```bash
curl -X POST http://localhost:8080/api/endpoints/marcacoes.php/1/finalizar \
  -H "Authorization: Bearer SEU_TOKEN"
```

### 9. Troubleshooting

#### Marcações não aparecem
- Verifique se está logado
- Verifique se tem permissão na lista
- Verifique console por erros

#### Sincronização não funciona
- Aguarde até 3 segundos
- Verifique conexão com internet
- Veja se polling está ativo (console)

#### Erro ao finalizar
- Verifique se há itens marcados
- Verifique permissões
- Veja logs do servidor

### 10. Logs do Servidor

Para ver logs em tempo real:
```bash
cd /Users/charlesonline/Desen/MVP/app_lista_de_compra
docker-compose logs -f app
```

---

## Resumo do Fluxo

```
┌─────────────────┐
│  Abrir Lista    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Carregar        │
│ Marcações       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Iniciar         │
│ Polling (3s)    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ Marcar/Desmarcar Itens  │
│ (sincroniza automático) │
└────────┬────────────────┘
         │
         ▼
┌─────────────────┐
│ Finalizar       │
│ Compra          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Limpar          │
│ Marcações       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Criar Histórico │
└─────────────────┘
```

Bons testes! 🎉
