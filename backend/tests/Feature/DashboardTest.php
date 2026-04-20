<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_dashboard_overview()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/dashboard/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary',
                'inventory',
                'service',
                'sales_breakdowns',
            ]);
    }

    public function test_profit_margin_analytics()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/analytics/profit-margin');

        $response->assertStatus(200)
            ->assertJsonStructure(['summary', 'breakdowns','filters']);
    }
}
