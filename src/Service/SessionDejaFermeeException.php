<?php

namespace App\Service;

/**
 * Levée quand on tente de clôturer une session déjà fermée.
 */
class SessionDejaFermeeException extends \RuntimeException
{
}
