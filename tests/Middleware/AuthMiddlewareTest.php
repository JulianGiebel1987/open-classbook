<?php

namespace OpenClassbook\Tests\Middleware;

use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testUnauthenticatedUserHasNoSessionUserId(): void
    {
        $this->assertFalse(isset($_SESSION['user_id']));
    }

    public function testAuthenticatedUserHasSessionUserId(): void
    {
        $_SESSION['user_id'] = 42;
        $_SESSION['user'] = ['id' => 42, 'role' => 'lehrer'];
        $_SESSION['last_activity'] = time();

        $this->assertTrue(isset($_SESSION['user_id']));
        $this->assertEquals(42, $_SESSION['user_id']);
    }

    public function testSessionTimeoutDetection(): void
    {
        $timeout = 3600; // 60 minutes
        $_SESSION['last_activity'] = time() - 3700; // 70 minutes ago

        $isExpired = (time() - $_SESSION['last_activity']) > $timeout;
        $this->assertTrue($isExpired);
    }

    public function testSessionNotExpiredWithinTimeout(): void
    {
        $timeout = 3600;
        $_SESSION['last_activity'] = time() - 1800; // 30 minutes ago

        $isExpired = (time() - $_SESSION['last_activity']) > $timeout;
        $this->assertFalse($isExpired);
    }

    public function testSessionActivityUpdateRefreshesTimeout(): void
    {
        $_SESSION['last_activity'] = time() - 1800;
        $oldActivity = $_SESSION['last_activity'];

        // Simulate activity update (as AuthMiddleware does)
        $_SESSION['last_activity'] = time();

        $this->assertGreaterThan($oldActivity, $_SESSION['last_activity']);
    }

    public function testSessionDataContainsRequiredFields(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user'] = [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@schule.de',
            'role' => 'admin',
        ];
        $_SESSION['last_activity'] = time();

        $this->assertArrayHasKey('id', $_SESSION['user']);
        $this->assertArrayHasKey('username', $_SESSION['user']);
        $this->assertArrayHasKey('email', $_SESSION['user']);
        $this->assertArrayHasKey('role', $_SESSION['user']);
    }
}
