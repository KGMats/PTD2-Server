<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for get_badges(array $extra): int in obfuscation.php.
 *
 * Pure function, no DB, no $_POST.
 *
 * Source logic:
 *   $badges = 0
 *   if extra[48] === 2  -> $badges = 1
 *   if extra[59] === 2  -> $badges = 2
 *   if isset(extra[64]):
 *       >= 12 -> 8
 *       >= 11 -> 7
 *       >= 9  -> 5
 *       >= 7  -> 4
 *       >= 1  -> 3
 *   return $badges
 */
class GetBadgesTest extends TestCase
{
    public function test_returns_zero_for_empty_extra(): void
    {
        $this->assertSame(0, get_badges([]));
    }

    public function test_returns_zero_when_no_relevant_keys_set(): void
    {
        $this->assertSame(0, get_badges([1 => 5, 2 => 3]));
    }

    public function test_returns_1_when_extra_48_is_2(): void
    {
        $this->assertSame(1, get_badges([48 => 2]));
    }

    public function test_returns_0_when_extra_48_is_not_2(): void
    {
        $this->assertSame(0, get_badges([48 => 1]));
        $this->assertSame(0, get_badges([48 => 3]));
    }

    public function test_returns_2_when_extra_59_is_2(): void
    {
        $this->assertSame(2, get_badges([59 => 2]));
    }

    public function test_returns_2_when_both_48_and_59_are_2(): void
    {
        $this->assertSame(2, get_badges([48 => 2, 59 => 2]));
    }

    public function test_returns_3_when_extra_64_is_between_1_and_6(): void
    {
        foreach ([1, 3, 6] as $val) {
            $this->assertSame(3, get_badges([64 => $val]), "extra[64]=$val should give 3 badges");
        }
    }

    public function test_returns_4_when_extra_64_is_7_or_8(): void
    {
        foreach ([7, 8] as $val) {
            $this->assertSame(4, get_badges([64 => $val]), "extra[64]=$val should give 4 badges");
        }
    }

    public function test_returns_5_when_extra_64_is_9_or_10(): void
    {
        foreach ([9, 10] as $val) {
            $this->assertSame(5, get_badges([64 => $val]), "extra[64]=$val should give 5 badges");
        }
    }

    public function test_returns_7_when_extra_64_is_11(): void
    {
        $this->assertSame(7, get_badges([64 => 11]));
    }

    public function test_returns_8_when_extra_64_is_12_or_more(): void
    {
        foreach ([12, 15, 99] as $val) {
            $this->assertSame(8, get_badges([64 => $val]), "extra[64]=$val should give 8 badges");
        }
    }

    public function test_extra_64_overrides_earlier_badge_checks(): void
    {
        $this->assertSame(8, get_badges([48 => 2, 59 => 2, 64 => 12]));
        $this->assertSame(3, get_badges([48 => 2, 59 => 2, 64 => 1]));
    }

    public function test_returns_0_when_extra_64_is_0(): void
    {
        $this->assertSame(0, get_badges([64 => 0]));
    }
}
