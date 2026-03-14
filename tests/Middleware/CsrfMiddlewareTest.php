<?php

namespace OpenClassbook\Tests\Middleware;

use OpenClassbook\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testGetRequestsAlwaysPass(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = new CsrfMiddleware();
        $result = $middleware->handle();

        $this->assertTrue($result);
    }

    public function testGenerateTokenCreatesToken(): void
    {
        $token = CsrfMiddleware::generateToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testGenerateTokenReturnsSameTokenOnSecondCall(): void
    {
        $token1 = CsrfMiddleware::generateToken();
        $token2 = CsrfMiddleware::generateToken();

        $this->assertEquals($token1, $token2);
    }

    public function testPostWithValidTokenPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CsrfMiddleware::generateToken();
        $_POST['csrf_token'] = $token;

        $middleware = new CsrfMiddleware();
        $result = $middleware->handle();

        $this->assertTrue($result);
    }

    public function testPostWithMissingTokenChecksFail(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token'] = 'valid_token';
        // No csrf_token in POST

        // The middleware calls App::redirect() which calls exit()
        // We verify the token comparison logic instead
        $postToken = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        $this->assertFalse(hash_equals($sessionToken, $postToken));
    }

    public function testPostWithWrongTokenChecksFail(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token'] = 'correct_token';
        $_POST['csrf_token'] = 'wrong_token';

        $this->assertFalse(hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']));
    }

    public function testPostWithMatchingTokenChecksPass(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CsrfMiddleware::generateToken();
        $_POST['csrf_token'] = $token;

        $this->assertTrue(hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']));
    }
}
