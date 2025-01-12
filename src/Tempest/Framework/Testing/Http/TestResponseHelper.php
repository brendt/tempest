<?php

declare(strict_types=1);

namespace Tempest\Framework\Testing\Http;

use Closure;
use Generator;
use PHPUnit\Framework\Assert;
use Tempest\Http\Status;
use Tempest\Router\Cookie\CookieManager;
use Tempest\Router\Response;
use Tempest\Router\Session\Session;
use Tempest\View\View;
use Tempest\View\ViewRenderer;
use function Tempest\get;

final class TestResponseHelper
{
    public function __construct(private(set) Response $response)
    {
    }

    public Status $status {
        get => $this->response->status;
    }

    /** @var \Tempest\Router\Header[] */
    public array $headers {
        get => $this->response->headers;
    }

    public View|string|array|Generator|null $body {
        get => $this->response->body;
    }

    public function assertHasHeader(string $name): self
    {
        Assert::assertArrayHasKey(
            $name,
            $this->response->headers,
            sprintf('Failed to assert that response contains header [%s].', $name),
        );

        return $this;
    }

    public function assertHeaderContains(string $name, mixed $value): self
    {
        $this->assertHasHeader($name);

        $header = $this->response->getHeader($name);

        $headerString = var_export($header, true);

        Assert::assertContains(
            $value,
            $header->values,
            sprintf('Failed to assert that response header [%s] value contains %s. These header values were found: %s', $name, $value, $headerString),
        );

        return $this;
    }

    public function assertRedirect(?string $to = null): self
    {
        Assert::assertTrue(
            $this->status->isRedirect(),
            sprintf('Failed asserting that status [%s] is a redirect.', $this->status->value),
        );

        return $to === null
            ? $this->assertHasHeader('Location')
            : $this->assertHeaderContains('Location', $to);
    }

    public function assertOk(): self
    {
        return $this->assertStatus(Status::OK);
    }

    public function assertForbidden(): self
    {
        return $this->assertStatus(Status::FORBIDDEN);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(Status::NOT_FOUND);
    }

    public function assertStatus(Status $expected): self
    {
        Assert::assertSame(
            expected: $expected,
            actual: $this->status,
            message: sprintf(
                'Failed asserting status [%s] matched expected status of [%s].',
                $expected->value,
                $this->status->value,
            ),
        );

        return $this;
    }

    public function assertHasCookie(string $key, ?Closure $test = null): self
    {
        $cookies = get(CookieManager::class);

        $cookie = $cookies->get($key);

        Assert::assertNotNull($cookie);

        if ($test !== null) {
            $test($cookie);
        }

        return $this;
    }

    public function assertHasSession(string $key, ?Closure $test = null): self
    {
        /** @var Session $session */
        $session = get(Session::class);

        $data = $session->get($key);

        Assert::assertNotNull(
            $data,
            sprintf(
                'No session value was set for %s, available session keys: %s',
                $key,
                implode(', ', array_keys($session->data)),
            ),
        );

        if ($test !== null) {
            $test($session, $data);
        }

        return $this;
    }

    public function assertHasValidationError(string $key, ?Closure $test = null): self
    {
        /** @var Session $session */
        $session = get(Session::class);

        $validationErrors = $session->get(Session::VALIDATION_ERRORS);

        Assert::assertArrayHasKey(
            $key,
            $validationErrors,
            sprintf(
                'No validation error was set for %s, available validation errors: %s',
                $key,
                implode(', ', array_keys($validationErrors)),
            ),
        );

        if ($test !== null) {
            $test($validationErrors);
        }

        return $this;
    }

    public function assertHasNoValidationsErrors(): self
    {
        /** @var Session $session */
        $session = get(Session::class);

        $validationErrors = $session->get(Session::VALIDATION_ERRORS) ?? [];

        Assert::assertEmpty(
            $validationErrors,
            sprintf(
                'There should be no validation errors, but there were: %s',
                implode(', ', array_keys($validationErrors)),
            ),
        );

        return $this;
    }

    public function assertSee(string $search): self
    {
        $body = $this->body;

        if ($body instanceof View) {
            $body = get(ViewRenderer::class)->render($body);
        }

        Assert::assertStringContainsString($search, $body);

        return $this;
    }

    public function assertNotSee(string $search): self
    {
        $body = $this->body;

        if ($body instanceof View) {
            $body = get(ViewRenderer::class)->render($body);
        }

        Assert::assertStringNotContainsString($search, $body);

        return $this;
    }
}
