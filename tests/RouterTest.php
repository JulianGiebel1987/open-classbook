<?php

namespace OpenClassbook\Tests;

use OpenClassbook\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testMatchRouteExtractsParameters(): void
    {
        // Use reflection to test private matchRoute method
        $reflection = new \ReflectionMethod(Router::class, 'matchRoute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->router, '/users/{id}', '/users/42');
        $this->assertIsArray($result);
        $this->assertEquals('42', $result['id']);
    }

    public function testMatchRouteNoMatch(): void
    {
        $reflection = new \ReflectionMethod(Router::class, 'matchRoute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->router, '/users/{id}', '/classes/42');
        $this->assertFalse($result);
    }

    public function testMatchRouteExactPath(): void
    {
        $reflection = new \ReflectionMethod(Router::class, 'matchRoute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->router, '/dashboard', '/dashboard');
        $this->assertIsArray($result);
    }

    public function testMatchRouteMultipleParameters(): void
    {
        $reflection = new \ReflectionMethod(Router::class, 'matchRoute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->router, '/classes/{classId}/students/{studentId}', '/classes/5/students/12');
        $this->assertIsArray($result);
        $this->assertEquals('5', $result['classId']);
        $this->assertEquals('12', $result['studentId']);
    }

    public function testGetRouteRegistration(): void
    {
        $this->router->get('/test', ['TestController', 'index']);

        // Verify route was registered via reflection
        $reflection = new \ReflectionProperty(Router::class, 'routes');
        $reflection->setAccessible(true);
        $routes = $reflection->getValue($this->router);

        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['path']);
    }

    public function testPostRouteRegistration(): void
    {
        $this->router->post('/test', ['TestController', 'store'], ['SomeMiddleware']);

        $reflection = new \ReflectionProperty(Router::class, 'routes');
        $reflection->setAccessible(true);
        $routes = $reflection->getValue($this->router);

        $this->assertCount(1, $routes);
        $this->assertEquals('POST', $routes[0]['method']);
        $this->assertEquals(['SomeMiddleware'], $routes[0]['middleware']);
    }

    public function testMultipleRoutesRegistered(): void
    {
        $this->router->get('/a', ['C', 'a']);
        $this->router->post('/b', ['C', 'b']);
        $this->router->get('/c', ['C', 'c']);

        $reflection = new \ReflectionProperty(Router::class, 'routes');
        $reflection->setAccessible(true);
        $routes = $reflection->getValue($this->router);

        $this->assertCount(3, $routes);
    }
}
