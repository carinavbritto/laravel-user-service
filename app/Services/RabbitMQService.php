<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    private AMQPStreamConnection $connection;
    private const QUEUE_NAME = 'user_events';

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            'rabbitmq', // host
            5672,      // port
            'guest',   // user
            'guest'    // password
        );
    }

    public function publishUserCreated(string $uuid, string $name): void
    {
        $channel = $this->connection->channel();

        $channel->queue_declare(self::QUEUE_NAME, false, true, false, false);

        $message = [
            'event' => 'user.created',
            'payload' => [
                'uuid' => $uuid,
                'name' => $name
            ]
        ];

        $msg = new AMQPMessage(
            json_encode($message),
            ['content_type' => 'application/json']
        );

        $channel->basic_publish($msg, '', self::QUEUE_NAME);

        $channel->close();
        $this->connection->close();
    }
}
