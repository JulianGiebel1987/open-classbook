<?php

namespace OpenClassbook\Middleware;

/**
 * Restricts access to admin, schulleitung and sekretariat roles.
 * Used for Zeugnis template management routes.
 *
 * Thin subclass of RbacMiddleware to work with the Router's
 * class-name-based middleware instantiation (no constructor args).
 */
class ZeugnisAdminMiddleware extends RbacMiddleware
{
    public function __construct()
    {
        parent::__construct(['admin', 'schulleitung', 'sekretariat']);
    }
}
