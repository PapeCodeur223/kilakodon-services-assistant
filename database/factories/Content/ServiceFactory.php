<?php

namespace Database\Factories\Content;

use App\Domain\Content\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $title = 'Solution de test '.Str::random(6);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'tagline' => 'Accroche de test.',
            'icon' => 'package',
            'problem' => 'Problème de test.',
            'audience' => 'Public de test.',
            'benefits' => [['title' => 'Bénéfice', 'text' => 'Texte']],
            'features' => [['title' => 'Fonction']],
            'faq' => [['q' => 'Question ?', 'a' => 'Réponse.']],
        ];
    }
}
