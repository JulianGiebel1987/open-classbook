<?php

namespace OpenClassbook\Middleware;

use OpenClassbook\App;
use OpenClassbook\View;

class RbacMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(): bool
    {
        $userRole = App::currentUserRole();

        if (empty($this->allowedRoles) || in_array($userRole, $this->allowedRoles)) {
            return true;
        }

        http_response_code(403);
        View::render('errors/403');
        return false;
    }
}
