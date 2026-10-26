<?php

namespace App\View\Components\Kh;

use App\View\Support\Halftone as HalftoneGrid;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Halftone extends Component
{
    public const TONES = [
        'blue-100' => 'text-kh-blue-100',
        'blue-200' => 'text-kh-blue-200',
        'blue-900' => 'text-kh-blue-900',
        'blue-700' => 'text-kh-blue-700',
        'smoke' => 'text-smoke',
    ];

    public function __construct(
        public int $size = 240,
        public string $tone = 'blue-100',
        public bool $animate = false,
    ) {}

    /**
     * @return list<array{cx: float, cy: float, r: float, d: float}>
     */
    public function points(): array
    {
        return HalftoneGrid::points($this->size);
    }

    public function toneClass(): string
    {
        return self::TONES[$this->tone] ?? self::TONES['blue-100'];
    }

    public function render(): View
    {
        return view('components.kh.halftone');
    }
}
