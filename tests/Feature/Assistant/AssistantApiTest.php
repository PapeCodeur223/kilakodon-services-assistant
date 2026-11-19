<?php

use App\Domain\Assistant\Actions\CheckAssistantBudget;
use App\Domain\Assistant\Actions\StartConversation;
use App\Domain\Assistant\Conversation\FallbackTexts;
use App\Domain\Assistant\Conversation\GuardFailure;
use App\Domain\Assistant\Enums\FeedbackRating;
use App\Domain\Assistant\Enums\ProposalStatus;
use App\Domain\Assistant\Enums\ReviewStatus;
use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config(['assistant.enabled' => true, 'assistant.driver' => 'fake']);
    app(Settings::class)->set('ai.enabled', true);
});

function startConversation(): string
{
    return test()->postJson(route('assistant.start'), ['entry_path' => '/solutions'])->assertCreated()->json('conversation');
}

function ask(string $conversation, string $message): TestResponse
{
    return test()->postJson(route('assistant.message', $conversation), ['message' => $message]);
}

function completeBriefPayload(array $proposal): array
{
    $payload = $proposal['payload'];
    $payload['brief']['summary'] = 'Boutique de 3 points de vente à Bamako avec des écarts de stock.';
    $payload['contact'] = ['full_name' => 'Awa Traoré', 'phone' => '74 66 64 89', 'email' => null, 'company' => null];
    $payload['consent_processing'] = true;

    return $payload;
}

it('répond, enregistre les deux messages et la version du prompt', function () {
    $id = startConversation();

    ask($id, 'Ça marche sans Internet ?')->assertOk()->assertJsonPath('status', 'ok')->assertJsonStructure(['message' => ['id', 'content', 'grounding', 'citations', 'suggested_action'], 'links']);

    $messages = Message::query()->where('conversation_id', $id)->orderBy('id')->get();
    expect($messages)->toHaveCount(2)
        ->and($messages[1]->prompt_version)->toBe('sys-v3')
        ->and(Conversation::query()->findOrFail($id)->message_count)->toBe(2)
        ->and(AiUsageDaily::today()->requests)->toBe(1);
});

it('n\'appelle jamais le modèle quand l\'interrupteur est coupé', function () {
    config(['assistant.driver' => 'anthropic', 'assistant.api_key' => 'sk-test-factice']);
    Http::fake();
    app(Settings::class)->set('ai.enabled', false);

    $this->postJson(route('assistant.start'))->assertOk()
        ->assertJsonPath('status', 'unavailable')
        ->assertJsonPath('reason', 'disabled')
        ->assertJsonPath('message', FallbackTexts::UNAVAILABLE)
        ->assertJsonStructure(['fallback' => ['diagnostic', 'whatsapp', 'contact']]);

    Http::assertNothingSent();
});

it('reste coupé avec la vraie API tant que budget et tarifs ne sont pas renseignés', function () {
    config(['assistant.driver' => 'anthropic', 'assistant.api_key' => 'sk-test-factice', 'assistant.pricing' => []]);
    Http::fake();
    app(Settings::class)->set('ai.daily_budget_usd', 5);

    $this->postJson(route('assistant.start'))->assertJsonPath('reason', 'disabled');
    Http::assertNothingSent();
});

it('coupe l\'assistant une fois le budget du jour atteint', function () {
    $id = startConversation();
    app(Settings::class)->set('ai.daily_budget_usd', 1);
    AiUsageDaily::today()->update(['cost_estimate_usd_micros' => 1_000_000]);

    ask($id, 'Bonjour')->assertJsonPath('status', 'unavailable')->assertJsonPath('reason', 'budget');

    expect(Message::query()->where('conversation_id', $id)->count())->toBe(0)
        ->and(AiUsageDaily::today()->blocked_requests)->toBe(1);
});

it('coupe l\'assistant une fois le budget du mois atteint, même sous le plafond du jour (D-026)', function () {
    $this->travelTo(now(config('kh.display_timezone'))->startOfMonth()->addDays(10)->setTime(12, 0));
    app(Settings::class)->set('ai.daily_budget_usd', 0.5);
    app(Settings::class)->set('ai.monthly_budget_usd', 1);
    $month = now(config('kh.display_timezone'))->startOfMonth();
    AiUsageDaily::query()->create(['date' => $month->copy()->subDays(3)->toDateString(), 'cost_estimate_usd_micros' => 5_000_000]); // mois précédent : ignoré
    AiUsageDaily::query()->create(['date' => $month->copy()->addDays(2)->toDateString(), 'cost_estimate_usd_micros' => 400_000]);

    $id = startConversation();
    ask($id, 'Bonjour')->assertJsonPath('status', 'ok');

    AiUsageDaily::query()->create(['date' => $month->copy()->addDays(5)->toDateString(), 'cost_estimate_usd_micros' => 600_000]);
    ask($id, 'Encore une question')->assertJsonPath('status', 'unavailable')->assertJsonPath('reason', 'budget');

    // Alerte unique du mois aux administrateurs.
    User::factory()->admin()->create();
    app(CheckAssistantBudget::class)->handle();
    app(CheckAssistantBudget::class)->handle();
    expect(OutboundMessage::query()->where('template', 'ai_budget_alert')->get()
        ->filter(fn ($m) => ($m->payload['month'] ?? null) === $month->format('Y-m'))->count())->toBe(1);
});

it('applique le plafond de messages par conversation', function () {
    app(Settings::class)->set('ai.max_messages_per_conversation', 2);
    $id = startConversation();

    ask($id, 'Question une')->assertJsonPath('status', 'ok');
    ask($id, 'Question deux')->assertJsonPath('status', 'ok');
    ask($id, 'Question trois')->assertJsonPath('status', 'unavailable')
        ->assertJsonPath('reason', 'message_limit')
        ->assertJsonPath('message', FallbackTexts::MESSAGE_LIMIT);
});

it('limite à 5 conversations par visiteur et par jour', function () {
    foreach (range(1, 5) as $i) {
        startConversation();
    }

    $this->postJson(route('assistant.start'))->assertJsonPath('reason', 'visitor_limit');
});

it('rejette un message vide ou trop long sans appeler le modèle', function (string $text, string $reason) {
    config(['assistant.driver' => 'anthropic', 'assistant.api_key' => 'sk-test-factice', 'assistant.pricing' => ['claude-haiku-5-5' => ['input' => 1, 'output' => 5]]]);
    app(Settings::class)->set('ai.daily_budget_usd', 5);
    Http::fake();
    $id = startConversation();

    ask($id, $text)->assertJsonPath('status', 'notice')->assertJsonPath('reason', $reason);

    Http::assertNothingSent();
    expect(Message::query()->where('conversation_id', $id)->count())->toBe(0);
})->with([
    'vide' => ['   ', 'empty'],
    'trop long' => [str_repeat('a', 5000), 'too_long'],
]);

it('refuse l\'accès à une conversation ouverte par une autre session', function () {
    $conversation = Conversation::query()->create(['ip_hash' => str_repeat('a', 64)]);

    ask($conversation->id, 'Bonjour')->assertNotFound();
});

it('enregistre le coût estimé quand le tarif du modèle est renseigné', function () {
    config(['assistant.driver' => 'anthropic', 'assistant.api_key' => 'sk-test-factice', 'assistant.pricing' => ['claude-haiku-5-5' => ['input' => 1, 'output' => 5]]]);
    app(Settings::class)->set('ai.daily_budget_usd', 5);
    Http::fake(['api.anthropic.com/*' => Http::response([
        // Identifiant daté renvoyé par l\'API : le tarif du modèle demandé doit s\'appliquer.
        'model' => 'claude-haiku-5-5-20261101',
        'content' => [['type' => 'tool_use', 'input' => ['answer' => 'Je n\'ai pas cette information sur notre site.', 'grounding' => 'no_source', 'citations' => [], 'suggested_action' => 'handoff_whatsapp']]],
        'usage' => ['input_tokens' => 1000, 'output_tokens' => 100],
    ])]);
    $id = startConversation();

    ask($id, 'Êtes-vous certifiés ISO ?')->assertJsonPath('message.suggested_action', 'handoff_whatsapp')->assertJsonPath('whatsapp_text', fn (string $t) => mb_strlen($t) <= 300);

    // 1 000 × 1 $ + 100 × 5 $ par million de jetons = 1 500 millionièmes de dollar.
    expect(AiUsageDaily::today()->cost_estimate_usd_micros)->toBe(1500)
        ->and(Conversation::query()->findOrFail($id)->cost_estimate_usd_micros)->toBe(1500);
});

it('remplace une réponse en panne par le repli, sans rien inventer', function () {
    config(['assistant.driver' => 'anthropic', 'assistant.api_key' => 'sk-test-factice', 'assistant.retry_delay_ms' => 0, 'assistant.pricing' => ['claude-haiku-5-5' => ['input' => 1, 'output' => 5]]]);
    app(Settings::class)->set('ai.daily_budget_usd', 5);
    Http::fake(['api.anthropic.com/*' => Http::response([], 503)]);
    $id = startConversation();

    ask($id, 'Vous faites des logiciels de caisse ?')->assertJsonPath('message.content', FallbackTexts::UNAVAILABLE)->assertJsonPath('message.error_code', 'llm_unavailable');
});

it('propose un brief sans rien écrire avant la confirmation', function () {
    $id = startConversation();

    $proposal = ask($id, 'J\'ai une boutique de 3 points de vente à Bamako, je perds du stock')->json('proposal');

    expect($proposal['type'])->toBe('propose_brief')
        ->and($proposal['complete'])->toBeFalse()
        ->and($proposal['payload']['consent_processing'])->toBeFalse()
        ->and($proposal['payload']['marketing_opt_in'])->toBeFalse()
        ->and($proposal['payload']['contact']['phone'])->toBe('')
        ->and(Lead::query()->count())->toBe(0)
        ->and(Brief::query()->count())->toBe(0);
});

it('refuse un payload modifié après affichage', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');
    $revised = $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => completeBriefPayload($proposal)])->assertOk()->json('proposal');

    // Hash d'un autre contenu que celui affiché.
    $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => hash('sha256', 'autre')])
        ->assertStatus(422)->assertJsonPath('status', 'refused');

    // Payload altéré directement en base après affichage.
    $stored = AiActionProposal::query()->findOrFail($revised['id']);
    $stored->forceFill(['payload' => [...$stored->payload, 'marketing_opt_in' => true]])->save();
    $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => $revised['hash']])
        ->assertStatus(422)->assertJsonPath('status', 'refused');

    expect(Lead::query()->count())->toBe(0);
});

it('crée un seul brief et un seul lead malgré une double confirmation', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');
    $revised = $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => completeBriefPayload($proposal)])->json('proposal');

    expect($revised['complete'])->toBeTrue()
        ->and(AiActionProposal::query()->findOrFail($proposal['id'])->status)->toBe(ProposalStatus::Cancelled);

    $first = $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => $revised['hash']])->assertOk();
    $second = $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => $revised['hash']])->assertOk();

    $lead = Lead::query()->sole();
    expect($first->json('result.url'))->toStartWith(url('/brief/'))
        ->and($second->json('result.url'))->toBeNull()
        ->and($second->json('result.reference'))->toBe($first->json('result.reference'))
        ->and(Brief::query()->sole()->conversation_id)->toBe($id)
        ->and($lead->type)->toBe(LeadType::Assistant)
        ->and($lead->source)->toBe('assistant')
        ->and(Conversation::query()->findOrFail($id)->lead_id)->toBe($lead->id)
        ->and(AiActionProposal::query()->findOrFail($revised['id'])->status)->toBe(ProposalStatus::Executed);

    $this->get($first->json('result.url'))->assertOk()->assertSee("préparé avec l'assistant", false);
});

it('transmet une demande de rendez-vous confirmée, jamais annoncée comme confirmée', function () {
    $id = startConversation();
    $proposal = ask($id, 'Je veux un rendez-vous jeudi matin')->json('proposal');
    expect($proposal['type'])->toBe('request_appointment');

    $payload = [...$proposal['payload'],
        'slots' => [['date' => now('Africa/Bamako')->addDays(3)->toDateString(), 'period' => 'morning']],
        'contact' => ['full_name' => 'Awa Traoré', 'phone' => '74 66 64 89', 'email' => null, 'company' => null],
        'consent_processing' => true,
    ];
    $revised = $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => $payload])->assertOk()->json('proposal');

    $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => $revised['hash']])
        ->assertOk()->assertJsonPath('status', 'executed')
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'pas encore confirmée'));

    expect(Appointment::query()->sole()->status->value)->toBe('requested')
        ->and(Lead::query()->sole()->source)->toBe('assistant');
});

it('refuse de confirmer une carte incomplète ou expirée', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');

    $this->postJson(route('assistant.actions.confirm', $proposal['id']), ['hash' => $proposal['hash']])->assertStatus(422);

    $revised = $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => completeBriefPayload($proposal)])->json('proposal');
    $this->travel(31)->minutes();
    $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => $revised['hash']])->assertStatus(422);

    expect(AiActionProposal::query()->findOrFail($revised['id'])->status)->toBe(ProposalStatus::Expired)
        ->and(Lead::query()->count())->toBe(0);
});

it('valide la carte avec les règles des formulaires (consentement non coché refusé)', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');
    $payload = [...completeBriefPayload($proposal), 'consent_processing' => false];

    $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => $payload])
        ->assertStatus(422)->assertJsonValidationErrors('consent_processing');
});

it('annule une proposition sans rien écrire', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');

    $this->postJson(route('assistant.actions.cancel', $proposal['id']))->assertOk()->assertJsonPath('status', 'cancelled');

    expect(AiActionProposal::query()->findOrFail($proposal['id'])->status)->toBe(ProposalStatus::Cancelled)
        ->and(Lead::query()->count())->toBe(0);
});

it('crée une entrée à revoir pour un retour négatif', function () {
    $id = startConversation();
    $messageId = ask($id, 'Ça marche sans Internet ?')->json('message.id');

    $this->postJson(route('assistant.feedback', [$id, $messageId]), ['rating' => 'down', 'comment' => 'Pas clair'])->assertOk();

    $feedback = AiFeedback::query()->sole();
    expect($feedback->rating)->toBe(FeedbackRating::Down)
        ->and($feedback->review_status)->toBe(ReviewStatus::ToReview);

    $userMessage = Message::query()->where('conversation_id', $id)->orderBy('id')->firstOrFail();
    $this->postJson(route('assistant.feedback', [$id, $userMessage->id]), ['rating' => 'down'])->assertNotFound();
});

it('refuse une seconde modification de la même carte : jamais deux briefs', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');
    $payload = ['payload' => completeBriefPayload($proposal)];

    $first = $this->postJson(route('assistant.actions.revise', $proposal['id']), $payload)->assertOk()->json('proposal');
    $this->postJson(route('assistant.actions.revise', $proposal['id']), $payload)->assertStatus(422)->assertJsonValidationErrors('proposal');

    $this->postJson(route('assistant.actions.confirm', $first['id']), ['hash' => $first['hash']])->assertOk();

    expect(AiActionProposal::query()->where('status', ProposalStatus::Proposed)->count())->toBe(0)
        ->and(Brief::query()->count())->toBe(1)
        ->and(Lead::query()->count())->toBe(1);
});

it('n\'annule jamais une proposition déjà exécutée', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');
    $revised = $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => completeBriefPayload($proposal)])->json('proposal');
    $this->postJson(route('assistant.actions.confirm', $revised['id']), ['hash' => $revised['hash']])->assertOk();

    $this->postJson(route('assistant.actions.cancel', $revised['id']))->assertOk();
    $this->postJson(route('assistant.actions.revise', $revised['id']), ['payload' => completeBriefPayload($proposal)])->assertStatus(422);

    expect(AiActionProposal::query()->findOrFail($revised['id'])->status)->toBe(ProposalStatus::Executed);
});

it('laisse renvoyer une carte expirée sans perdre la saisie', function () {
    $id = startConversation();
    $proposal = ask($id, 'J\'ai une boutique, je perds du stock')->json('proposal');
    $this->travel(31)->minutes();

    $fresh = $this->postJson(route('assistant.actions.revise', $proposal['id']), ['payload' => completeBriefPayload($proposal)])->assertOk()->json('proposal');
    $this->postJson(route('assistant.actions.confirm', $fresh['id']), ['hash' => $fresh['hash']])->assertOk()->assertJsonPath('status', 'executed');

    expect(AiActionProposal::query()->findOrFail($proposal['id'])->status)->toBe(ProposalStatus::Expired);
});

it('indique hors horaires les horaires de l\'équipe, tirés des paramètres', function () {
    app(Settings::class)->set('contact.hours', 'Lundi–samedi, 8 h–18 h');
    $this->travelTo(Carbon::parse('2026-11-15 22:00', 'Africa/Bamako')); // dimanche soir
    $id = startConversation();

    ask($id, 'Je veux parler à quelqu\'un')
        ->assertJsonPath('team.open', false)
        ->assertJsonPath('team.hours', 'Lundi–samedi, 8 h–18 h');
});

it('ne bloque pas les autres visiteurs derrière la même IP (opérateurs mobiles)', function () {
    $start = app(StartConversation::class);

    foreach (range(1, 5) as $i) {
        expect($start->handle((string) Str::uuid(), '10.0.0.1', '/'))->toBeInstanceOf(Conversation::class);
    }
    $visitor = (string) Str::uuid();
    foreach (range(1, 5) as $i) {
        $start->handle($visitor, '10.0.0.1', '/');
    }

    expect($start->handle($visitor, '10.0.0.1', '/'))->toBeInstanceOf(GuardFailure::class)
        ->and($start->handle((string) Str::uuid(), '10.0.0.1', '/'))->toBeInstanceOf(Conversation::class);
});

it('répond aux salutations sans appeler le modèle et sans coût (D-021)', function () {
    config(['assistant.driver' => 'anthropic', 'assistant.api_key' => 'sk-test-factice', 'assistant.pricing' => ['claude-haiku-5-5' => ['input' => 1, 'output' => 5]]]);
    app(Settings::class)->set('ai.daily_budget_usd', 5);
    Http::fake();
    $id = startConversation();

    ask($id, 'Salut, ça va ?')->assertOk()
        ->assertJsonPath('message.content', fn (string $text) => str_contains($text, 'Kilakodon Assistant'))
        ->assertJsonPath('message.suggested_action', 'none');

    Http::assertNothingSent();
    $messages = Message::query()->where('conversation_id', $id)->orderBy('id')->get();
    expect($messages)->toHaveCount(2)
        ->and($messages[1]->model)->toBe('small-talk-v1')
        ->and($messages[1]->prompt_version)->toBe('small-talk-v1')
        ->and(Conversation::query()->findOrFail($id)->message_count)->toBe(2)
        ->and(AiUsageDaily::today()->requests)->toBe(0)
        ->and(AiUsageDaily::today()->cost_estimate_usd_micros)->toBe(0);
});

it('applique les gardes même aux salutations', function () {
    $id = startConversation();
    app(Settings::class)->set('ai.enabled', false);

    ask($id, 'salut')->assertJsonPath('status', 'unavailable')->assertJsonPath('reason', 'disabled');
    expect(Message::query()->where('conversation_id', $id)->count())->toBe(0);
});
