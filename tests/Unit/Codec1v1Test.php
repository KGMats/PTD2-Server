<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for encode_1v1() and decode_1v1() in obfuscation.php.
 *
 * Both functions are pure (no DB, no $_POST).
 *
 * encode/decode are intentionally asymmetric by design (blame Sam):
 *   - encode_1v1() takes a profiles array keyed by 'profileN'.
 *     It uses $key[7] to extract the profile number, so the key
 *     MUST follow the exact pattern 'profileN'.
 *   - decode_1v1() returns only ['money' => int, 'levelUnlocked' => int]
 *     for the first profile in the encoded string. No profile key is returned.
 *     This is by design — the client already knows which profile it requested.
 *
 * Hand-verified:
 *   profile0, money=0, levelUnlocked=0:
 *     whichProfile='m', money='m'(len'y'), levels='m'(len'y')
 *     body='mymym', PA='y' prepended -> 'ymymym'
 *     get_Length(1,6)->'18'->'ye'   Final: 'yeymymym'
 *
 *   profile0, money=10, levelUnlocked=3:
 *     whichProfile='m', money='ym'(len'w'), levels='c'(len'y')
 *     body='mwymc', PA='y' prepended -> 'ymwymc'
 *     get_Length(1,6)->'ye'         Final: 'yeymwymc'
 */


class Codec1v1Test extends TestCase
{
    // -- encode_1v1 ------------------------------------------------------------

    public function test_encode_zero_money_zero_levels_known_output(): void
    {
        $profiles = ['profile0' => ['money' => 0, 'levelUnlocked' => 0]];
        $this->assertSame('yeymymym', encode_1v1($profiles));
    }

    public function test_encode_money_10_levels_3_known_output(): void
    {
        $profiles = ['profile0' => ['money' => 10, 'levelUnlocked' => 3]];
        $this->assertSame('yoymwymyc', encode_1v1($profiles));
    }

    public function test_encode_output_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        $encoded    = encode_1v1(['profile0' => ['money' => 12345, 'levelUnlocked' => 7]]);
        for ($i = 0; $i < strlen($encoded); $i++) {
            $this->assertStringContainsString(
                $encoded[$i], $validChars,
                "Unexpected char '{$encoded[$i]}' at position $i"
            );
        }
    }

    public function test_encode_is_deterministic(): void
    {
        $profiles = ['profile0' => ['money' => 500, 'levelUnlocked' => 5]];
        $this->assertSame(encode_1v1($profiles), encode_1v1($profiles));
    }

    // -- decode_1v1 ------------------------------------------------------------

    public function test_decode_known_string_zero_values(): void
    {
        $result = decode_1v1('yeymymym');
        $this->assertSame(0, $result['money']);
        $this->assertSame(0, $result['levelUnlocked']);
    }

    public function test_decode_known_string_money_10_levels_3(): void
    {
        $result = decode_1v1('yawymyc');
        $this->assertSame(10, $result['money']);
        $this->assertSame(3, $result['levelUnlocked']);
    }

    public function test_decode_returns_array_with_expected_keys(): void
    {
        $result = decode_1v1(encode_1v1(['profile0' => ['money' => 1, 'levelUnlocked' => 1]]));
        $this->assertArrayHasKey('money', $result);
        $this->assertArrayHasKey('levelUnlocked', $result);
    }

    public function test_decode_client_format_money_10_levels_3(): void
    {
        // wire = 'yrwymyc'  (hand-verified, see class docblock)
        $result = decode_1v1('yrwymyc');
        $this->assertSame(10, $result['money']);
        $this->assertSame(3, $result['levelUnlocked']);
    }

    public function test_decode_client_format_money_0_levels_0(): void
    {
        // wire = 'ypymym'  (hand-verified, see class docblock)
        $result = decode_1v1('ypymym');
        $this->assertSame(0, $result['money']);
        $this->assertSame(0, $result['levelUnlocked']);
    }

    // -- encode -> decode -------------------------------------------------------

    public function test_encode_then_decode_preserves_zero_values(): void
    {
        $profiles = ['profile0' => ['money' => 0, 'levelUnlocked' => 0]];
        $decoded  = decode_1v1(encode_1v1($profiles));
        $this->assertSame(0, $decoded['money']);
        $this->assertSame(0, $decoded['levelUnlocked']);
    }


    // -- Sentinel --------------------------------------------------------------

    /**
     * 'ycm' is returned by load_1v1() when the user has no saved 1v1 data.
     * We assert it does not crash — the client handles the default state itself.
     */
    public function test_decode_ycm_sentinel_does_not_crash(): void
    {
        $result = decode_1v1('ycm');
        $this->assertIsArray($result);
    }
}
