<?php

namespace OpenClassbook\Tests\Controllers;

use OpenClassbook\Controllers\UserController;
use OpenClassbook\Services\AuthService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserControllerTest extends TestCase
{
    /**
     * Das generierte Zufallspasswort muss die Passwort-Policy erfuellen
     * (Laenge, Gross-/Kleinbuchstaben, Ziffer).
     */
    public function testGenerateRandomPasswordMeetsPasswordPolicy(): void
    {
        $method = (new ReflectionClass(UserController::class))->getMethod('generateRandomPassword');
        $method->setAccessible(true);

        for ($i = 0; $i < 100; $i++) {
            $password = $method->invoke(null, 12);

            $errors = AuthService::validatePassword($password);
            $this->assertEmpty(
                $errors,
                'Generiertes Passwort erfuellt Policy nicht: ' . $password . ' (' . implode(' ', $errors) . ')'
            );
            $this->assertSame(12, strlen($password));
        }
    }

    public function testGenerateRandomPasswordRespectsCustomLength(): void
    {
        $method = (new ReflectionClass(UserController::class))->getMethod('generateRandomPassword');
        $method->setAccessible(true);

        $password = $method->invoke(null, 20);
        $this->assertSame(20, strlen($password));
    }

    public function testGenerateRandomPasswordIsDifferentEachCall(): void
    {
        $method = (new ReflectionClass(UserController::class))->getMethod('generateRandomPassword');
        $method->setAccessible(true);

        $passwords = [];
        for ($i = 0; $i < 20; $i++) {
            $passwords[] = $method->invoke(null, 12);
        }

        // Es sollte praktisch unmoeglich sein, dass 20 aufeinanderfolgende
        // Generierungen Duplikate enthalten (Geburtstagsparadoxon vernachlaessigbar).
        $this->assertCount(20, array_unique($passwords));
    }
}
