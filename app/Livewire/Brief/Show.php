<?php

namespace App\Livewire\Brief;

use App\Domain\Brief\Actions\ValidateBrief;
use App\Domain\Brief\Data\BriefCorrections;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Services\BriefAccess;
use App\Domain\Diagnostic\Engine\Catalog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Brief privé (UX §5.5, BRF-01..04) : le prospect relit, corrige ce qu'il a dit et le résumé,
 * puis valide ; chaque validation crée une version figée. Les recommandations restent en lecture seule.
 */
class Show extends Component
{
    #[Locked]
    public string $token = '';

    public bool $editing = false;

    public string $company = '';

    public string $contextNote = '';

    public string $summary = '';

    public string $comment = '';

    public function mount(Brief $brief, string $token): void
    {
        $this->token = $token;
        $this->fillFrom($brief);
        $this->editing = ! $brief->isValidated();
    }

    public function edit(): void
    {
        $this->fillFrom($this->brief());
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->fillFrom($this->brief());
        $this->resetErrorBag();
        $this->editing = ! $this->brief()->isValidated();
    }

    public function validateBrief(ValidateBrief $validate): void
    {
        $this->validate([
            'company' => ['nullable', 'string', 'max:160'],
            'contextNote' => ['nullable', 'string', 'max:'.Catalog::TEXT_MAX_LENGTH],
            'summary' => ['required', 'string', 'max:'.ValidateBrief::SUMMARY_MAX],
            'comment' => ['nullable', 'string', 'max:'.ValidateBrief::COMMENT_MAX],
        ], [
            'summary.required' => 'Le résumé ne peut pas être vide.',
            'summary.max' => 'Le résumé est trop long ('.ValidateBrief::SUMMARY_MAX.' caractères au maximum).',
            'contextNote.max' => 'Ce texte est trop long ('.Catalog::TEXT_MAX_LENGTH.' caractères au maximum).',
        ]);

        $brief = $this->brief();
        abort_unless($brief->isAccessible(), 410);

        $validate->handle($brief, new BriefCorrections($this->company, $this->contextNote, $this->summary, $this->comment));

        $this->comment = '';
        $this->editing = false;
        $this->dispatch('kh-brief-validated');
    }

    public function render(): View
    {
        $brief = $this->brief();

        return view('livewire.brief.show', [
            'brief' => $brief,
            'version' => $brief->currentVersion(),
        ]);
    }

    /**
     * Le brief est relu à chaque requête depuis le token : un lien révoqué entre-temps cesse de fonctionner.
     */
    private function brief(): Brief
    {
        return app(BriefAccess::class)->find($this->token) ?? abort(404);
    }

    private function fillFrom(Brief $brief): void
    {
        $version = $brief->currentVersion();
        $this->company = (string) ($version->declared['contact']['company'] ?? '');
        $this->contextNote = (string) ($version->declared['context_note'] ?? '');
        $this->summary = $version->summary;
    }
}
