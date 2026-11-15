<?php

namespace App\Domain\Crm\Exceptions;

use RuntimeException;

/**
 * Opération CRM refusée par une règle métier : message affichable tel quel dans l'admin.
 */
class CrmException extends RuntimeException {}
