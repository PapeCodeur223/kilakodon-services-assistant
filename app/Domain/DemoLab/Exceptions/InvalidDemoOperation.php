<?php

namespace App\Domain\DemoLab\Exceptions;

use RuntimeException;

/**
 * Opération refusée avec un message affichable au visiteur (panier vide, stock insuffisant…).
 */
class InvalidDemoOperation extends RuntimeException {}
