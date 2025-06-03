<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Gera a documentação do Swagger';

    public function handle()
    {
        $this->info('Gerando documentação do Swagger...');

        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'User Service API',
                'version' => '1.0.0',
                'description' => 'API para gerenciamento de usuários',
                'contact' => [
                    'email' => 'carinavbritto@gmail.com'
                ]
            ],
            'servers' => [
                [
                    'url' => url('/'),
                    'description' => 'API Server'
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
            'paths' => []
        ];

        // Scan controllers
        $controllers = glob(app_path('Http/Controllers/*.php'));
        foreach ($controllers as $controller) {
            $className = 'App\\Http\\Controllers\\' . basename($controller, '.php');
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    $docComment = $method->getDocComment();
                    if ($docComment && strpos($docComment, '@OA\\') !== false) {
                        $this->parseMethodDoc($docComment, $openapi);
                    }
                }
            }
        }

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

    protected function parseMethodDoc($docComment, &$openapi)
    {
        // Implementação básica do parser de anotações
        // Em um ambiente de produção, você pode querer usar uma biblioteca mais robusta
        preg_match('/@OA\\\\([A-Za-z]+)\s*\(([^)]*)\)/s', $docComment, $matches);
        if (count($matches) >= 3) {
            $type = strtolower($matches[1]);
            $params = $this->parseParams($matches[2]);

            if (isset($params['path'])) {
                $path = $params['path'];
                unset($params['path']);

                if (!isset($openapi['paths'][$path])) {
                    $openapi['paths'][$path] = [];
                }

                $openapi['paths'][$path][$type] = $params;
            }
        }
    }

    protected function parseParams($paramsStr)
    {
        $params = [];
        preg_match_all('/(\w+)\s*=\s*([^,]+)/', $paramsStr, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = trim($match[1]);
            $value = trim($match[2]);

            // Remove quotes if present
            if (preg_match('/^["\'](.*)["\']$/', $value, $quoteMatch)) {
                $value = $quoteMatch[1];
            }

            $params[$key] = $value;
        }

        return $params;
    }
}
