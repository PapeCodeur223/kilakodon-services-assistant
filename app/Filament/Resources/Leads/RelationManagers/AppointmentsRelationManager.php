<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Rendez-vous du lead, avec les mêmes colonnes et actions que l'écran « Rendez-vous ».
 */
class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Rendez-vous';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return AppointmentResource::table($table)->filters([]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
