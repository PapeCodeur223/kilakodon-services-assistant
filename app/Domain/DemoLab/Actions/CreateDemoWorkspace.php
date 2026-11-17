<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Exceptions\DemoLimitReached;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\DemoLabFixtures;
use App\Domain\Platform\Support\IpHasher;
use App\Domain\Platform\Support\PublicToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Crée l'espace d'un visiteur (LAB-01) : token aléatoire (seul le hash est stocké), données fictives
 * clonées, expiration à 24 h. Au plus 3 espaces par IP sur 24 h (TRD §8).
 */
class CreateDemoWorkspace
{
    public function __construct(
        private readonly IpHasher $ipHasher,
        private readonly DemoLabFixtures $fixtures,
    ) {}

    /**
     * @return array{workspace: DemoWorkspace, token: string}
     *
     * @throws DemoLimitReached
     */
    public function handle(string $ip, string $scenario = 'retail'): array
    {
        $ipHash = $this->ipHasher->hash($ip);

        // Verrou par IP : deux requêtes simultanées ne peuvent pas dépasser la limite ensemble.
        return Cache::lock("kh:lab-ip:{$ipHash}", 30)->block(10, fn (): array => $this->create($ipHash, $scenario));
    }

    /**
     * @return array{workspace: DemoWorkspace, token: string}
     */
    private function create(string $ipHash, string $scenario): array
    {
        $recent = DemoWorkspace::query()
            ->where('ip_hash', $ipHash)
            ->where('created_at', '>', now()->subDay())
            ->count();

        if ($recent >= DemoWorkspace::MAX_PER_IP_PER_DAY) {
            throw new DemoLimitReached('Nombre maximal d’espaces de démonstration atteint pour aujourd’hui.');
        }

        $token = PublicToken::generate();

        $workspace = DB::transaction(function () use ($token, $ipHash, $scenario): DemoWorkspace {
            $workspace = DemoWorkspace::query()->create([
                'token_hash' => $token['hash'],
                'scenario' => $scenario,
                'ip_hash' => $ipHash,
                'expires_at' => now()->addHours(DemoWorkspace::LIFETIME_HOURS),
            ]);

            $this->fixtures->seed($workspace);

            return $workspace;
        });

        return ['workspace' => $workspace, 'token' => $token['token']];
    }
}
