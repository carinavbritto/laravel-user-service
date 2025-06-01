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
            $connection = new AMQPStreamConnection(
                config('queue.connections.rabbitmq.host'),
                config('queue.connections.rabbitmq.port'),
                config('queue.connections.rabbitmq.user'),
                config('queue.connections.rabbitmq.password'),
                config('queue.connections.rabbitmq.vhost')
            );

            $channel = $connection->channel();
            $channel->queue_declare('user_events', false, true, false, false);

            $message = new AMQPMessage(
                json_encode([
                    'event' => 'user.created',
                    'data' => [
                        'uuid' => $this->uuid,
                        'name' => $this->name
                    ]
                ]),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $channel->basic_publish($message, '', 'user_events');

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Erro ao publicar evento no RabbitMQ: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
        }
    }
}
