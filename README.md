# App Lista de Compras 🛒

Aplicação web moderna e responsiva para gerenciar listas de compras, desenvolvida com PHP 8 e arquitetura REST API.

## 🎨 Características

- **Super Responsivo**: Design mobile-first otimizado para smartphones
- **API REST**: Backend PHP 8 com endpoints RESTful
- **Interface Moderna**: Layout bonito com cores pastéis e vibrantes
- **Sistema de Autenticação**: Login seguro com proteção contra ataques
- **Gerenciamento de Usuários**: Controle completo de usuários (apenas admins)
- **Sistema de Listas**: Crie e gerencie múltiplas listas de compras
- **Categorias Customizáveis**: Organize itens por categorias com cores personalizadas
- **Itens Detalhados**: Nome, categoria, preço e quantidade
- **Finalização de Compra**: Marque itens e registre compras realizadas
- **Histórico de Compras**: Acompanhe suas compras e evolução de preços
- **Listas Fixas**: Após finalizar, a lista se renova automaticamente

## 🛠️ Tecnologias

- **Backend**: PHP 8 com PDO
- **Banco de Dados**: SQLite
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Design**: CSS Grid, Flexbox, Animações CSS
- **Arquitetura**: REST API + SPA-like

## 📁 Estrutura do Projeto

```
app_lista_de_compra/
├── public/
│   ├── index.html          # Interface principal
│   ├── index.php           # Entry point
│   ├── .htaccess           # Configuração Apache
│   ├── css/
│   │   └── style.css       # Estilos modernos
│   └── js/
│       └── app.js          # Lógica da aplicação
├── api/
│   ├── config/
│   │   └── database.php    # Configuração do banco
│   ├── models/
│   │   ├── Lista.php       # Model de Listas
│   │   ├── Item.php        # Model de Itens
│   │   ├── Compra.php      # Model de Compras
│   │   ├── Categoria.php   # Model de Categorias
│   │   ├── Usuario.php     # Model de Usuários
│   │   └── Sessao.php      # Model de Sessões
│   └── endpoints/
│       ├── auth.php        # Middleware de autenticação
│       ├── login.php       # Endpoint de login
│       ├── logout.php      # Endpoint de logout
│       ├── verificar.php   # Verificação de sessão
│       ├── usuarios.php    # API de Usuários
│       ├── listas.php      # API de Listas
│       ├── itens.php       # API de Itens
│       ├── compras.php     # API de Compras
│       └── categorias.php  # API de Categorias
├── database/
│   ├── schema.sql          # Schema do banco
│   └── lista_compras.db    # Banco SQLite (gerado automaticamente)
├── Dockerfile              # Configuração Docker
├── docker-compose.yml      # Orquestração Docker
└── .dockerignore           # Arquivos ignorados pelo Docker
```

## 🚀 Como Usar

### Opção 1: Docker (Recomendado) 🐳

#### Requisitos
- Docker
- Docker Compose

#### Instalação

1. Clone ou baixe o projeto
2. Navegue até a pasta do projeto:
   ```bash
   cd app_lista_de_compra
   ```

3. Build e execute com Docker Compose:
   ```bash
   docker-compose up -d --build
   ```

4. Acesse no navegador:
   ```
   http://localhost:8080
   ```

5. **Credenciais padrão de acesso:**
   - Usuário: `admin`
   - Senha: `admin123`

6. Para parar a aplicação:
   ```bash
   docker-compose down
   ```

6. Para ver os logs:
   ```bash
   docker-compose logs -f
   ```

### Opção 2: PHP Nativo

#### Requisitos
- PHP 8.0 ou superior
- Extensão PDO SQLite habilitada

#### Instalação

1. Clone ou baixe o projeto
2. Navegue até a pasta do projeto:
   ```bash
   cd app_lista_de_compra
   ```

3. Inicie o servidor PHP embutido:
   ```bash
   php -S localhost:8000 -t public
   ```

4. Acesse no navegador:
   ```
   http://localhost:8000
   ```

### Configuração do Servidor Web (Produção)

#### Apache

Crie um arquivo `.htaccess` na pasta `public`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/endpoints/$1 [L]
```

#### Nginx

Configure o servidor:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /caminho/para/app_lista_de_compra/public;
    
    index index.html index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 📱 Funcionalidades

### Autenticação e Segurança

- ✅ Login seguro com username e senha
- ✅ Captcha matemático para proteção contra ataques
- ✅ Sistema de sessões com tokens
- ✅ Tokens com expiração de 7 dias
- ✅ Renovação automática de sessão
- ✅ Logout seguro
- ✅ Middleware de autenticação em todas as APIs
- ✅ Controle de permissões (admin/usuário)

### Gerenciamento de Usuários (Admin)

- ✅ Criar novos usuários
- ✅ Editar usuários existentes
- ✅ Ativar/desativar usuários
- ✅ Definir permissões de administrador
- ✅ Excluir usuários
- ✅ Visualizar última atividade
- ✅ Proteção: apenas admins podem gerenciar usuários

### Chat em Tempo Real

- ✅ Chat em grupo para todos os usuários autenticados
- ✅ Interface flutuante que não interfere nas compras
- ✅ Minimizar/maximizar chat
- ✅ Contador de mensagens não lidas
- ✅ Notificações de novas mensagens
- ✅ Atualização automática (polling a cada 2 segundos)
- ✅ Histórico de mensagens
- ✅ Visual moderno com balões de conversa

### Listas de Compras

- ✅ Criar novas listas (vinculadas ao usuário)
- ✅ Editar listas existentes
- ✅ Visualizar apenas suas listas
- ✅ Excluir listas

### Categorias

- ✅ Criar categorias personalizadas
- ✅ Escolher cores para cada categoria
- ✅ Adicionar ícones (emojis) às categorias
- ✅ Editar e excluir categorias
- ✅ Paleta de cores pré-definidas
- ✅ Color picker integrado

### Itens

- ✅ Adicionar itens à lista
- ✅ Editar itens (nome, categoria, preço, quantidade)
- ✅ Excluir itens
- ✅ Marcar/desmarcar itens para compra
- ✅ Categorias coloridas do banco de dados
- ✅ Ícones personalizados por categoria

### Compras

- ✅ Selecionar itens para comprar
- ✅ Visualizar total em tempo real
- ✅ Finalizar compra
- ✅ Histórico de compras
- ✅ Lista se renova após finalização

### Histórico e Análises

- ✅ Visualizar todas as compras realizadas
- ✅ Filtrar por lista e período
- ✅ Estatísticas (total gasto, ticket médio, etc.)
- ✅ Evolução de preços (comparação entre compras)
- ✅ Indicadores visuais de variação de preços

## 🎨 Paleta de Cores

- **Rosa Vibrante**: #FF6B9D (Principal)
- **Roxo**: #8B5CF6 (Secundário)
- **Verde**: #10B981 (Sucesso)
- **Pastéis**: Rosa, Roxo, Azul, Verde, Amarelo, Laranja

## 📊 API Endpoints

> **Importante**: Todos os endpoints (exceto login) requerem autenticação via header `Authorization: Bearer {token}`

### Autenticação

- `POST /api/endpoints/login.php` - Fazer login (retorna token)
  ```json
  {
    "username": "admin",
    "senha": "admin123",
    "captcha": "5 + 3",
    "captcha_resposta": "8"
  }
  ```
- `POST /api/endpoints/logout.php` - Fazer logout (invalida token)
- `GET /api/endpoints/verificar.php` - Verificar se token é válido

### Usuários (Admin apenas)

- `GET /api/endpoints/usuarios.php` - Listar todos os usuários
- `GET /api/endpoints/usuarios.php?id=1` - Buscar usuário específico
- `POST /api/endpoints/usuarios.php` - Criar novo usuário
- `PUT /api/endpoints/usuarios.php` - Atualizar usuário
- `DELETE /api/endpoints/usuarios.php` - Deletar usuário

### Categorias

- `GET /api/endpoints/categorias.php` - Listar todas as categorias
- `GET /api/endpoints/categorias.php?id=1` - Buscar categoria específica
- `POST /api/endpoints/categorias.php` - Criar nova categoria
- `PUT /api/endpoints/categorias.php` - Atualizar categoria
- `DELETE /api/endpoints/categorias.php` - Deletar categoria

### Mensagens (Chat)

- `GET /api/endpoints/mensagens.php` - Listar últimas 50 mensagens
- `GET /api/endpoints/mensagens.php?limite=100` - Listar com limite customizado
- `GET /api/endpoints/mensagens.php?ultimo_id=10` - Buscar mensagens após ID específico (polling)
- `POST /api/endpoints/mensagens.php` - Enviar nova mensagem

### Listas

- `GET /api/endpoints/listas.php` - Listar listas do usuário logado
- `GET /api/endpoints/listas.php?id=1` - Buscar lista específica
- `GET /api/endpoints/listas.php?id=1&itens=1` - Buscar lista com itens
- `POST /api/endpoints/listas.php` - Criar nova lista
- `PUT /api/endpoints/listas.php?id=1` - Atualizar lista
- `DELETE /api/endpoints/listas.php?id=1` - Deletar lista

### Itens

- `GET /api/endpoints/itens.php?lista_id=1` - Listar itens da lista
- `GET /api/endpoints/itens.php?id=1` - Buscar item específico
- `POST /api/endpoints/itens.php` - Criar novo item
- `PUT /api/endpoints/itens.php?id=1` - Atualizar item
- `DELETE /api/endpoints/itens.php?id=1` - Deletar item

### Compras

- `GET /api/endpoints/compras.php?lista_id=1` - Histórico de compras
- `GET /api/endpoints/compras.php?id=1` - Buscar compra específica
- `POST /api/endpoints/compras.php` - Finalizar compra

## 🔒 Segurança

- **Autenticação**: Sistema de login com sessões baseadas em tokens
- **Captcha Matemático**: Proteção contra bots e ataques automatizados
- **Tokens Seguros**: Gerados com 32 bytes aleatórios (64 caracteres hex)
- **Expiração de Sessão**: Tokens expiram em 7 dias
- **Renovação Automática**: Sessões são renovadas a cada requisição
- **Middleware de Autenticação**: Todas as APIs protegidas
- **Controle de Permissões**: Sistema de roles (admin/usuário)
- **Prepared Statements**: PDO para prevenir SQL Injection
- **Password Hashing**: Senhas armazenadas com `password_hash()` (bcrypt)
- **Validação de Dados**: Backend valida todos os inputs
- **CORS Configurado**: Headers de segurança apropriados
- **Proteção de Arquivos**: .htaccess bloqueia acesso a arquivos sensíveis

## 🐳 Docker

### Características do Container

- **Imagem Base**: PHP 8.2 com Apache
- **Extensões**: PDO, PDO_SQLite
- **Porta**: 8080 (host) → 80 (container)
- **Volumes**: Código fonte e banco de dados persistidos
- **Healthcheck**: Verificação automática de saúde do container
- **Network**: Rede isolada para comunicação

### Comandos Úteis

```bash
# Iniciar aplicação
docker-compose up -d

# Parar aplicação
docker-compose down

# Ver logs em tempo real
docker-compose logs -f app

# Reconstruir container
docker-compose up -d --build

# Acessar bash do container
docker-compose exec app bash

# Ver status
docker-compose ps

# Limpar tudo (cuidado: apaga o banco de dados)
docker-compose down -v
```

## 📝 Licença

Este projeto foi criado para fins educacionais e pode ser usado livremente.

## 🤝 Contribuições

Sinta-se à vontade para contribuir com melhorias!

---

Desenvolvido com ❤️ e PHP 8
