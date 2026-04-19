<?php

namespace OpenClassbook\Middleware;

/**
 * Beschränkt den Zugriff auf Nutzer mit Rolle "admin".
 * Wird für hochprivilegierte Routen (z. B. Benutzerverwaltung) verwendet.
 */
class AdminMiddleware extends RbacMiddleware
{
    public function __construct()
    {
        parent::__construct(['admin']);
    }
}
