<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Enums\Mission;
use App\Domain\DemoLab\Models\DemoWorkspace;
use Illuminate\Support\Facades\DB;

/**
 * Coche une mission du guide (idempotent).
 */
class CompleteDemoMission
{
    public function handle(DemoWorkspace $workspace, Mission $mission): void
    {
        DB::transaction(function () use ($workspace, $mission): void {
            /** @var DemoWorkspace $locked */
            $locked = DemoWorkspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();

            if (! $locked->hasCompleted($mission)) {
                $locked->missions = [...($locked->missions ?? []), $mission->value];
                $locked->save();
            }

            $workspace->missions = $locked->missions;
        });
    }
}
