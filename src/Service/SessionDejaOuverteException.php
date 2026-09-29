<?php

namespace App\Service;

/**
 * Levée quand on tente d'ouvrir une session sur un poste qui en a déjà une OUVERTE.
 */
class SessionDejaOuverteException extends \RuntimeException
{
}
