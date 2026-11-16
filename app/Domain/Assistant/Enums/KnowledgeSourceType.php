<?php

namespace App\Domain\Assistant\Enums;

enum KnowledgeSourceType: string
{
    case Service = 'service';
    case Sector = 'sector';
    case Faq = 'faq';
    case Page = 'page';
    case Article = 'article';
    case CaseStudy = 'case_study';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Service => 'Solution',
            self::Sector => 'Secteur',
            self::Faq => 'FAQ',
            self::Page => 'Page',
            self::Article => 'Article',
            self::CaseStudy => 'Réalisation',
            self::Manual => 'Document manuel',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
