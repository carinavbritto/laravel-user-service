<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNotNull($user->id);
    }

    public function test_user_has_uuid(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertNotNull($user->id);
        $this->assertIsString($user->id);
    }

    public function test_user_email_is_unique(): void
    {
        User::create([
            'name' => 'Test User 1',
            'email' => 'test@example.com',
        ]);

        $this->expectException(QueryException::class);

        User::create([
            'name' => 'Test User 2',
            'email' => 'test@example.com',
        ]);
    }
}
