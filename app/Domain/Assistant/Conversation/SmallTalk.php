<?php

namespace App\Domain\Assistant\Conversation;

use App\Domain\Assistant\Enums\Grounding;
use App\Domain\Assistant\Enums\SuggestedAction;
use Illuminate\Support\Str;

/**
 * Échanges courants (salutations, « ça va ? », remerciements, au revoir, identité…) traités par des
 * règles fixes, sans appel au modèle : réponse immédiate, gratuite et identique que l'IA soit
 * branchée ou non (D-021). Le message n'est reconnu que s'il est ENTIÈREMENT composé de formules
 * connues : « salut, vous faites des logiciels de caisse ? » part au modèle, qui saluera aussi.
 * Les textes sont validés par la direction : aucune promesse de délai, de prix ni de disponibilité.
 */
final class SmallTalk
{
    /** Enregistré comme « modèle » et « version de prompt » des réponses de politesse. */
    public const ENGINE = 'small-talk-v1';

    /**
     * Formules reconnues (texte normalisé : minuscules, sans accents ni ponctuation) → intention.
     *
     * @var array<string, string>
     */
    private const PHRASES = [
        // Salutations
        'bonjour' => 'greeting', 'bjr' => 'greeting', 'bonsoir' => 'greeting', 'bsr' => 'greeting',
        'salut' => 'greeting', 'slt' => 'greeting', 'coucou' => 'greeting', 'cc' => 'greeting',
        'hello' => 'greeting', 'hi' => 'greeting', 'hey' => 'greeting', 'yo' => 'greeting',
        'salam' => 'salam', 'salam alaykoum' => 'salam', 'salam aleykoum' => 'salam', 'salam alaykum' => 'salam',
        'salamou alaykoum' => 'salam', 'assalamou alaykoum' => 'salam', 'assalam alaykoum' => 'salam',
        'as salam alaykoum' => 'salam', 'assalamu alaikum' => 'salam', 'salam aleikoum' => 'salam',
        'i ni ce' => 'bambara', 'aw ni ce' => 'bambara', 'i ni sogoma' => 'bambara', 'aw ni sogoma' => 'bambara',
        'i ni tile' => 'bambara', 'i ni wula' => 'bambara', 'aw ni wula' => 'bambara', 'i ni su' => 'bambara',
        // Nouvelles
        'ca va' => 'how', 'cv' => 'how', 'ca va bien' => 'how', 'comment ca va' => 'how', 'comment ca' => 'how',
        'comment allez vous' => 'how', 'comment vous allez' => 'how', 'vous allez bien' => 'how',
        'comment vas tu' => 'how', 'comment tu vas' => 'how', 'tu vas bien' => 'how', 'la forme' => 'how',
        'comment vous l avez' => 'how', 'comment tu l as' => 'how', 'quoi de neuf' => 'how',
        'tout va bien' => 'how', 'la famille' => 'how', 'et la famille' => 'how', 'et vous' => 'how', 'et toi' => 'how',
        'i ka kene' => 'how', 'here tun' => 'how', 'here sira' => 'how',
        // Merci
        'merci' => 'thanks', 'mrc' => 'thanks', 'merci beaucoup' => 'thanks', 'merci bien' => 'thanks',
        'merci infiniment' => 'thanks', 'grand merci' => 'thanks', 'thanks' => 'thanks', 'thank you' => 'thanks',
        'abaraka' => 'thanks', 'a barika' => 'thanks', 'barika' => 'thanks',
        // Au revoir
        'au revoir' => 'bye', 'aurevoir' => 'bye', 'bye' => 'bye', 'bye bye' => 'bye', 'a bientot' => 'bye',
        'a plus' => 'bye', 'a plus tard' => 'bye', 'ciao' => 'bye', 'tchao' => 'bye', 'a la prochaine' => 'bye',
        'bonne journee' => 'wish_day', 'bonne soiree' => 'wish_evening', 'bonne fin de journee' => 'wish_day',
        'bonne nuit' => 'bye', 'k an ben' => 'bye', 'an ka taa' => 'bye',
        // Acquiescement
        'ok' => 'ack', 'okay' => 'ack', 'oki' => 'ack', 'd accord' => 'ack', 'dac' => 'ack', 'daccord' => 'ack',
        'super' => 'ack', 'parfait' => 'ack', 'top' => 'ack', 'cool' => 'ack', 'tres bien' => 'ack', 'bien' => 'ack',
        'entendu' => 'ack', 'compris' => 'ack', 'genial' => 'ack', 'excellent' => 'ack', 'nickel' => 'ack', 'bien recu' => 'ack',
        // Identité (D-019)
        'qui es tu' => 'name', 'qui etes vous' => 'name', 'tu es qui' => 'name', 'vous etes qui' => 'name',
        'comment tu t appelles' => 'name', 'comment vous appelez vous' => 'name', 'comment vous vous appelez' => 'name',
        'quel est ton nom' => 'name', 'quel est votre nom' => 'name', 'ton nom' => 'name', 'votre nom' => 'name',
        'tu t appelles comment' => 'name', 'vous vous appelez comment' => 'name', 'c est quoi ton nom' => 'name',
        'qui t a developpe' => 'developer', 'qui vous a developpe' => 'developer', 'qui t a cree' => 'developer',
        'qui vous a cree' => 'developer', 'qui t a concu' => 'developer', 'qui vous a concu' => 'developer',
        'qui est ton developpeur' => 'developer', 'qui est votre developpeur' => 'developer',
        'ton developpeur' => 'developer', 'votre developpeur' => 'developer', 'qui t a fait' => 'developer', 'qui vous a fait' => 'developer',
        'tu es un robot' => 'human', 'vous etes un robot' => 'human', 'es tu un robot' => 'human', 'etes vous un robot' => 'human',
        'tu es humain' => 'human', 'vous etes humain' => 'human', 'es tu humain' => 'human', 'etes vous humain' => 'human',
        'tu es une ia' => 'human', 'vous etes une ia' => 'human', 'es tu une ia' => 'human', 'etes vous une ia' => 'human',
        'c est un robot' => 'human', 'je parle a un robot' => 'human', 'je parle a un humain' => 'human',
        // Aide
        'aide' => 'help', 'aidez moi' => 'help', 'aide moi' => 'help', 'help' => 'help', 'j ai besoin d aide' => 'help',
        'que pouvez vous faire' => 'help', 'que peux tu faire' => 'help', 'tu sais faire quoi' => 'help',
        'vous pouvez faire quoi' => 'help', 'tu peux faire quoi' => 'help', 'tu fais quoi' => 'help',
        'a quoi tu sers' => 'help', 'a quoi servez vous' => 'help', 'comment ca marche' => 'help',
        'je ne sais pas par ou commencer' => 'help', 'je sais pas par ou commencer' => 'help',
    ];

    /**
     * Mots d'adresse ignorés (« bonjour Kilakodon », « merci à vous », « salut l'équipe »…).
     *
     * @var list<string>
     */
    private const FILLERS = [
        'kilakodon', 'kilakodon services', 'kilakodon assistant', 'l assistant', 'assistant', 'l equipe', 'equipe',
        'monsieur', 'madame', 'mademoiselle', 'mr', 'mme', 'cher', 'chere', 'a vous', 'a toi', 'a tous', 'tout le monde',
        'encore', 'oui', 'beaucoup', 'bien', 'aussi', 'et', 'alors', 'donc', 'svp', 's il vous plait', 'stp', 'a vous aussi',
    ];

    /** Un message plus long n'est jamais une simple formule de politesse. */
    private const MAX_LENGTH = 80;

    private const OFFER = 'Je peux vous présenter nos logiciels de gestion, vous aider à clarifier votre besoin ou vous mettre en relation avec un conseiller de l’équipe, à Bamako.';

    /**
     * Réponse fixe si le message n'est qu'une formule courante, sinon null (le message part au modèle).
     */
    public function reply(string $message, ?\DateTimeInterface $now = null): ?AssistantAnswer
    {
        $intents = $this->intents($message);

        if ($intents === null) {
            return null;
        }

        $hour = (int) ($now ?? now(config('kh.display_timezone')))->format('G');
        $hello = $hour >= 18 || $hour < 4 ? 'Bonsoir' : 'Bonjour';
        $has = fn (string $intent): bool => in_array($intent, $intents, true);

        // Questions précises : la réponse attendue prime sur les salutations qui l'accompagnent.
        $text = match (true) {
            $has('developer') => 'Développé par Kilakodon Services.',
            $has('name') => 'Je suis Kilakodon Assistant, l’assistant de Kilakodon Services. '.self::OFFER.' Que puis-je faire pour vous ?',
            $has('human') => 'Je suis Kilakodon Assistant, un assistant automatique. Pour échanger avec une personne de l’équipe, il suffit de le demander : un conseiller peut prendre le relais sur WhatsApp.',
            $has('help') => $this->help(),
            default => null,
        };

        if ($text !== null) {
            $action = $has('help') ? SuggestedAction::StartDiagnostic : SuggestedAction::None;

            return new AssistantAnswer($text, Grounding::NoSource, [], $action);
        }

        $parts = [];
        if ($has('salam')) {
            $parts[] = 'Wa alaykoum salam !';
        } elseif ($has('bambara')) {
            $parts[] = 'I ni ce ! Bienvenue chez Kilakodon Services.';
        } elseif ($has('greeting')) {
            $parts[] = $hello.' et bienvenue chez Kilakodon Services !';
        }

        if ($has('how')) {
            $parts[] = 'Je vais très bien, merci ! J’espère que vous aussi.';
        }

        $opening = $parts !== [];
        $wish = $has('wish_day') || $has('wish_evening');
        $leaving = $has('bye') || $wish;

        if ($has('thanks')) {
            $parts[] = $leaving
                ? 'Avec plaisir, merci à vous pour votre visite !'
                : 'Avec plaisir ! Avez-vous une autre question ? Je suis là pour vous aider.';
        }

        if ($wish) {
            $parts[] = ($has('wish_evening') ? 'Bonne soirée' : 'Bonne journée').' à vous aussi !';
        }

        if ($leaving) {
            if (! $has('thanks') && ! $wish) {
                $parts[] = 'Merci pour votre visite et à bientôt !';
            }
            $parts[] = 'Si un projet se précise, notre équipe reste joignable sur WhatsApp ou via le [diagnostic gratuit](/diagnostic).';
        } elseif ($has('ack') && ! $has('thanks')) {
            $parts[] = $opening ? '' : 'Parfait ! N’hésitez pas si vous avez une autre question : je peux aussi vous orienter vers le [diagnostic gratuit](/diagnostic) ou un conseiller.';
        } elseif ($opening && ! $has('thanks')) {
            $parts = [implode(' ', $parts), 'Je suis Kilakodon Assistant : '.lcfirst(self::OFFER), 'Quelle est votre activité, et que cherchez-vous à améliorer (stock, caisse, facturation, suivi des clients…) ?'];
        }

        return new AssistantAnswer(trim(implode("\n\n", array_filter($parts))), Grounding::NoSource, [], SuggestedAction::None);
    }

    /**
     * Intentions reconnues, ou null si le message contient autre chose que des formules connues.
     *
     * @return list<string>|null
     */
    public function intents(string $message): ?array
    {
        if (mb_strlen($message) > self::MAX_LENGTH) {
            return null;
        }

        $text = $this->normalize($message);
        if ($text === '') {
            return null;
        }

        $phrases = self::PHRASES + array_fill_keys(self::FILLERS, 'filler');
        // Les formules les plus longues d'abord : « merci beaucoup » avant « merci ».
        uksort($phrases, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $intents = [];
        while ($text !== '') {
            $matched = false;
            foreach ($phrases as $phrase => $intent) {
                if ($text === $phrase || str_starts_with($text, $phrase.' ')) {
                    $intents[] = $intent;
                    $text = ltrim(mb_substr($text, mb_strlen($phrase)));
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return null;
            }
        }

        $intents = array_values(array_unique(array_diff($intents, ['filler'])));

        return $intents === [] ? null : $intents;
    }

    private function help(): string
    {
        return implode("\n", [
            'Avec plaisir ! Voici ce que je peux faire pour vous :',
            '- vous présenter nos solutions : ERP et CRM, stock et caisse, applications web et mobiles ;',
            '- vous expliquer notre méthode de travail, étape par étape ;',
            '- préparer avec vous un brief de votre projet, que vous relisez avant tout envoi ;',
            '- transmettre une demande de rendez-vous ou vous mettre en relation avec un conseiller.',
            '',
            'Si vous ne savez pas par où commencer, le diagnostic gratuit vous aide à y voir clair en 3 minutes.',
        ]);
    }

    private function normalize(string $message): string
    {
        $text = Str::lower(Str::ascii($message));
        // Ponctuation, émojis et apostrophes → espaces ; lettres répétées réduites (« saluuut » → « salut »).
        $text = (string) preg_replace('/[^a-z0-9]+/', ' ', $text);
        $text = (string) preg_replace('/([a-z])\1{2,}/', '$1', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}
