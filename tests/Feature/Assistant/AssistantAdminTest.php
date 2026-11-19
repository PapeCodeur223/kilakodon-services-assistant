<?php

use App\Domain\Assistant\Actions\CheckAssistantBudget;
use App\Domain\Assistant\Actions\ReviewAssistantFeedback;
use App\Domain\Assistant\Actions\ReviewKnowledgeDocument;
use App\Domain\Assistant\Actions\SaveManualKnowledgeDocument;
use App\Domain\Assistant\Enums\FeedbackRating;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\ReviewStatus;
use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use Database\Seeders\SettingsSeeder;

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function conversationForLead(?User $owner): Conversation
{
    $contact = Contact::query()->create(['full_name' => 'Awa Traoré', 'phone_e164' => '+22374666489']);
    $lead = Lead::query()->create(['contact_id' => $contact->id, 'type' => 'assistant', 'source' => 'assistant', 'owner_id' => $owner?->id, 'idempotency_key' => bin2hex(random_bytes(32))]);

    return Conversation::query()->create(['ip_hash' => str_repeat('b', 64), 'lead_id' => $lead->id, 'message_count' => 1]);
}

it('ouvre les écrans de l\'assistant selon le rôle', function () {
    $admin = User::factory()->admin()->withMfa()->create();
    $editor = User::factory()->editor()->create();
    $sales = User::factory()->sales()->create();

    foreach (['/admin/knowledge-documents', '/admin/conversations', '/admin/retours-ia', '/admin/usage-ia'] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }

    $this->actingAs($editor)->get('/admin/knowledge-documents')->assertOk();
    $this->actingAs($editor)->get('/admin/retours-ia')->assertOk();
    $this->actingAs($editor)->get('/admin/conversations')->assertForbidden();
    $this->actingAs($editor)->get('/admin/usage-ia')->assertForbidden();

    $this->actingAs($sales)->get('/admin/conversations')->assertOk();
    $this->actingAs($sales)->get('/admin/knowledge-documents')->assertForbidden();
    $this->actingAs($sales)->get('/admin/retours-ia')->assertForbidden();
});

it('limite le commercial aux conversations de ses leads', function () {
    $sales = User::factory()->sales()->create();
    $mine = conversationForLead($sales);
    $other = conversationForLead(User::factory()->sales()->create());
    $orphan = Conversation::query()->create(['ip_hash' => str_repeat('c', 64)]);

    $this->actingAs($sales)->get('/admin/conversations/'.$mine->id)->assertOk();
    $this->actingAs($sales)->get('/admin/conversations/'.$other->id)->assertNotFound();
    $this->actingAs($sales)->get('/admin/conversations/'.$orphan->id)->assertNotFound();

    expect($sales->can('view', $mine))->toBeTrue()
        ->and($sales->can('view', $other))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('view', $orphan))->toBeTrue();
});

it('affiche le fil d\'une conversation en échappant les messages du visiteur', function () {
    $admin = User::factory()->admin()->withMfa()->create();
    $conversation = Conversation::query()->create(['ip_hash' => str_repeat('d', 64)]);
    Message::query()->create(['conversation_id' => $conversation->id, 'role' => MessageRole::User, 'content' => '<script>alert(1)</script> Bonjour']);

    $this->actingAs($admin)->get('/admin/conversations/'.$conversation->id)
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Bonjour', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('remet un document manuel en brouillon à chaque modification', function () {
    $admin = User::factory()->admin()->create();
    $document = app(SaveManualKnowledgeDocument::class)->handle(['title' => 'Zones couvertes', 'body' => "## Où intervenez-vous ?\n\nNous intervenons à Bamako et à distance."]);

    expect($document->status)->toBe(KnowledgeStatus::Draft)
        ->and($document->chunks()->count())->toBe(1)
        ->and($admin->can('approve', $document))->toBeTrue()
        ->and(User::factory()->editor()->create()->can('approve', $document))->toBeFalse();

    app(ReviewKnowledgeDocument::class)->approve($document, $admin);
    $document = app(SaveManualKnowledgeDocument::class)->handle(['title' => 'Zones couvertes', 'body' => "## Où intervenez-vous ?\n\nBamako, Kati et à distance."], $document->refresh());

    expect($document->status)->toBe(KnowledgeStatus::Draft)
        ->and($document->version)->toBe(2)
        ->and($document->approved_by)->toBeNull()
        ->and($document->chunks()->first()->content)->toContain('Kati');
});

it('marque un retour négatif comme traité', function () {
    $editor = User::factory()->editor()->create();
    $conversation = Conversation::query()->create(['ip_hash' => str_repeat('e', 64)]);
    $message = Message::query()->create(['conversation_id' => $conversation->id, 'role' => MessageRole::Assistant, 'content' => 'Réponse']);
    $feedback = AiFeedback::query()->create(['message_id' => $message->id, 'rating' => FeedbackRating::Down, 'review_status' => ReviewStatus::ToReview]);

    app(ReviewAssistantFeedback::class)->handle($feedback, ReviewStatus::Actioned, $editor, 'FAQ complétée');

    expect($feedback->refresh()->review_status)->toBe(ReviewStatus::Actioned)
        ->and($feedback->reviewed_by)->toBe($editor->id);
});

it('alerte les administrateurs une seule fois par jour à 80 % du budget', function () {
    User::factory()->admin()->create(['email' => 'direction@example.com']);
    app(Settings::class)->set('ai.daily_budget_usd', 2);
    app(Settings::class)->set('ai.monthly_budget_usd', 0); // plafond du mois testé à part (AssistantApiTest)

    AiUsageDaily::today()->update(['cost_estimate_usd_micros' => 1_000_000]);
    expect(app(CheckAssistantBudget::class)->handle()['alerted'])->toBeFalse();

    AiUsageDaily::today()->update(['cost_estimate_usd_micros' => 1_700_000]);
    expect(app(CheckAssistantBudget::class)->handle()['alerted'])->toBeTrue()
        ->and(app(CheckAssistantBudget::class)->handle()['alerted'])->toBeFalse()
        ->and(OutboundMessage::query()->where('template', 'ai_budget_alert')->count())->toBe(1);

    $this->artisan('kh:ai-budget-check')->expectsOutputToContain('85 %')->assertSuccessful();
});

it('affiche le bouton de l\'assistant seulement quand il est activé', function () {
    config(['assistant.enabled' => true, 'assistant.driver' => 'fake']);

    app(Settings::class)->set('ai.enabled', false);
    $this->get('/')->assertOk()->assertDontSee('data-assistant-launcher', false);

    app(Settings::class)->set('ai.enabled', true);
    $this->get('/')->assertOk()->assertSee('data-assistant-launcher', false)->assertSee('Une question ?');
});
