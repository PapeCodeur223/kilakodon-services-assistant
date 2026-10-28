<?php

namespace App\Domain\Diagnostic\Jobs;

use App\Domain\Diagnostic\Models\DiagnosticResult;
use App\Domain\Diagnostic\Services\DiagnosticSummaryWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reformulation IA du résultat (TRD §6). Idempotent : sans effet si un résumé existe déjà.
 * En cas d'échec ou de sortie rejetée, rien n'est écrit : l'affichage utilise le gabarit.
 */
class GenerateDiagnosticSummary implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $resultId) {}

    public function handle(DiagnosticSummaryWriter $writer): void
    {
        $result = DiagnosticResult::query()->find($this->resultId);

        if ($result === null || $result->ai_summary !== null) {
            return;
        }

        $response = $writer->ai($result->toOutcome());

        if ($response === null) {
            return;
        }

        $result->forceFill([
            'ai_summary' => $response->text,
            'ai_summary_model' => mb_substr($response->model, 0, 64),
        ])->save();
    }
}
