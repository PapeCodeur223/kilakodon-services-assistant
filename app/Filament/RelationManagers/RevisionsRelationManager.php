<?php

namespace App\Filament\RelationManagers;

use App\Domain\Content\Actions\RestoreRevision;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Models\ContentRevision;
use App\Domain\Platform\Models\User;
use Filament\Actions\Action;
use Filament\Infolists\Components\CodeEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * Historique des versions d'un contenu, avec restauration (WEB-01).
 */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Historique des versions';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('version', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('version')->label('Version')->prefix('v'),
                TextColumn::make('user.name')->label('Auteur')->placeholder('Système'),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone')),
            ])
            ->recordActions([
                Action::make('show')
                    ->label('Voir')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (ContentRevision $record): string => 'Version '.$record->version)
                    ->modalSubmitAction(false)
                    ->schema([
                        CodeEntry::make('data')
                            ->hiddenLabel()
                            ->state(fn (ContentRevision $record): string => (string) json_encode($record->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                    ]),
                Action::make('restore')
                    ->label('Restaurer')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->requiresConfirmation()
                    ->modalDescription('Les champs éditoriaux reprendront le contenu de cette version. Le statut ne change pas.')
                    ->visible(fn (): bool => $this->currentUser()?->can('update', $this->getOwnerRecord()) === true)
                    ->action(function (ContentRevision $record): void {
                        try {
                            $owner = $this->getOwnerRecord();
                            if (! $owner instanceof ContentModel) {
                                throw new AuthorizationException('Ce contenu ne gère pas de versions.');
                            }
                            app(RestoreRevision::class)->handle($owner, $record, $this->currentUser() ?? throw new AuthorizationException);
                            Notification::make()->success()->title('Version '.$record->version.' restaurée')->send();
                            $this->redirect(request()->header('Referer') ?? url()->previous());
                        } catch (AuthorizationException $e) {
                            Notification::make()->danger()->title('Action non autorisée')->body($e->getMessage())->send();
                        }
                    }),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
