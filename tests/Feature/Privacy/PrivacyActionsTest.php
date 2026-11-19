<?php

use App\Domain\Assistant\Actions\PurgeConversations;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Brief\Models\BriefVersion;
use App\Domain\Crm\Actions\AnonymizeContact;
use App\Domain\Crm\Actions\ChangeLeadStage;
use App\Domain\Crm\Actions\ExportContactData;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LostReason;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Models\DiagnosticAnswer;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('exporte en JSON toutes les données d’un contact et journalise l’export (PRV-02)', function () {
    ['contact' => $contact] = privacyProspect();
    $admin = User::factory()->admin()->create();

    $export = app(ExportContactData::class)->handle($contact, $admin);
    $json = json_encode($export, JSON_UNESCAPED_UNICODE);

    expect($export)->toHaveKeys(['generated_at', 'contact', 'consents', 'leads', 'briefs', 'conversations', 'messages_sent'])
        ->and($export['contact']['full_name'])->toBe('Awa Traoré')
        ->and($export['leads'])->toHaveCount(1)
        ->and($json)->toContain('Chez Awa')->toContain('appelez-moi')
        ->and($json)->not->toContain('token')->not->toContain('ip_hash');

    expect(AuditLog::query()->where('action', AuditAction::Exported)->where('auditable_id', $contact->id)->exists())->toBeTrue();
});

it('anonymise le contact, ses leads, briefs, conversations et e-mails, en gardant les agrégats', function () {
    ['contact' => $contact, 'lead' => $lead, 'brief' => $brief, 'conversation' => $conversation] = privacyProspect();
    $admin = User::factory()->admin()->create();

    // Fragments relevés par la revue : motif libre de perte (historique, activité système, audit)
    // et réponse libre d'un brief préparé par l'assistant.
    $lead = app(ChangeLeadStage::class)->handle(
        $lead, LeadStage::Lost, $admin, 'Mme Traoré a pris un concurrent, rappeler au 76 12 34 56', LostReason::Competitor,
    );
    BriefVersion::query()->create([
        'brief_id' => $brief->id, 'version' => 9, 'summary' => 'Résumé', 'summary_source' => 'template', 'created_by' => 'assistant', 'recommended' => [],
        'declared' => ['contact' => ['full_name' => 'Awa Traoré'], 'answers' => [['key' => 'main_problem', 'question' => 'Problème', 'answer' => 'Ma boutique de Kalaban perd du stock', 'step' => 'assistant']], 'context_note' => null],
    ]);
    $stage = $lead->stage;

    app(AnonymizeContact::class)->handle($contact, $admin);

    $contact->refresh();
    expect($contact->full_name)->toBe("Contact anonymisé #{$contact->id}")
        ->and($contact->phone_e164)->toBeNull()
        ->and($contact->email)->toBeNull()
        ->and($contact->anonymized_at)->not->toBeNull();

    // Agrégats conservés : le lead reste, avec son étape et sa source.
    $lead->refresh();
    expect($lead->stage)->toBe($stage)->and($lead->source)->not->toBeEmpty();

    $brief->refresh();
    expect($brief->isAccessible())->toBeFalse();

    $dump = json_encode([
        Contact::query()->find($contact->id)?->toArray(),
        Lead::query()->find($lead->id)?->toArray(),
        $lead->activities()->get()->toArray(),
        DB::table('brief_versions')->where('brief_id', $brief->id)->get()->toArray(),
        DiagnosticAnswer::query()->where('session_id', $brief->diagnostic_session_id)->get()->toArray(),
        OutboundMessage::query()->get()->toArray(),
        Company::query()->get()->toArray(),
        Message::query()->where('conversation_id', $conversation->id)->get()->toArray(),
        DB::table('lead_stage_history')->where('lead_id', $lead->id)->get()->toArray(),
        DB::table('audit_logs')->where('action', '!=', AuditAction::Anonymized->value)->get()->toArray(),
    ], JSON_UNESCAPED_UNICODE);

    expect($dump)->not->toContain('Awa')->not->toContain('Traoré')->not->toContain('awa@example.test')
        ->not->toContain('76123456')->not->toContain('12 34 56')->not->toContain('Kalaban');

    expect($conversation->refresh()->visitor_key)->toBeNull()
        ->and(AuditLog::query()->where('action', AuditAction::Anonymized)->where('auditable_id', $contact->id)->exists())->toBeTrue();
});

it('refuse d’anonymiser deux fois le même contact', function () {
    ['contact' => $contact] = privacyProspect();
    $admin = User::factory()->admin()->create();
    app(AnonymizeContact::class)->handle($contact, $admin);

    expect(fn () => app(AnonymizeContact::class)->handle($contact->refresh(), $admin))->toThrow(CrmException::class);
});

it('purge les conversations non liées à un lead de plus de 90 jours, et elles seules (PRV-03)', function () {
    $old = Conversation::query()->create(['ip_hash' => str_repeat('a', 64)]);
    $old->forceFill(['created_at' => now()->subDays(91), 'last_message_at' => now()->subDays(91)])->save();
    Message::query()->create(['conversation_id' => $old->id, 'role' => 'user', 'content' => 'Bonjour']);

    $recent = Conversation::query()->create(['ip_hash' => str_repeat('b', 64)]);
    $recent->forceFill(['created_at' => now()->subDays(10), 'last_message_at' => now()->subDays(10)])->save();

    $linked = makeLead();
    $kept = Conversation::query()->create(['ip_hash' => str_repeat('d', 64), 'lead_id' => $linked->id]);
    $kept->forceFill(['created_at' => now()->subDays(200), 'last_message_at' => now()->subDays(200)])->save();

    expect(app(PurgeConversations::class)->handle())->toBe(1)
        ->and(Conversation::query()->pluck('id')->all())->toEqualCanonicalizing([$recent->id, $kept->id])
        ->and(Message::query()->where('conversation_id', $old->id)->exists())->toBeFalse();
});

it('planifie la purge des conversations chaque jour à 02:15', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toMatch('/^\s*15\s+2\s+\*\s+\*\s+\*\s+php artisan kh:conversations-purge/m');
});
