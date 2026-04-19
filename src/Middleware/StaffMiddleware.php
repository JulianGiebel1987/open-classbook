<?php

namespace OpenClassbook\Middleware;

/**
 * Beschränkt den Zugriff auf administrative Rollen:
 * admin, schulleitung und sekretariat.
 * Wird für Klassen- und Importverwaltung verwendet.
 */
class StaffMiddleware extends RbacMiddleware
{
    public function __construct()
    {
        parent::__construct(['admin', 'schulleitung', 'sekretariat']);
    }
}
