<?php

namespace App\Livewire\Diagnostic;

use App\Domain\Brief\Actions\RequestBrief;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Services\ContentFinder;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Support\PhoneNormalizer;
use App\Domain\Diagnostic\Actions\CompleteDiagnostic;
use App\Domain\Diagnostic\Actions\SaveDiagnosticAnswers;
use App\Domain\Diagnostic\Actions\StartDiagnosticSession;
use App\Domain\Diagnostic\Data\StartDiagnostic;
use App\Domain\Diagnostic\Engine\Catalog;
use App\Domain\Diagnostic\Engine\QuestionDefinition;
use App\Domain\Diagnostic\Engine\TemplateDefinition;
use App\Domain\Diagnostic\Engine\VisibilityResolver;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticResult;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Diagnostic\Services\DiagnosticSummaryWriter;
use App\Domain\Platform\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

/**
 * Questionnaire adaptatif (UX §5.4, DIA-01..04). Le composant reste mince : les questions visibles,
 * le nettoyage, l'enregistrement et le calcul passent par le moteur et les Actions.
 * La session n'est créée qu'au premier « Continuer » (pas de session pour une simple visite).
 */
class Wizard extends Component
{
    use UsesSpamProtection;

    public const SCREEN_QUESTIONS = 'questions';

    public const SCREEN_RESULT = 'result';

    /** Sessions créées par adresse IP et par jour (TRD §11). */
    public const SESSIONS_PER_DAY = 20;

    /** Demandes de brief par adresse IP et par heure. */
    public const BRIEFS_PER_HOUR = 5;

    #[Locked]
    public ?string $sessionId = null;

    #[Locked]
    public ?string $resumeToken = null;

    #[Locked]
    public ?string $entry = null;

    #[Locked]
    public ?string $prefillSector = null;

    #[Locked]
    public int $templateId = 0;

    /** @var array<string, mixed> */
    public array $answers = [];

    public int $step = 1;

    public string $screen = self::SCREEN_QUESTIONS;

    public bool $showResumeLink = false;

    public bool $showBriefForm = false;

    /** @var list<string> */
    public array $missing = [];

    public string $fullName = '';

    public string $phone = '';

    public string $email = '';

    public string $company = '';

    public bool $consentProcessing = false;

    public bool $consentMarketing = false;

    public HoneypotData $extraFields;

    public function mount(?DiagnosticSession $session = null, ?string $token = null, ?string $entry = null, ?string $sector = null): void
    {
        $this->extraFields = new HoneypotData;

        // Livewire passe un modèle vide (non persisté) quand aucune session n'est fournie.
        if ($session !== null && $session->exists) {
            $this->sessionId = $session->id;
            $this->resumeToken = $token;
            $this->templateId = $session->template_id;
            $this->answers = $session->answerMap();
            $this->step = $session->current_step;
            $this->screen = in_array($session->status, [SessionStatus::Completed, SessionStatus::Converted], true) && $session->result()->exists()
                ? self::SCREEN_RESULT
                : self::SCREEN_QUESTIONS;

            return;
        }

        $template = DiagnosticTemplate::query()->active()->first();
        abort_if($template === null, 503);

        $this->templateId = $template->id;
        $this->entry = $entry;
        $this->prefillSector = $sector;

        // DIA-04 : secteur proposé comme réponse modifiable, jamais imposé.
        if ($sector !== null) {
            $this->answers = $this->visibility()->prune($this->template(), ['sector' => $sector]);
        }
    }

    /**
     * Toute réponse modifiée recalcule les questions visibles et supprime les réponses devenues inutiles.
     */
    public function updatedAnswers(): void
    {
        $this->answers = $this->visibility()->prune($this->template(), $this->answers);
        $this->missing = array_values(array_intersect($this->missing, $this->missingKeys()));
    }

    /**
     * Choix multiple : coche ou décoche une option (le choix exclusif et le maximum sont gérés par le moteur).
     */
    public function toggle(string $key, string $value): void
    {
        $question = $this->template()->question($key);
        if ($question === null || ! $question->isMulti()) {
            return;
        }

        $current = $this->answers[$key] ?? [];
        $current = is_array($current) ? $current : [];

        if (in_array($value, $current, true)) {
            $current = array_values(array_diff($current, [$value]));
        } elseif ($question->option($value)['exclusive'] ?? false) {
            $current = [$value];
        } else {
            $exclusive = array_filter($current, fn (string $v): bool => (bool) ($question->option($v)['exclusive'] ?? false));
            $current = [...array_diff($current, $exclusive), $value];

            if ($question->maxSelections !== null && count($current) > $question->maxSelections) {
                $this->addError('answers.'.$key, "Choisissez au maximum {$question->maxSelections} réponses.");

                return;
            }
        }

        $this->resetErrorBag('answers.'.$key);
        $this->answers[$key] = $current;
        $this->updatedAnswers();
    }

    public function unknown(string $key): void
    {
        $question = $this->template()->question($key);

        if ($question !== null && $question->allowUnknown) {
            $this->answers[$key] = Catalog::UNKNOWN;
            $this->updatedAnswers();
        }
    }

    public function next(): void
    {
        $this->updatedAnswers();
        $this->missing = $this->missingKeys();

        if ($this->missing !== []) {
            $this->dispatch('kh-step-invalid');

            return;
        }

        if (! $this->ensureSession()) {
            return;
        }

        $steps = $this->template()->steps();
        $isLast = $this->step >= max($steps);
        $nextStep = $isLast ? $this->step : $this->step + 1;

        $this->answers = app(SaveDiagnosticAnswers::class)->handle($this->session(), $this->answers, $nextStep);

        if ($isLast) {
            app(CompleteDiagnostic::class)->handle($this->session());
            $this->screen = self::SCREEN_RESULT;
        } else {
            $this->step = $nextStep;
        }

        $this->dispatch('kh-step-changed');
    }

    public function back(): void
    {
        $this->missing = [];

        if ($this->screen === self::SCREEN_RESULT) {
            $this->screen = self::SCREEN_QUESTIONS;
            $this->step = max($this->template()->steps());
        } else {
            $this->step = max(1, $this->step - 1);
        }

        $this->dispatch('kh-step-changed');
    }

    public function editAnswers(): void
    {
        $this->screen = self::SCREEN_QUESTIONS;
        $this->step = 1;
        $this->showBriefForm = false;
        $this->dispatch('kh-step-changed');
    }

    public function saveForLater(): void
    {
        $this->updatedAnswers();

        if (! $this->ensureSession()) {
            return;
        }

        app(SaveDiagnosticAnswers::class)->handle($this->session(), $this->answers, $this->step);
        $this->showResumeLink = true;
    }

    public function openBriefForm(): void
    {
        $this->showBriefForm = true;
        $this->dispatch('kh-brief-form-opened');
    }

    public function requestBrief(): void
    {
        $this->protectAgainstSpam();

        $this->validate([
            'fullName' => ['required', 'string', 'min:2', 'max:160'],
            'phone' => ['required', 'string', 'max:30', function (string $_attribute, mixed $value, \Closure $fail): void {
                if (PhoneNormalizer::toE164((string) $value) === null) {
                    $fail('Ce numéro ne semble pas valide. Exemple : 74 66 64 89');
                }
            }],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:160'],
            'consentProcessing' => ['accepted'],
            'consentMarketing' => ['boolean'],
        ], [
            'fullName.required' => 'Indiquez votre nom.',
            'phone.required' => 'Indiquez un numéro de téléphone ou WhatsApp.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
            'consentProcessing.accepted' => 'Votre accord est nécessaire pour que nous puissions traiter votre demande.',
        ]);

        $key = 'brief-request:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::BRIEFS_PER_HOUR)) {
            $this->addError('fullName', 'Trop de demandes depuis cette connexion. Réessayez dans une heure ou écrivez-nous sur WhatsApp.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $session = $this->session();
        abort_if($session === null || $session->result()->doesntExist(), 404);

        $result = app(RequestBrief::class)->handle($session, new ProspectDetails(
            fullName: $this->fullName,
            phone: $this->phone,
            email: $this->email !== '' ? $this->email : null,
            company: $this->company !== '' ? $this->company : null,
            marketingOptIn: $this->consentMarketing,
            ip: request()->ip(),
        ));

        $this->redirectRoute('brief.show', ['token' => $result['token']]);
    }

    public function render(): View
    {
        $template = $this->template();
        $visibility = $this->visibility();
        $result = $this->screen === self::SCREEN_RESULT ? $this->session()?->result : null;

        return view('livewire.diagnostic.wizard', [
            'steps' => $template->steps(),
            'stepTitles' => self::STEP_TITLES,
            'questions' => $visibility->visibleQuestions($template, $this->answers, $this->step),
            'optionsFor' => fn (QuestionDefinition $q): array => $visibility->visibleOptions($q, $this->answers),
            'result' => $result,
            'outcome' => $result?->toOutcome(),
            'summary' => $result !== null ? $this->summary($result) : null,
            'resumeUrl' => $this->resumeToken !== null ? route('diagnostic.resume', ['token' => $this->resumeToken]) : null,
            'consentTexts' => (array) app(Settings::class)->get('consent.texts', []),
            'privacyVisible' => app(ContentFinder::class)->query(Page::class)->where('slug', 'confidentialite')->exists(),
            'questionsByKey' => $template->questions,
        ]);
    }

    public const STEP_TITLES = [
        1 => 'Votre activité',
        2 => 'Votre façon de travailler',
        3 => 'Votre contexte',
        4 => 'Votre projet',
    ];

    /**
     * @return array{text: string, ai: bool}
     */
    private function summary(DiagnosticResult $result): array
    {
        return $result->ai_summary !== null
            ? ['text' => $result->ai_summary, 'ai' => true]
            : ['text' => app(DiagnosticSummaryWriter::class)->template($result->toOutcome()), 'ai' => false];
    }

    /**
     * @return list<string>
     */
    private function missingKeys(): array
    {
        $missing = $this->visibility()->missingRequired($this->template(), $this->answers, $this->step);

        foreach ($this->visibility()->visibleQuestions($this->template(), $this->answers, $this->step) as $question) {
            $value = $this->answers[$question->key] ?? null;
            if ($question->maxSelections !== null && is_array($value) && count($value) > $question->maxSelections) {
                $missing[] = $question->key;
            }
        }

        return array_values(array_unique($missing));
    }

    private function ensureSession(): bool
    {
        if ($this->sessionId !== null) {
            return true;
        }

        $key = 'diagnostic-start:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::SESSIONS_PER_DAY)) {
            $this->addError('session', 'Trop de diagnostics ont été démarrés depuis cette connexion aujourd\'hui. Réessayez demain ou écrivez-nous sur WhatsApp.');

            return false;
        }
        RateLimiter::hit($key, 86400);

        $started = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(
            ip: (string) request()->ip(),
            entry: $this->entry,
            sector: $this->prefillSector,
            utm: array_map('strval', array_intersect_key((array) session('kh.utm', []), array_flip(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']))),
        ));

        $this->sessionId = $started['session']->id;
        $this->resumeToken = $started['token'];
        $this->templateId = $started['session']->template_id;

        return true;
    }

    private function session(): ?DiagnosticSession
    {
        return $this->sessionId !== null
            ? DiagnosticSession::query()->with(['template', 'result'])->find($this->sessionId)
            : null;
    }

    private function template(): TemplateDefinition
    {
        static $cache = [];

        return $cache[$this->templateId] ??= DiagnosticTemplate::query()->findOrFail($this->templateId)->toDefinition();
    }

    private function visibility(): VisibilityResolver
    {
        return app(VisibilityResolver::class);
    }
}
