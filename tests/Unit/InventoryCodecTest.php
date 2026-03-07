<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for encode_inventory() and decode_inventory() in obfuscation.php.
 *
 * Both functions are pure (no DB, no $_POST).
 *
 * Expected behaviour:
 *   - Items with quantity <= 0 are silently skipped. This is intentional.
 *   - Roundtrip must preserve all items with qty > 0 exactly.
 *
 * Hand-verified encode of [3 => 2]:
 *   encoded_num='c', num_len='y', quantity='w', quantity_len='y'
 *   body='ycyw', count prefix: inventory_len='y', inventory_len_len='y' -> 'yyycyw'
 *   get_Length(1,6)->'18'->convertIntToString(18)='ye'
 *   Final: 'yeyyycyw'
 *
 * Hand-verified encode of []:
 *   count=0 -> 'm', len_len='y' -> 'ym'
 *   get_Length(1,2)->'14'->'yq'
 *   Final: 'yqym'
 */
class InventoryCodecTest extends TestCase
{
    // -- encode_inventory ------------------------------------------------------

    public function test_encode_single_item_produces_known_string(): void
    {
        $this->assertSame('yeyyycyw', encode_inventory([3 => 2]));
    }

    public function test_encode_empty_inventory_produces_known_string(): void
    {
        $this->assertSame('yqym', encode_inventory([]));
    }

    public function test_encode_skips_zero_quantity_items(): void
    {
        $this->assertSame(encode_inventory([]), encode_inventory([5 => 0]));
    }

    public function test_encode_skips_negative_quantity_items(): void
    {
        $this->assertSame(encode_inventory([]), encode_inventory([5 => -1]));
    }

    public function test_encode_output_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        $encoded    = encode_inventory([1 => 5, 7 => 3, 99 => 1]);
        for ($i = 0; $i < strlen($encoded); $i++) {
            $this->assertStringContainsString(
                $encoded[$i], $validChars,
                "Unexpected char '{$encoded[$i]}' at position $i in '$encoded'"
            );
        }
    }

    // -- decode_inventory ------------------------------------------------------

    public function test_decode_known_string_single_item(): void
    {
        $this->assertSame([3 => 2], decode_inventory('yeyyycyw'));
    }

    public function test_decode_empty_encoded_inventory(): void
    {
        $this->assertSame([], decode_inventory('yqym'));
    }

    // -- encode -> decode roundtrip ---------------------------------------------

    public function test_roundtrip_single_item(): void
    {
        $original = [3 => 2];
        $this->assertSame($original, decode_inventory(encode_inventory($original)));
    }

    public function test_roundtrip_multiple_items(): void
    {
        $original = [1 => 10, 5 => 3, 99 => 1];
        $this->assertSame($original, decode_inventory(encode_inventory($original)));
    }

    public function test_roundtrip_large_item_id_and_quantity(): void
    {
        $original = [255 => 999];
        $this->assertSame($original, decode_inventory(encode_inventory($original)));
    }

    public function test_roundtrip_drops_zero_quantity_items(): void
    {
        $input    = [1 => 5, 2 => 0, 3 => 7];
        $expected = [1 => 5, 3 => 7];
        $this->assertSame($expected, decode_inventory(encode_inventory($input)));
    }

    public function test_roundtrip_is_deterministic(): void
    {
        $original = [4 => 2, 8 => 1];
        $this->assertSame(encode_inventory($original), encode_inventory($original));
    }
}
