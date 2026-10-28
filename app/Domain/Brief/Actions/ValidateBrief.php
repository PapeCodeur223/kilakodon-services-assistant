<?php

namespace App\Domain\Brief\Actions;

use App\Domain\Brief\Data\BriefCorrections;
use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Brief\Enums\SummarySource;
use App\Domain\Brief\Enums\VersionAuthor;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Models\BriefVersion;
use App\Domain\Diagnostic\Engine\Catalog;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Validation par le prospect (BRF-02) : chaque validation crée une nouvelle version figée,
 * avec ses corrections ; la partie « recommandé » est recopiée telle quelle.
 */
class ValidateBrief
{
    public const SUMMARY_MAX = 2000;

    public const COMMENT_MAX = 1000;

    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(Brief $brief, BriefCorrections $corrections): BriefVersion
    {
        if (! $brief->isAccessible()) {
            throw new InvalidArgumentException('Ce lien a expiré ou a été révoqué.');
        }

        $summary = trim($corrections->summary);
        if ($summary === '') {
            throw new InvalidArgumentException('Le résumé ne peut pas être vide.');
        }

        return DB::transaction(function () use ($brief, $corrections, $summary): BriefVersion {
            $brief = Brief::query()->lockForUpdate()->findOrFail($brief->id);
            $current = $brief->currentVersion();

            $declared = $current->declared;
            $declared['contact']['company'] = $this->clean($corrections->company, 160);
            $declared['context_note'] = $this->clean($corrections->contextNote, Catalog::TEXT_MAX_LENGTH);

            $summary = mb_substr($summary, 0, self::SUMMARY_MAX);
            $summarySource = $summary === $current->summary ? $current->summary_source : SummarySource::ProspectEdited;

            $version = $brief->versions()->create([
                'version' => $brief->current_version + 1,
                'declared' => $declared,
                'recommended' => $current->recommended,
                'summary' => $summary,
                'summary_source' => $summarySource,
                'prospect_comment' => $this->clean($corrections->comment, self::COMMENT_MAX),
                'created_by' => VersionAuthor::Prospect,
            ]);

            $brief->forceFill([
                'status' => BriefStatus::Validated,
                'current_version' => $version->version,
                'validated_at' => now(),
                'pdf_path' => null,
            ])->save();

            $this->audit->handle(AuditAction::BriefValidated, $brief, ['after' => ['version' => $version->version]]);

            return $version;
        });
    }

    private function clean(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
