<?php

namespace App\Domain\Brief\Exceptions;

use RuntimeException;

/**
 * Demande de brief refusée : message affichable tel quel au visiteur.
 */
class BriefRequestException extends RuntimeException
{
    public static function alreadyRequested(string $reference): self
    {
        return new self("Le brief {$reference} a déjà été demandé pour ce diagnostic. Contactez-nous avec cette référence pour en recevoir le lien.");
    }

    public static function notCompleted(): self
    {
        return new self('Votre diagnostic a changé depuis la restitution : terminez-le à nouveau avant de demander votre brief.');
    }
}
