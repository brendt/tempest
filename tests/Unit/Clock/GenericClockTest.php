<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\Clock;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tempest\Clock\GenericClock;

/**
 * @internal
 * @small
 */
final class GenericClockTest extends TestCase
{
    public function test_that_generic_clock_returns_the_current_date_time(): void
    {
        $clock = new GenericClock();

        // It's a little tough to test "real" time without mocking stuff.
        // Because of this, we are just going to get the date/time before and after
        // getting the date/time from the clock and make sure it all checks out.
        $dateTimeBefore = new DateTimeImmutable('now');
        $clockDateTime = $clock->now();
        $dateTimeAfter = new DateTimeImmutable('now');

        $this->assertGreaterThan($dateTimeBefore, $clockDateTime);
        $this->assertLessThan($dateTimeAfter, $clockDateTime);
    }

    public function test_that_generic_clock_returns_the_current_time(): void
    {
        $clock = new GenericClock();

        // It's a little tough to test "real" time without mocking stuff.
        // Because of this, we are just going to get the time before and after
        // getting the time from the clock and make sure it all checks out.
        $timeBefore = hrtime(true);
        $clockTime = $clock->time();
        $timeAfter = hrtime(true);

        $this->assertGreaterThan($timeBefore, $clockTime);
        $this->assertLessThan($timeAfter, $clockTime);
    }

    public function test_that_generic_clock_sleeps(): void
    {
        $timeBefore = time();

        (new GenericClock())->sleep(1);

        $timeAfter = time();

        $this->assertEquals($timeAfter - 1, $timeBefore);
    }
}
