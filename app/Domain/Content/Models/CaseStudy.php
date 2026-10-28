<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Enums\ClientDisplay;
use App\Domain\Content\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Réalisation. Publication impossible sans autorisation écrite du client (WEB-03).
 *
 * @property int $id
 * @property string $slug
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string $title
 * @property ClientDisplay $client_display
 * @property string|null $client_name
 * @property string $client_descriptor
 * @property int|null $sector_id
 * @property string $context
 * @property string $challenge
 * @property string $intervention
 * @property list<string> $deliverables
 * @property string|null $results
 * @property bool $results_verified
 * @property string|null $quote
 * @property string|null $quote_author
 * @property AuthorizationStatus $authorization_status
 * @property string|null $authorization_note
 * @property int|null $authorized_by
 * @property Carbon|null $authorized_at
 * @property string|null $cover_path
 * @property-read Sector|null $sector
 */
class CaseStudy extends ContentModel
{
    protected $fillable = [
        'slug', 'seo_title', 'seo_description', 'og_image_path', 'sort',
        'title', 'client_display', 'client_name', 'client_descriptor', 'sector_id',
        'context', 'challenge', 'intervention', 'deliverables', 'results', 'results_verified',
        'quote', 'quote_author', 'cover_path',
    ];

    protected $attributes = [
        'authorization_status' => 'none',
        'results_verified' => false,
    ];

    protected static function booted(): void
    {
        // Verrou modèle, en plus de la politique : jamais de réalisation publiée sans autorisation accordée.
        static::saving(function (CaseStudy $caseStudy): void {
            if ($caseStudy->status === ContentStatus::Published && $caseStudy->authorization_status !== AuthorizationStatus::Granted) {
                throw new LogicException('Une réalisation ne peut pas être publiée sans autorisation client accordée.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'client_display' => ClientDisplay::class,
            'authorization_status' => AuthorizationStatus::class,
            'deliverables' => 'array',
            'results_verified' => 'boolean',
            'authorized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function clientLabel(): string
    {
        return $this->client_display === ClientDisplay::Named && filled($this->client_name)
            ? (string) $this->client_name
            : $this->client_descriptor;
    }

    /**
     * Aucun résultat chiffré sans vérification (WEB-03).
     */
    public function showsResults(): bool
    {
        return $this->results_verified && filled($this->results);
    }

    public function showsQuote(): bool
    {
        return $this->authorization_status === AuthorizationStatus::Granted && filled($this->quote);
    }

    public function contentFields(): array
    {
        return ['title', 'client_display', 'client_name', 'client_descriptor', 'sector_id', 'context', 'challenge', 'intervention', 'deliverables', 'results', 'results_verified', 'quote', 'quote_author', 'cover_path'];
    }

    public function publicUrl(): string
    {
        return route('cases.show', $this->slug);
    }
}
