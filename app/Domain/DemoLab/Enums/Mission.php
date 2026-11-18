<?php

namespace App\Domain\DemoLab\Enums;

/**
 * Missions du guide (UX §5.6, CONTENT §5). La carte de conversion apparaît après 2 missions.
 */
enum Mission: string
{
    case Sale = 'sale';
    case Alert = 'alert';
    case Invoice = 'invoice';

    public const CONVERSION_AFTER = 2;

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Faites une vente',
            self::Alert => 'Déclenchez une alerte de stock',
            self::Invoice => 'Imprimez une facture',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Sale => 'Ajoutez des produits au panier, choisissez le paiement, puis « Encaisser ».',
            self::Alert => 'Vendez un produit marqué « bientôt bas » jusqu’à ce qu’il atteigne son seuil.',
            self::Invoice => 'Dans « Ventes », ouvrez la facture d’une vente : elle est prête à imprimer.',
        };
    }
}
