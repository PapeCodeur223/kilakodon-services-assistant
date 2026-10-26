<?php

namespace App\View\Components\Kh;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Icône Lucide (resources/svg/lucide, ISC) ou de marque (resources/svg/brands), rendue en SVG inline.
 */
class Icon extends Component
{
    /** @var array<string, string> */
    private static array $cache = [];

    public function __construct(
        public string $name,
        public int $size = 20,
        public ?string $label = null,
    ) {}

    public function inner(): string
    {
        return self::$cache[$this->name] ??= $this->load();
    }

    public function isBrand(): bool
    {
        return str_starts_with($this->name, 'brand:');
    }

    public function render(): View
    {
        return view('components.kh.icon');
    }

    private function load(): string
    {
        $path = $this->isBrand()
            ? resource_path('svg/brands/'.substr($this->name, 6).'.svg')
            : resource_path('svg/lucide/'.$this->name.'.svg');

        if (! preg_match('/^(brand:)?[a-z0-9-]+$/', $this->name) || ! is_file($path)) {
            throw new InvalidArgumentException("Icône inconnue : {$this->name}");
        }

        $svg = (string) file_get_contents($path);
        $svg = (string) preg_replace('/<!--.*?-->|<title>.*?<\/title>/s', '', $svg);

        // On ne garde que le contenu : l'élément <svg> est reconstruit par la vue.
        return trim((string) preg_replace('/^.*?<svg[^>]*>|<\/svg>\s*$/s', '', $svg));
    }
}
