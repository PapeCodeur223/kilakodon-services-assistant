<?php

namespace App\Filament\Resources\AiFeedback;

use App\Domain\Assistant\Actions\ReviewAssistantFeedback;
use App\Domain\Assistant\Enums\FeedbackRating;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\ReviewStatus;
use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Assistant\Models\Message;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\AiFeedback\Pages\ListAiFeedback;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * « Retours à revoir » (IA-06) : chaque 👎 arrive ici ; l'éditeur corrige le contenu source si besoin.
 */
class AiFeedbackResource extends Resource
{
    protected static ?string $model = AiFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandThumbDown;

    protected static string|UnitEnum|null $navigationGroup = 'Assistant';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Retours à revoir';

    protected static ?string $modelLabel = 'retour';

    protected static ?string $pluralModelLabel = 'retours';

    protected static ?string $slug = 'retours-ia';

    public static function getNavigationBadge(): ?string
    {
        $count = AiFeedback::query()->where('review_status', ReviewStatus::ToReview)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['message', 'reviewer']);
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
                TextColumn::make('rating')->label('Avis')->badge()
                    ->formatStateUsing(fn (FeedbackRating $state): string => $state->label())
                    ->color(fn (FeedbackRating $state): string => $state === FeedbackRating::Down ? 'danger' : 'success'),
                TextColumn::make('question')->label('Question du visiteur')->wrap()->limit(120)
                    ->getStateUsing(fn (AiFeedback $record): string => self::question($record)),
                TextColumn::make('message.content')->label('Réponse')->wrap()->limit(160),
                TextColumn::make('comment')->label('Commentaire')->wrap()->placeholder('—'),
                TextColumn::make('review_status')->label('Revue')->badge()
                    ->formatStateUsing(fn (ReviewStatus $state): string => $state->label())
                    ->color(fn (ReviewStatus $state): string => $state === ReviewStatus::ToReview ? 'warning' : 'gray'),
                TextColumn::make('reviewer.name')->label('Par')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('review_status')->label('Revue')->options(ReviewStatus::options())->default(ReviewStatus::ToReview->value),
                SelectFilter::make('rating')->label('Avis')->options(FeedbackRating::options()),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Marquer comme revu')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (AiFeedback $record): bool => auth()->user()?->can('update', $record) === true)
                    ->schema([
                        Radio::make('status')->label('Résultat')->required()->default(ReviewStatus::Reviewed->value)->options([
                            ReviewStatus::Reviewed->value => 'Revu : rien à changer',
                            ReviewStatus::Actioned->value => 'Traité : contenu ou FAQ corrigé',
                        ]),
                        Textarea::make('note')->label('Note interne')->rows(3)->maxLength(2000),
                    ])
                    ->action(function (AiFeedback $record, array $data): void {
                        /** @var User $user */
                        $user = auth()->user();
                        app(ReviewAssistantFeedback::class)->handle($record, ReviewStatus::from((string) $data['status']), $user, $data['note'] ?? null);
                        Notification::make()->title('Retour revu')->success()->send();
                    }),
            ]);
    }

    /**
     * Question du visiteur à laquelle répondait le message évalué.
     */
    private static function question(AiFeedback $record): string
    {
        return (string) Message::query()
            ->where('conversation_id', $record->message->conversation_id)
            ->where('role', MessageRole::User)
            ->where('id', '<', $record->message_id)
            ->orderByDesc('id')
            ->value('content');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiFeedback::route('/'),
        ];
    }
}
