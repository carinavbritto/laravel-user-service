<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Gera a documentação do Swagger baseada na collection do Postman';

    public function handle()
    {
        $this->info('Gerando documentação do Swagger conforme a collection do Postman...');

        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'API de Usuários',
                'version' => '1.0.0',
                'description' => 'Documentação fiel à collection do Postman',
            ],
            'servers' => [
                [
                    'url' => 'http://localhost:8000/api',
                    'description' => 'Servidor Local'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ]
            ],
            'security' => [
                ['bearerAuth' => []]
            ],
            'paths' => new \stdClass()
        ];

        $paths = [
            // Users
            '/users' => [
                'get' => $this->makeOp('Users', 'Listar todos os usuários', 'GET /users'),
                'post' => $this->makeOp('Users', 'Criar um novo usuário', 'POST /users'),
            ],
            '/users/{id}' => [
                'get' => $this->makeOp('Users', 'Buscar usuário pelo ID', 'GET /users/{id}', ['id']),
                'put' => $this->makeOp('Users', 'Atualizar usuário pelo ID', 'PUT /users/{id}', ['id']),
                'delete' => $this->makeOp('Users', 'Excluir usuário pelo ID', 'DELETE /users/{id}', ['id']),
            ],
            // Auth (com prefixo /auth)
            '/auth/register' => [
                'post' => $this->makeOp('Auth', 'Registrar novo usuário (autenticação)', 'POST /auth/register'),
            ],
            '/auth/login' => [
                'post' => $this->makeOp('Auth', 'Login do usuário', 'POST /auth/login'),
            ],
            '/auth/user-profile' => [
                'get' => $this->makeOp('Auth', 'Obter perfil do usuário autenticado', 'GET /auth/user-profile'),
            ],
            '/auth/logout' => [
                'post' => $this->makeOp('Auth', 'Logout do usuário', 'POST /auth/logout'),
            ],
            '/auth/refresh' => [
                'post' => $this->makeOp('Auth', 'Renovar token JWT', 'POST /auth/refresh'),
            ],
        ];

        $openapi['paths'] = $paths;

        if (!file_exists(storage_path('api-docs'))) {
            mkdir(storage_path('api-docs'), 0755, true);
        }

        file_put_contents(
            storage_path('api-docs/api-docs.json'),
            json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info('Documentação gerada com sucesso!');
        $this->info('Acesse: ' . url('api/documentation'));
    }

    private function makeOp($tag, $summary, $operationId, $pathParams = [])
    {
        $parameters = [];
        foreach ($pathParams as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => [ 'type' => 'string' ]
            ];
        }
        return [
            'tags' => [$tag],
            'summary' => $summary,
            'operationId' => str_replace(['/', '{', '}', ' '], ['_', '', '', '_'], strtolower($operationId)),
            'parameters' => $parameters,
            'responses' => [
                '200' => [ 'description' => 'Sucesso' ]
            ],
            'security' => [['bearerAuth' => []]]
        ];
    }
}
