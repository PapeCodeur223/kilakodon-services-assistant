<?php

namespace Database\Factories\Content;

use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Models\CaseStudy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Réalisation fictive pour les tests uniquement (jamais seedée).
 *
 * @extends Factory<CaseStudy>
 */
class CaseStudyFactory extends Factory
{
    protected $model = CaseStudy::class;

    public function definition(): array
    {
        return [
            'slug' => 'realisation-test-'.Str::lower(Str::random(6)),
            'title' => 'Réalisation de test',
            'client_display' => 'anonymized',
            'client_descriptor' => 'Client de test',
            'context' => 'Contexte.',
            'challenge' => 'Défi.',
            'intervention' => 'Intervention.',
            'deliverables' => ['Livrable'],
        ];
    }

    public function authorized(): static
    {
        return $this->afterMaking(function (CaseStudy $caseStudy): void {
            $caseStudy->authorization_status = AuthorizationStatus::Granted;
            $caseStudy->authorization_note = 'Accord de test';
        });
    }
}
