<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Data\StartDiagnostic;
use App\Domain\Diagnostic\Engine\VisibilityResolver;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Support\IpHasher;
use App\Domain\Platform\Support\PublicToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Démarre une session anonyme sur le template actif (DIA-03) et pré-remplit le secteur
 * s'il est fourni et valide (DIA-04) : une réponse proposée, que le visiteur peut changer.
 */
class StartDiagnosticSession
{
    public function __construct(
        private readonly IpHasher $ipHasher,
        private readonly VisibilityResolver $visibility,
    ) {}

    /**
     * @return array{session: DiagnosticSession, token: string}
     */
    public function handle(StartDiagnostic $data): array
    {
        $template = DiagnosticTemplate::query()->active()->first()
            ?? throw new RuntimeException('Aucun questionnaire de diagnostic actif.');

        $prefill = $this->prefill($template, $data->sector);
        $token = PublicToken::generate();

        $session = DB::transaction(function () use ($template, $data, $prefill, $token): DiagnosticSession {
            $session = DiagnosticSession::query()->create([
                'template_id' => $template->id,
                'resume_token_hash' => $token['hash'],
                'status' => SessionStatus::InProgress,
                'current_step' => 1,
                'entry' => $data->entry !== null ? Str::limit(preg_replace('/[^a-z0-9:_-]/i', '', $data->entry) ?? '', 64, '') : null,
                'prefill' => $prefill !== [] ? $prefill : null,
                'visitor_key' => $data->visitorKey !== null && Str::isUuid($data->visitorKey) ? $data->visitorKey : null,
                'ip_hash' => $this->ipHasher->hash($data->ip),
                'utm' => $data->utm !== [] ? $data->utm : null,
                'started_at' => now(),
                'expires_at' => now()->addDays(DiagnosticSession::LIFETIME_DAYS),
            ]);

            foreach ($prefill as $key => $value) {
                $session->answers()->create(['question_key' => $key, 'value' => $value]);
            }

            return $session;
        });

        return ['session' => $session, 'token' => $token['token']];
    }

    /**
     * @return array<string, string>
     */
    private function prefill(DiagnosticTemplate $template, ?string $sector): array
    {
        if ($sector === null || $sector === '') {
            return [];
        }

        $pruned = $this->visibility->prune($template->toDefinition(), ['sector' => $sector]);

        return isset($pruned['sector']) ? ['sector' => (string) $pruned['sector']] : [];
    }
}
