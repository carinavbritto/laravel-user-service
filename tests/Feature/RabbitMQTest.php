<?php

namespace Tests\Feature;

use App\Jobs\PublishUserCreated;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RabbitMQTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_dispatches_rabbitmq_event(): void
    {
        Queue::fake();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);

        Queue::assertPushed(PublishUserCreated::class, function ($job) use ($userData) {
            return $job->getName() === $userData['name'];
        });
    }

    public function test_rabbitmq_event_contains_correct_data(): void
    {
        Queue::fake();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', $userData['email'])->first();

        Queue::assertPushed(PublishUserCreated::class, function ($job) use ($user) {
            return $job->getUuid() === $user->uuid &&
                   $job->getName() === $user->name;
        });
    }
}
