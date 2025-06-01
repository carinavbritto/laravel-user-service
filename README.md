# User Service

Um microserviço RESTful para gerenciamento de usuários, construído com Laravel 12 e PostgreSQL. Este serviço implementa operações CRUD básicas para usuários e utiliza RabbitMQ para comunicação assíncrona.

## Tecnologias Utilizadas

-   Laravel 12
-   PostgreSQL 15
-   Redis 7
-   RabbitMQ 3
-   Docker & Docker Compose

## Requisitos

-   Docker (versão 20.10.0 ou superior)
-   Docker Compose (versão 2.0.0 ou superior)
-   PHP 8.2 ou superior (apenas para desenvolvimento local)
-   Composer (apenas para desenvolvimento local)

## Portas Utilizadas

Certifique-se que as seguintes portas estejam disponíveis em sua máquina:

-   8000: API Laravel
-   5432: PostgreSQL
-   6379: Redis
-   5672: RabbitMQ
-   15672: RabbitMQ Management Interface

## Configuração do Ambiente

1. Clone o repositório:

```bash
git clone [URL_DO_REPOSITORIO]
cd user-service
```

2. Crie o arquivo `.env` na raiz do projeto:

```bash
# Copie o arquivo de exemplo
cp .env.example .env

# Edite o arquivo .env com suas configurações
nano .env  # ou use seu editor preferido
```

O arquivo `.env` deve conter as seguintes variáveis (os valores abaixo são exemplos seguros para desenvolvimento local):

```bash
APP_NAME="User Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=user_service
DB_USERNAME=postgres
DB_PASSWORD=postgres

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
```

> **Nota de Segurança**:
>
> -   O arquivo `.env.example` contém configurações padrão para desenvolvimento local
> -   Para ambientes de produção, use credenciais mais seguras
> -   Nunca compartilhe seu arquivo `.env` real
> -   Mantenha suas credenciais seguras e nunca as inclua em repositórios públicos

3. Inicie os containers:

```bash
docker-compose up -d --build
```

4. Verifique se todos os containers estão rodando:

```bash
docker-compose ps
```

Você deve ver todos os containers com status "Up".

5. Instale as dependências do Composer:

```bash
docker-compose exec app composer install
```

6. Gere a chave da aplicação:

```bash
docker-compose exec app php artisan key:generate
```

7. Execute as migrações:

```bash
docker-compose exec app php artisan migrate
```

## Verificando o Ambiente

1. Teste a API:

```bash
curl http://localhost:8000/api/users
```

Deve retornar um array vazio `[]` ou uma lista de usuários.

2. Verifique os logs da aplicação:

```bash
docker-compose logs -f app
```

3. Verifique os logs do RabbitMQ:

```bash
docker-compose logs -f rabbitmq
```

## Comandos Úteis

-   Parar todos os containers:

```bash
docker-compose down
```

-   Reconstruir e reiniciar os containers:

```bash
docker-compose down && docker-compose up -d --build
```

-   Limpar o cache do Laravel:

```bash
docker-compose exec app php artisan cache:clear
```

-   Verificar status dos serviços:

```bash
docker-compose ps
```

## Solução de Problemas

1. Se a API não estiver respondendo:

    - Verifique se o container `app` está rodando: `docker-compose ps`
    - Verifique os logs: `docker-compose logs app`
    - Tente reconstruir o container: `docker-compose up -d --build app`

2. Se o banco de dados não estiver acessível:

    - Verifique se o container `db` está rodando: `docker-compose ps`
    - Verifique os logs: `docker-compose logs db`
    - Tente reconstruir o container: `docker-compose up -d --build db`

3. Se o RabbitMQ não estiver funcionando:
    - Verifique se o container `rabbitmq` está rodando: `docker-compose ps`
    - Verifique os logs: `docker-compose logs rabbitmq`
    - Acesse a interface de gerenciamento: http://localhost:15672 (guest/guest)

## Endpoints da API

### Listar Usuários

```bash
GET http://localhost:8000/api/users
```

### Criar Usuário

```bash
POST http://localhost:8000/api/users
Content-Type: application/json

{
    "name": "Nome do Usuário",
    "email": "email@exemplo.com"
}
```

### Buscar Usuário por ID

```bash
GET http://localhost:8000/api/users/{id}
```

### Atualizar Usuário

```bash
PUT http://localhost:8000/api/users/{id}
Content-Type: application/json

{
    "name": "Novo Nome",
    "email": "novo@email.com"
}
```

### Deletar Usuário

```bash
DELETE http://localhost:8000/api/users/{id}
```

## Eventos

Quando um usuário é criado, o serviço publica uma mensagem no RabbitMQ com o seguinte formato:

```json
{
    "event": "user.created",
    "payload": {
        "uuid": "uuid-v4",
        "name": "Nome do Usuário"
    }
}
```

## Acessando os Serviços

-   API: http://localhost:8000
-   RabbitMQ Management: http://localhost:15672 (usuário: guest, senha: guest)
-   PostgreSQL: localhost:5432
-   Redis: localhost:6379

## Justificativa das Escolhas Tecnológicas

1. **Laravel 12**: Framework PHP moderno com excelente suporte a APIs REST, ORM robusto e sistema de filas integrado.

2. **PostgreSQL**: Banco de dados relacional robusto e confiável, com suporte nativo a UUID e excelente performance.

3. **RabbitMQ**: Message broker confiável para comunicação assíncrona entre serviços, permitindo desacoplamento e escalabilidade.

4. **Redis**: Cache em memória para melhorar a performance e gerenciar sessões.

5. **Docker**: Containerização para garantir consistência entre ambientes de desenvolvimento e produção.

## Estrutura do Projeto

```
user-service/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── UserController.php
│   │
│   ├── Models/
│   │   └── User.php
│   │
│   └── Services/
│       └── RabbitMQService.php
├── database/
│   └── migrations/
│       └── create_users_table.php
├── routes/
│   └── api.php
├── docker-compose.yml
└── Dockerfile
```

## Testes

O projeto inclui testes unitários e de integração. Para executar os testes:

```bash
# Executar todos os testes
docker-compose exec app php artisan test

# Executar testes específicos
docker-compose exec app php artisan test --filter=UserTest
```

### Cobertura de Testes

-   Testes unitários para o modelo User
-   Testes de integração para a API
-   Testes do serviço RabbitMQ
-   Testes de validação de dados

## Validação de Dados

O serviço implementa validação robusta para todos os endpoints:

### Criar Usuário

-   Nome: obrigatório, mínimo 3 caracteres
-   Email: obrigatório, formato válido, único no sistema

### Atualizar Usuário

-   Nome: opcional, mínimo 3 caracteres
-   Email: opcional, formato válido, único no sistema (exceto para o próprio usuário)

### Respostas de Erro

```json
{
    "error": "Validation failed",
    "messages": {
        "email": ["O email já está em uso"],
        "name": ["O nome deve ter pelo menos 3 caracteres"]
    }
}
```

## Monitoramento e Logs

### Logs da Aplicação

Os logs são armazenados em `storage/logs/laravel.log` e incluem:

-   Requisições HTTP
-   Erros e exceções
-   Eventos do RabbitMQ
-   Queries do banco de dados

### Monitoramento

-   RabbitMQ Management Interface: http://localhost:15672
    -   Monitoramento de filas
    -   Métricas de performance
    -   Status dos consumidores

### Métricas

-   Taxa de requisições
-   Tempo de resposta
-   Uso de recursos
-   Status dos serviços
