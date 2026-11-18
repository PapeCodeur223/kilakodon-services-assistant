<?php

namespace App\Domain\DemoLab\Exceptions;

use RuntimeException;

/**
 * Limite du Démo Lab atteinte (TRD §8) : espaces par IP et par jour, ou actions par espace.
 */
class DemoLimitReached extends RuntimeException {}
