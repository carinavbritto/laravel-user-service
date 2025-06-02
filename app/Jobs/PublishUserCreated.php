<?php

namespace App\Jobs;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublishUserCreated
{
    public function __construct(
        private string $uuid,
        private string $name
    ) {}

    public function handle(): void
    {
        try {
            \Log::info('Iniciando publicação do evento user.created', [
                'uuid' => $this->uuid,
                'name' => $this->name
            ]);

            $connection = new AMQPStreamConnection(
                'rabbitmq', // host
                5672,      // port
                'guest',   // user
                'guest',   // password
                '/'        // vhost
            );

            $channel = $connection->channel();

            // Configura prefetch por consumidor (recomendado)
            $channel->basic_qos(10, false); // 10 mensagens não confirmadas por consumidor

            // Declara o exchange
            $channel->exchange_declare('user_events', 'direct', false, true, false);

            // Declara a fila
            $channel->queue_declare('user_events', false, true, false, false);

            // Faz o bind da fila com o exchange
            $channel->queue_bind('user_events', 'user_events', 'user.created');

            $message = new AMQPMessage(
                json_encode([
                    'uuid' => $this->uuid,
                    'name' => $this->name
                ]),
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'content_type' => 'application/json'
                ]
            );

            // Publica usando o default exchange e routing key igual ao nome da fila
            $channel->basic_publish($message, '', 'user_events');

            \Log::info('Evento user.created publicado com sucesso', [
                'uuid' => $this->uuid,
                'name' => $this->name
            ]);

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Erro ao publicar evento no RabbitMQ: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e;
        }
    }
}
