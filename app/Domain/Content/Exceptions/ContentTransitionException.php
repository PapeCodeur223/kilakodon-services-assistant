<?php

namespace App\Domain\Content\Exceptions;

use DomainException;

/**
 * Transition de statut refusée par une règle métier (message affichable à l'équipe).
 */
class ContentTransitionException extends DomainException {}
