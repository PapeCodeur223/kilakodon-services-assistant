<?php

namespace App\Domain\Assistant\Llm;

/**
 * AI_DRIVER=fake : réponses déterministes, sans réseau (tests et développement sans clé).
 */
final class FakeLlmClient implements LlmClient
{
    public const MODEL = 'fake';

    public function complete(LlmRequest $request): LlmResponse
    {
        $text = match ($request->purpose) {
            'diagnostic_summary' => $this->diagnosticSummary($request),
            default => 'Réponse de démonstration.',
        };

        return new LlmResponse($text, self::MODEL, mb_strlen($request->system) + mb_strlen(json_encode($request->messages) ?: ''), mb_strlen($text));
    }

    /**
     * Reformulation plausible construite à partir des seules données fournies (le DTO).
     */
    private function diagnosticSummary(LlmRequest $request): string
    {
        $data = json_decode($this->between($request->messages[0]['content'] ?? '', '<diagnostic>', '</diagnostic>'), true);
        $data = is_array($data) ? $data : [];

        $modules = array_column((array) ($data['recommendations'] ?? []), 'label');
        $pains = array_column((array) ($data['pains'] ?? []), 'label');

        $sentences = [
            'Votre organisation se situe aujourd\'hui au niveau « '.($data['maturity_label'] ?? 'Démarrage').' » : '.mb_strtolower((string) ($data['maturity_explanation'] ?? '')),
            $pains !== [] ? 'Vos priorités portent sur : '.mb_strtolower(implode(', ', $pains)).'.' : 'Vos priorités restent à préciser ensemble.',
            $modules !== [] ? 'Pour y répondre, nous recommandons d\'étudier en premier lieu : '.implode(', ', $modules).'.' : 'Un court échange nous permettra de préciser votre besoin avant toute recommandation.',
            'Chaque recommandation s\'appuie sur vos réponses et sera affinée lors d\'un premier échange avec notre équipe, qui vérifiera avec vous les points à clarifier, les données à reprendre et l\'ordre de mise en place le plus adapté à votre activité et à votre équipe.',
            'Ce résumé est un document de travail : il sert à préparer la discussion et ne constitue ni un devis ni un engagement.',
        ];

        return implode(' ', $sentences);
    }

    private function between(string $text, string $start, string $end): string
    {
        $from = mb_strpos($text, $start);
        $to = mb_strpos($text, $end);

        return $from === false || $to === false ? '' : mb_substr($text, $from + mb_strlen($start), $to - $from - mb_strlen($start));
    }
}
