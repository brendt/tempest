<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Http;

use Tempest\Router\Session\Session;
use function Tempest\uri;
use Tests\Tempest\Fixtures\Controllers\ValidationController;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 * @small
 */
final class ValidationResponseTest extends FrameworkIntegrationTestCase
{
    public function test_validation_errors_are_listed_in_the_response_body(): void
    {
        $this->http
            ->post(uri([ValidationController::class, 'store']), ['number' => 11, 'item.number' => 11])
            ->assertRedirect(uri([ValidationController::class, 'store']))
            ->assertHasValidationError('number');
    }

    public function test_original_values(): void
    {
        $values = ['number' => 11, 'item.number' => 11];

        $this->http
            ->post(uri([ValidationController::class, 'store']), $values)
            ->assertRedirect(uri([ValidationController::class, 'store']))
            ->assertHasValidationError('number')
            ->assertHasSession(Session::ORIGINAL_VALUES, function (Session $session, array $data) use ($values): void {
                $this->assertEquals($values, $data);
            });
    }
}
