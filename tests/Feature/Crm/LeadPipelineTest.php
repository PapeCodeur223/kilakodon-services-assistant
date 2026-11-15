<?php

use App\Domain\Crm\Actions\AssignLead;
use App\Domain\Crm\Actions\ChangeLeadStage;
use App\Domain\Crm\Actions\LogActivity;
use App\Domain\Crm\Actions\MergeContacts;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LostReason;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadStageHistory;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Carbon;

it('historise chaque changement d\'étape avec auteur, date et motif (CRM-02)', function () {
    $sales = User::factory()->sales()->create();
    $lead = makeLead();

    app(ChangeLeadStage::class)->handle($lead, LeadStage::Qualified, $sales);
    app(ChangeLeadStage::class)->handle($lead, LeadStage::OnHold, $sales, 'Rappeler après la rentrée scolaire');

    $history = LeadStageHistory::query()->where('lead_id', $lead->id)->orderBy('id')->get();
    expect($history)->toHaveCount(2)
        ->and($history[0]->from_stage)->toBe(LeadStage::New)
        ->and($history[0]->to_stage)->toBe(LeadStage::Qualified)
        ->and($history[0]->user_id)->toBe($sales->id)
        ->and($history[1]->reason)->toBe('Rappeler après la rentrée scolaire')
        ->and($lead->refresh()->stage)->toBe(LeadStage::OnHold)
        ->and($lead->first_response_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', AuditAction::StatusChanged)->count())->toBe(2);
});

it('exige un motif pour perdre ou mettre en pause un lead', function () {
    $lead = makeLead();
    $change = app(ChangeLeadStage::class);

    expect(fn () => $change->handle($lead, LeadStage::Lost, null))->toThrow(CrmException::class)
        ->and(fn () => $change->handle($lead, LeadStage::Lost, null, null, LostReason::Other))->toThrow(CrmException::class, 'Autre')
        ->and(fn () => $change->handle($lead, LeadStage::OnHold, null, '   '))->toThrow(CrmException::class)
        ->and($lead->refresh()->stage)->toBe(LeadStage::New);

    $change->handle($lead, LeadStage::Lost, null, 'Budget reporté à 2027', LostReason::Timing);

    expect($lead->refresh()->stage)->toBe(LeadStage::Lost)
        ->and($lead->lost_reason)->toBe(LostReason::Timing)
        ->and(LeadStageHistory::query()->where('lead_id', $lead->id)->sole()->reason)->toBe('Calendrier — Budget reporté à 2027');
});

it('refuse les transitions non autorisées et garde « gagné » définitif', function () {
    $change = app(ChangeLeadStage::class);

    expect(fn () => $change->handle(makeLead(), LeadStage::Won, null))->toThrow(CrmException::class, 'Passage impossible');

    $won = makeLead(['stage' => LeadStage::Proposal]);
    $change->handle($won, LeadStage::Won, null);
    expect(fn () => $change->handle($won, LeadStage::Qualified, null))->toThrow(CrmException::class)
        ->and(LeadStage::Won->allowedTransitions())->toBe([]);

    // Un lead perdu peut être repris.
    $lost = makeLead(['stage' => LeadStage::Lost]);
    expect($change->handle($lost, LeadStage::Qualified, null)->stage)->toBe(LeadStage::Qualified);
});

it('liste « à traiter » les leads actifs sans prochaine action ou dont l\'action est échue', function () {
    $noAction = makeLead();
    $overdue = makeLead(['next_action' => 'Rappeler', 'next_action_at' => now()->subHour()]);
    $planned = makeLead(['next_action' => 'Envoyer la démo', 'next_action_at' => now()->addDay()]);
    $lost = makeLead(['stage' => LeadStage::Lost]);

    $ids = Lead::query()->toHandle()->pluck('id')->all();

    expect($ids)->toContain($noAction->id, $overdue->id)
        ->not->toContain($planned->id)
        ->not->toContain($lost->id);
});

it('n\'attribue un lead qu\'à un commercial ou un administrateur actif', function () {
    $lead = makeLead();
    $sales = User::factory()->sales()->create();
    $editor = User::factory()->editor()->create();
    $inactive = User::factory()->sales()->create(['is_active' => false]);

    app(AssignLead::class)->handle($lead, $sales, null);
    expect($lead->refresh()->owner_id)->toBe($sales->id)
        ->and($lead->activities()->first()->body)->toContain($sales->name);

    expect(fn () => app(AssignLead::class)->handle($lead, $editor, null))->toThrow(CrmException::class)
        ->and(fn () => app(AssignLead::class)->handle($lead, $inactive, null))->toThrow(CrmException::class);
});

it('enregistre les activités internes et fixe le délai de prise en charge (CRM-04)', function () {
    $sales = User::factory()->sales()->create();
    $lead = makeLead();
    $log = app(LogActivity::class);

    expect(fn () => $log->handle($lead, ActivityType::Task, 'Rappeler', $sales))->toThrow(CrmException::class, 'échéance')
        ->and(fn () => $log->handle($lead, ActivityType::System, 'x', $sales))->toThrow(CrmException::class)
        ->and(fn () => $log->handle($lead, ActivityType::Note, '  ', $sales))->toThrow(CrmException::class);

    Carbon::setTestNow('2026-11-02 10:00:00');
    $task = $log->handle($lead, ActivityType::Task, 'Envoyer la présentation', $sales, now()->addDay());

    expect($lead->refresh()->first_response_at?->toDateTimeString())->toBe('2026-11-02 10:00:00')
        ->and($task->isOpenTask())->toBeTrue()
        ->and($log->complete($task)->done_at)->not->toBeNull();
    Carbon::setTestNow();
});

it('fusionne deux contacts manuellement, sans jamais le faire automatiquement (CRM-01)', function () {
    $admin = User::factory()->admin()->create();
    $kept = makeLead([], ['full_name' => 'Awa Diarra', 'email' => null])->contact;
    $duplicateLead = makeLead([], ['full_name' => 'Awa Diarra', 'phone_e164' => $kept->phone_e164, 'email' => 'awa@example.test']);
    $duplicate = $duplicateLead->contact;
    Consent::query()->create(['contact_id' => $duplicate->id, 'purpose' => 'marketing', 'granted' => true, 'text_version' => '1', 'source' => 'contact_form']);

    // Deux homonymes au même numéro restent deux contacts tant qu'un humain ne fusionne pas.
    expect(Contact::query()->count())->toBe(2);

    app(MergeContacts::class)->handle($duplicate, $kept, $admin);

    expect($duplicateLead->refresh()->contact_id)->toBe($kept->id)
        ->and($kept->refresh()->email)->toBe('awa@example.test')
        ->and(Consent::query()->where('contact_id', $kept->id)->count())->toBe(1)
        ->and(Contact::withTrashed()->find($duplicate->id)->merged_into_id)->toBe($kept->id)
        ->and(Contact::query()->find($duplicate->id))->toBeNull()
        ->and(AuditLog::query()->where('action', AuditAction::ContactMerged)->count())->toBe(1);

    expect(fn () => app(MergeContacts::class)->handle($kept, $kept, $admin))->toThrow(CrmException::class);
});
