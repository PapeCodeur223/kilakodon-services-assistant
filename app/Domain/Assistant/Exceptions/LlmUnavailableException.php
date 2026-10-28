<?php

namespace App\Domain\Assistant\Exceptions;

use RuntimeException;

/**
 * IA coupée, non configurée ou en échec : l'appelant bascule sur son repli (P5).
 */
class LlmUnavailableException extends RuntimeException {}
