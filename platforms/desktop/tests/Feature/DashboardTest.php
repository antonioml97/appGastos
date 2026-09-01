<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_the_requested_month(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard?mes=2026-02')
            ->assertOk()
            ->assertJsonPath('home.selectedMonthValue', '2026-02')
            ->assertJsonPath('page', 'home');
    }
}
