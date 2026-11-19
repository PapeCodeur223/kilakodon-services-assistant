<?php

namespace App\Filament\Resources\PrivacyRequests\Pages;

use App\Domain\Crm\Actions\AnonymizeContact;
use App\Domain\Crm\Actions\ExportContactData;
use App\Domain\Crm\Actions\ProcessPrivacyRequest;
use App\Domain\Crm\Actions\RecordConsent;
use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Enums\PrivacyRequestStatus;
use App\Domain\Crm\Models\PrivacyRequest;
use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\PrivacyRequests\PrivacyRequestResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Traitement d'une demande de droits : 1) vérifier l'identité, 2) relier le contact,
 * 3) exporter ou anonymiser, 4) clore. Chaque étape passe par une Action journalisée.
 */
class EditPrivacyRequest extends EditRecord
{
    protected static string $resource = PrivacyRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label('Identité vérifiée')
                ->icon(Heroicon::OutlinedIdentification)
                ->color('success')
                ->visible(fn (): bool => $this->request()->verified_at === null && $this->isOpen())
                ->requiresConfirmation()
                ->modalDescription('Confirmez que vous avez vérifié que la demande vient bien de la personne concernée (rappel sur le numéro ou l’adresse connus).')
                ->action(fn () => LeadActions::run(
                    fn () => app(ProcessPrivacyRequest::class)->verify($this->request(), LeadActions::user() ?? abort(403)),
                    'Identité vérifiée',
                )),

            Action::make('export')
                ->label('Exporter les données (JSON)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (): bool => $this->canProcess() && $this->request()->contact?->anonymized_at === null)
                ->action(function (): ?StreamedResponse {
                    $request = $this->request();
                    if (! LeadActions::run(fn () => app(ProcessPrivacyRequest::class)->ensureProcessable($request), 'Export prêt')) {
                        return null;
                    }
                    $contact = $request->contact ?? abort(404);
                    $data = app(ExportContactData::class)->handle($contact, LeadActions::user() ?? abort(403));
                    $name = 'donnees-contact-'.$contact->id.'-'.now()->format('Y-m-d').'.json';

                    return response()->streamDownload(
                        fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                        $name,
                        ['Content-Type' => 'application/json; charset=utf-8'],
                    );
                }),

            Action::make('withdrawMarketing')
                ->label('Retirer le consentement marketing')
                ->icon(Heroicon::OutlinedBellSlash)
                ->visible(fn (): bool => $this->canProcess() && $this->request()->contact?->marketing_opt_in === true)
                ->requiresConfirmation()
                ->modalDescription('Le contact ne recevra plus de conseils ni d’actualités. Le retrait est enregistré (consentements en écriture seule).')
                ->action(function (): void {
                    app(RecordConsent::class)->handle($this->request()->contact ?? abort(404), ConsentPurpose::Marketing, false, ConsentSource::PrivacyRequest);
                    Notification::make()->success()->title('Consentement marketing retiré')->send();
                }),

            Action::make('anonymize')
                ->label('Anonymiser le contact')
                ->icon(Heroicon::OutlinedUserMinus)
                ->color('danger')
                ->visible(fn (): bool => $this->canProcess() && $this->request()->contact?->anonymized_at === null)
                ->modalHeading('Anonymiser définitivement ce contact ?')
                ->modalDescription(fn (): string => 'Contact concerné : '.($this->request()->contact !== null ? PrivacyRequestResource::contactLabel($this->request()->contact) : '—').'. Nom, téléphones, e-mail, messages, notes, conversations et e-mails liés seront effacés. Les chiffres (étapes, dates, sources) restent. Cette opération est irréversible.')
                ->schema([
                    TextInput::make('confirmation')->label('Tapez ANONYMISER pour confirmer')->required()->in(['ANONYMISER'])
                        ->validationMessages(['in' => 'Tapez exactement ANONYMISER.']),
                ])
                ->modalSubmitActionLabel('Anonymiser')
                ->action(function (): void {
                    $request = $this->request();
                    $user = LeadActions::user() ?? abort(403);

                    $done = LeadActions::run(function () use ($request, $user): void {
                        app(ProcessPrivacyRequest::class)->ensureProcessable($request);
                        app(AnonymizeContact::class)->handle($request->contact ?? abort(404), $user);
                    }, 'Contact anonymisé');

                    if ($done) {
                        $request->forceFill(['decision_note' => trim(($request->decision_note ?? '')."\nContact anonymisé le ".now(config('kh.display_timezone'))->format('d/m/Y H:i').'.')])->save();
                        $this->refreshFormData(['decision_note', 'contact_id']);
                    }
                }),

            Action::make('done')
                ->label('Marquer traitée')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->isOpen() && $this->request()->verified_at !== null)
                ->schema([Textarea::make('note')->label('Ce qui a été fait')->rows(3)->required()])
                ->action(fn (array $data) => $this->close(PrivacyRequestStatus::Done, $data['note'] ?? null, 'Demande traitée')),

            Action::make('reject')
                ->label('Rejeter')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('gray')
                ->visible(fn (): bool => $this->isOpen())
                ->schema([Textarea::make('note')->label('Motif du rejet')->rows(3)->required()
                    ->helperText('Par exemple : identité impossible à vérifier, aucune donnée trouvée.')])
                ->action(fn (array $data) => $this->close(PrivacyRequestStatus::Rejected, $data['note'] ?? null, 'Demande rejetée')),
        ];
    }

    /**
     * Un changement du contact relié annule la vérification d'identité (revue M3).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newContactId = isset($data['contact_id']) && $data['contact_id'] !== '' ? (int) $data['contact_id'] : null;

        if (array_key_exists('contact_id', $data) && app(ProcessPrivacyRequest::class)->contactChanged($this->request(), $newContactId, LeadActions::user() ?? abort(403))) {
            Notification::make()->warning()->title('Contact modifié : vérifiez à nouveau l’identité avant tout traitement.')->send();
        }

        return $data;
    }

    private function close(PrivacyRequestStatus $to, mixed $note, string $success): void
    {
        LeadActions::run(
            fn () => app(ProcessPrivacyRequest::class)->close($this->request(), LeadActions::user() ?? abort(403), $to, is_string($note) ? $note : null),
            $success,
        );
        $this->refreshFormData(['decision_note']);
    }

    private function request(): PrivacyRequest
    {
        /** @var PrivacyRequest $record */
        $record = $this->getRecord();

        return $record->refresh();
    }

    private function isOpen(): bool
    {
        return ! in_array($this->request()->status, [PrivacyRequestStatus::Done, PrivacyRequestStatus::Rejected], true);
    }

    /**
     * Export et anonymisation : identité vérifiée, contact relié, droit `process`.
     */
    private function canProcess(): bool
    {
        $request = $this->request();

        return $this->isOpen()
            && $request->verified_at !== null
            && $request->contact_id !== null
            && auth()->user()?->can('process', $request) === true;
    }
}
