<?php

namespace App\Domain\Diagnostic\Exceptions;

use RuntimeException;

/**
 * Un template actif ou retiré est immuable : on le duplique en nouvelle version.
 */
class TemplateLockedException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Ce questionnaire est actif ou retiré : il ne peut plus être modifié. Dupliquez-le en nouvelle version.');
    }
}
