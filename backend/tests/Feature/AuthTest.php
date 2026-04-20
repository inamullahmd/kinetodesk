<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials()
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@kinetodesk.local',
            'password' => bcrypt('Admin@12345'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@kinetodesk.local',
            'password' => 'Admin@12345',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user',
            ]);
    }

    public function test_login_with_invalid_credentials()
    {
        User::factory()->admin()->create([
            'email' => 'admin@kinetodesk.local',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@kinetodesk.local',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_get_current_user()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'email' => $user->email,
            ]);
    }

    public function test_logout()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Logged out successfully.']);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_access_admin_routes()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(403);
    }
}
