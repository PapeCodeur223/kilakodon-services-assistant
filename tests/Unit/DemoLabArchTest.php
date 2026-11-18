<?php

use App\Domain\DemoLab\Models\Concerns\BelongsToWorkspace;
use App\Domain\DemoLab\Models\DemoWorkspace;

// Isolation du Démo Lab (BACKEND_SCHEMA §7) : chaque modèle Demo* porte le global scope de l'espace.
arch('tous les modèles du Démo Lab, sauf l’espace lui-même, utilisent BelongsToWorkspace')
    ->expect('App\Domain\DemoLab\Models')
    ->classes()
    ->toUseTrait(BelongsToWorkspace::class)
    ->ignoring([DemoWorkspace::class, 'App\Domain\DemoLab\Models\Concerns']);

// Modèles et actions séparés du CRM, sans aucun envoi (TRD §8).
arch('le Démo Lab ne dépend ni du CRM ni des envois')
    ->expect('App\Domain\DemoLab')
    ->not->toUse([
        'App\Domain\Crm',
        'App\Domain\Platform\Actions\QueueOutboundMessage',
        'Illuminate\Support\Facades\Mail',
        'Illuminate\Support\Facades\Notification',
        'Illuminate\Support\Facades\Http',
    ]);
