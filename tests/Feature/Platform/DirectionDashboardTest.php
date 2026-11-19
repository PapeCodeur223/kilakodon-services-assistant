<?php

use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Platform\Models\AnalyticsEvent;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\DirectionMetrics;
use App\Filament\Pages\DirectionDashboard;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('recalcule l’entonnoir, les sources, le délai médian et le coût IA depuis les tables sources (MES-02)', function () {
    config(['assistant.model' => 'test-model', 'assistant.pricing.test-model' => ['input' => 1, 'output' => 5]]);

    AnalyticsEvent::query()->insert([
        ['name' => 'page_view', 'visitor_key' => null, 'path' => '/', 'occurred_at' => now()->subDay()],
        ['name' => 'page_view', 'visitor_key' => '4f1c2a9e-6b1d-4c8e-9a51-2f3b7d8e9c10', 'path' => '/', 'occurred_at' => now()->subDay()],
        ['name' => 'page_view', 'visitor_key' => '4f1c2a9e-6b1d-4c8e-9a51-2f3b7d8e9c10', 'path' => '/solutions', 'occurred_at' => now()->subDay()],
        ['name' => 'page_view', 'visitor_key' => null, 'path' => '/', 'occurred_at' => now()->subDays(40)], // hors période
    ]);

    $created = now()->subDays(2);
    makeLead(['source' => 'facebook', 'stage' => LeadStage::Qualified, 'created_at' => $created, 'first_response_at' => $created->copy()->addHours(2)]);
    makeLead(['source' => 'facebook', 'stage' => LeadStage::Won, 'created_at' => $created, 'first_response_at' => $created->copy()->addHours(4)]);
    makeLead(['source' => 'organic', 'created_at' => $created, 'first_response_at' => $created->copy()->addHours(9)]);
    makeLead(['source' => 'organic', 'created_at' => $created]); // en attente
    makeLead(['source' => 'ancien', 'created_at' => now()->subDays(60)]); // hors période

    AiUsageDaily::query()->create(['date' => now()->subDay()->toDateString(), 'requests' => 10, 'cost_estimate_usd_micros' => 300_000]);

    $m = app(DirectionMetrics::class)->forLastDays(30);
    $funnel = collect($m['funnel'])->pluck('value', 'key');

    expect($funnel['page_views'])->toBe(3)
        ->and($funnel['visitors'])->toBe(1)
        ->and($funnel['leads'])->toBe(4)
        ->and($funnel['qualified'])->toBe(2)
        ->and($funnel['won'])->toBe(1)
        ->and($m['sources'][0])->toBe(['source' => 'facebook', 'leads' => 2, 'qualified' => 2, 'won' => 1])
        ->and($m['response'])->toBe(['median_hours' => 4.0, 'answered' => 3, 'waiting' => 1])
        ->and($m['ai']['cost_usd'])->toBe(0.3)
        // Aucun lead issu d'un brief : pas de coût par brief qualifié (jamais de division inventée).
        ->and($m['ai']['qualified_briefs'])->toBe(0)
        ->and($m['ai']['cost_per_qualified_brief_usd'])->toBeNull();
});

it('réserve le tableau de bord direction aux administrateurs', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create())->get(DirectionDashboard::getUrl())->assertOk()
        ->assertSee('Entonnoir')->assertSee('Sources des leads')->assertSee('Coût IA');

    foreach (['sales', 'editor'] as $role) {
        $this->actingAs(User::factory()->{$role}()->create())->get(DirectionDashboard::getUrl())->assertForbidden();
    }
});
