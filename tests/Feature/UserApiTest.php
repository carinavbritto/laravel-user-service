<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_users(): void
    {
        User::create([
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
        ]);

        User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
        ]);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'uuid', 'name', 'email', 'created_at', 'updated_at']
            ]);
    }

    public function test_can_create_user(): void
    {
        Queue::fake();

        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'uuid', 'name', 'email', 'created_at', 'updated_at'
            ])
            ->assertJson([
                'name' => $userData['name'],
                'email' => $userData['email'],
            ]);

        $this->assertDatabaseHas('users', $userData);
    }

    public function test_cannot_create_user_with_invalid_data(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Jo',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'messages' => [
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_can_get_user_by_id(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $response = $this->getJson('/api/users/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function test_can_update_user(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $updateData['name'],
                'email' => $updateData['email'],
            ]);

        $this->assertDatabaseHas('users', $updateData);
    }

    public function test_can_delete_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
