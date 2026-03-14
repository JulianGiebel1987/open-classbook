<?php

namespace OpenClassbook\Tests;

use OpenClassbook\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    protected function setUp(): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCsrfFieldGeneratesHiddenInput(): void
    {
        $_SESSION['csrf_token'] = 'test-token-123';

        $html = View::csrfField();

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
        $this->assertStringContainsString('value="test-token-123"', $html);
    }

    public function testCsrfFieldEscapesToken(): void
    {
        $_SESSION['csrf_token'] = '<script>alert("xss")</script>';

        $html = View::csrfField();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testFlashReturnsMessageAndClearsSession(): void
    {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Gespeichert!',
        ];

        $html = View::flash();

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Gespeichert!', $html);
        $this->assertArrayNotHasKey('flash', $_SESSION);
    }

    public function testFlashReturnsEmptyWhenNoFlash(): void
    {
        $html = View::flash();
        $this->assertEquals('', $html);
    }

    public function testFlashEscapesMessage(): void
    {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => '<b>XSS</b>',
        ];

        $html = View::flash();

        $this->assertStringNotContainsString('<b>XSS</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;XSS&lt;/b&gt;', $html);
    }

    public function testBreadcrumbsIncludesDashboard(): void
    {
        $crumbs = View::breadcrumbs([
            ['label' => 'Klassen', 'url' => '/classes'],
        ]);

        $this->assertCount(2, $crumbs);
        $this->assertEquals('Dashboard', $crumbs[0]['label']);
        $this->assertEquals('/dashboard', $crumbs[0]['url']);
        $this->assertEquals('Klassen', $crumbs[1]['label']);
    }

    public function testBreadcrumbsEmpty(): void
    {
        $crumbs = View::breadcrumbs([]);
        $this->assertCount(1, $crumbs);
        $this->assertEquals('Dashboard', $crumbs[0]['label']);
    }

    public function testFlashHasDismissButton(): void
    {
        $_SESSION['flash'] = [
            'type' => 'info',
            'message' => 'Info',
        ];

        $html = View::flash();
        $this->assertStringContainsString('alert-dismiss', $html);
        $this->assertStringContainsString('aria-label', $html);
    }
}
