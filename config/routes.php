<?php

use OpenClassbook\Router;
use OpenClassbook\Controllers\AuthController;
use OpenClassbook\Controllers\DashboardController;
use OpenClassbook\Middleware\AuthMiddleware;
use OpenClassbook\Middleware\CsrfMiddleware;

/** @var Router $router */

// === Oeffentliche Routen (kein Login erforderlich) ===
$router->get('/', [AuthController::class, 'loginForm']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [CsrfMiddleware::class]);
$router->get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [CsrfMiddleware::class]);

// === Geschuetzte Routen (Login erforderlich) ===
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/change-password', [AuthController::class, 'changePasswordForm'], [AuthMiddleware::class]);
$router->post('/change-password', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);
