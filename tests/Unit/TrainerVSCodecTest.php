<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for TrainerVS codec functions in obfuscation.php.
 *
 * Functions under test:
 *   encode_trainervs_profile($profile): string
 *   decode_trainervs_pokeinfo(string $encoded_data): array
 *   decode_trainervs_misc(string $encoded_data): array
 *
 * ── Asymmetry note ────────────────────────────────────────────────────────────
 * encode_trainervs_profile (server→client) and decode_trainervs_pokeinfo
 * (client→server) operate on DIFFERENT wire formats — no roundtrip is possible.
 *
 * encode_trainervs_profile encodes per-poke:
 *   num, lvl, gender, extra, move1-4, ability, 'yy', item
 * then appends: wins, avatar, trainerID
 *
 * decode_trainervs_pokeinfo reads per-poke:
 *   num, lvl, move1-4, gender, item, extra, ability, skip 2
 * (client sends a different field order — no redundant wins/avatar/ID)
 *
 * decode_trainervs_misc reads: header, wins_len_len+wins, loses_len_len+loses, avatar
 * (separate client message for match result data)
 *
 * Fixtures for decode_* are built programmatically using primitive codec
 * functions to guarantee correctness by construction.
 */
class TrainerVSCodecTest extends TestCase
{
    // ── Fixtures ──────────────────────────────────────────────────────────────

    private function minimalPoke(array $overrides = []): array
    {
        return array_merge([
            'num' => 1, 'lvl' => 5,
            'move1' => 10, 'move2' => 20, 'move3' => 30, 'move4' => 40,
            'gender' => 1, 'extra' => 0, 'item' => 5,
            'selected_ability' => 0,
        ], $overrides);
    }

    private function minimalProfile(array $overrides = []): array
    {
        return array_merge([
            'poke'   => [$this->minimalPoke(), $this->minimalPoke(['num' => 4]), $this->minimalPoke(['num' => 7])],
            'wins'   => 3,
            'avatar' => 1,
            'ID'     => 42,
        ], $overrides);
    }

    // ── Wire builders for decode_* functions ──────────────────────────────────

    private function encodeWithLen(int $value): string
    {
        $e = convertIntToString($value);
        return convertIntToString(strlen($e)) . $e;
    }

    private function encodeWithLenLen(int $value): string
    {
        $e   = convertIntToString($value);
        $len = convertIntToString(strlen($e));
        return convertIntToString(strlen($len)) . $len . $e;
    }

    /**
     * Build the client wire format for decode_trainervs_pokeinfo.
     * 3 pokes, each: num, lvl, move1-4, gender, item, extra, ability, 'yy'
     */
    private function buildPokeInfoWire(array $pokes): string
    {
        $body = '';
        foreach ($pokes as $poke) {
            $body .= $this->encodeWithLen($poke['num']);
            $body .= $this->encodeWithLen($poke['lvl']);
            $body .= $this->encodeWithLen($poke['move1']);
            $body .= $this->encodeWithLen($poke['move2']);
            $body .= $this->encodeWithLen($poke['move3']);
            $body .= $this->encodeWithLen($poke['move4']);
            $body .= $this->encodeWithLen($poke['gender']);
            $body .= $this->encodeWithLen($poke['item']);
            $body .= $this->encodeWithLen($poke['extra']);
            $body .= $this->encodeWithLen($poke['selected_ability']);
            $body .= 'yy'; // hardcoded separator in both encode and decode
        }
        $bodyLen    = strlen($body);
        $bodyLenLen = strlen((string)$bodyLen);
        $header     = convertIntToString((int) get_Length($bodyLenLen, $bodyLen));
        return $header . $body;
    }

    /**
     * Build client wire format for decode_trainervs_misc.
     * Format: header + wins_len_len+wins_len+wins + loses_len_len+loses_len+loses + avatar_len+avatar
     */
    private function buildMiscWire(int $wins, int $loses, int $avatar): string
    {
        $body  = $this->encodeWithLenLen($wins);
        $body .= $this->encodeWithLenLen($loses);
        $body .= $this->encodeWithLen($avatar);

        $bodyLen    = strlen($body);
        $bodyLenLen = strlen((string)$bodyLen);
        $header     = convertIntToString((int) get_Length($bodyLenLen, $bodyLen));
        return $header . $body;
    }

    // ── encode_trainervs_profile ──────────────────────────────────────────────

    public function test_encode_returns_non_empty_string(): void
    {
        $this->assertNotEmpty(encode_trainervs_profile($this->minimalProfile()));
    }

    public function test_encode_output_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        $encoded    = encode_trainervs_profile($this->minimalProfile());
        for ($i = 0; $i < strlen($encoded); $i++) {
            $this->assertStringContainsString(
                $encoded[$i], $validChars,
                "Unexpected char '{$encoded[$i]}' at position $i"
            );
        }
    }

    public function test_encode_is_deterministic(): void
    {
        $profile = $this->minimalProfile();
        $this->assertSame(
            encode_trainervs_profile($profile),
            encode_trainervs_profile($profile)
        );
    }

    public function test_encode_different_wins_produces_different_output(): void
    {
        $p1 = $this->minimalProfile(['wins' => 0]);
        $p2 = $this->minimalProfile(['wins' => 10]);
        $this->assertNotSame(
            encode_trainervs_profile($p1),
            encode_trainervs_profile($p2)
        );
    }

    public function test_encode_different_poke_num_produces_different_output(): void
    {
        $p1 = $this->minimalProfile();
        $p2 = $this->minimalProfile(['poke' => [
            $this->minimalPoke(['num' => 99]),
            $this->minimalPoke(['num' => 99]),
            $this->minimalPoke(['num' => 99]),
        ]]);
        $this->assertNotSame(
            encode_trainervs_profile($p1),
            encode_trainervs_profile($p2)
        );
    }

    // ── decode_trainervs_pokeinfo ─────────────────────────────────────────────

    public function test_decode_pokeinfo_returns_array_of_3(): void
    {
        $pokes  = [$this->minimalPoke(), $this->minimalPoke(), $this->minimalPoke()];
        $wire   = $this->buildPokeInfoWire($pokes);
        $result = decode_trainervs_pokeinfo($wire);
        $this->assertCount(3, $result);
    }

    public function test_decode_pokeinfo_num_decoded_correctly(): void
    {
        $pokes  = [
            $this->minimalPoke(['num' => 25]),
            $this->minimalPoke(['num' => 4]),
            $this->minimalPoke(['num' => 7]),
        ];
        $wire   = $this->buildPokeInfoWire($pokes);
        $result = decode_trainervs_pokeinfo($wire);
        $this->assertSame(25, $result[0]['num']);
        $this->assertSame(4,  $result[1]['num']);
        $this->assertSame(7,  $result[2]['num']);
    }

    public function test_decode_pokeinfo_lvl_decoded_correctly(): void
    {
        $pokes  = [
            $this->minimalPoke(['lvl' => 50]),
            $this->minimalPoke(['lvl' => 30]),
            $this->minimalPoke(['lvl' => 10]),
        ];
        $wire   = $this->buildPokeInfoWire($pokes);
        $result = decode_trainervs_pokeinfo($wire);
        $this->assertSame(50, $result[0]['lvl']);
        $this->assertSame(30, $result[1]['lvl']);
        $this->assertSame(10, $result[2]['lvl']);
    }

    public function test_decode_pokeinfo_all_moves_decoded_correctly(): void
    {
        $poke   = $this->minimalPoke(['move1' => 11, 'move2' => 22, 'move3' => 33, 'move4' => 44]);
        $wire   = $this->buildPokeInfoWire([$poke, $this->minimalPoke(), $this->minimalPoke()]);
        $result = decode_trainervs_pokeinfo($wire);
        $this->assertSame(11, $result[0]['move1']);
        $this->assertSame(22, $result[0]['move2']);
        $this->assertSame(33, $result[0]['move3']);
        $this->assertSame(44, $result[0]['move4']);
    }

    public function test_decode_pokeinfo_gender_item_extra_ability_decoded(): void
    {
        $poke   = $this->minimalPoke(['gender' => 1, 'item' => 7, 'extra' => 0, 'selected_ability' => 2]);
        $wire   = $this->buildPokeInfoWire([$poke, $this->minimalPoke(), $this->minimalPoke()]);
        $result = decode_trainervs_pokeinfo($wire);
        $this->assertSame(1, $result[0]['gender']);
        $this->assertSame(7, $result[0]['item']);
        $this->assertSame(0, $result[0]['extra']);
        $this->assertSame(2, $result[0]['selected_ability']);
    }

    public function test_decode_pokeinfo_result_has_expected_keys(): void
    {
        $wire   = $this->buildPokeInfoWire([$this->minimalPoke(), $this->minimalPoke(), $this->minimalPoke()]);
        $result = decode_trainervs_pokeinfo($wire);
        $keys   = ['num', 'lvl', 'move1', 'move2', 'move3', 'move4', 'gender', 'item', 'extra', 'selected_ability'];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $result[0], "Missing key '$key' in decoded poke");
        }
    }

    // ── decode_trainervs_misc ─────────────────────────────────────────────────

    public function test_decode_misc_wins_decoded_correctly(): void
    {
        $wire   = $this->buildMiscWire(15, 3, 2);
        $result = decode_trainervs_misc($wire);
        $this->assertSame(15, $result['wins']);
    }

    public function test_decode_misc_loses_decoded_correctly(): void
    {
        $wire   = $this->buildMiscWire(5, 20, 1);
        $result = decode_trainervs_misc($wire);
        $this->assertSame(20, $result['loses']);
    }

    public function test_decode_misc_avatar_decoded_correctly(): void
    {
        $wire   = $this->buildMiscWire(0, 0, 3);
        $result = decode_trainervs_misc($wire);
        $this->assertSame(3, $result['avatar']);
    }

    public function test_decode_misc_returns_array_with_expected_keys(): void
    {
        $wire   = $this->buildMiscWire(1, 2, 0);
        $result = decode_trainervs_misc($wire);
        $this->assertArrayHasKey('wins',   $result);
        $this->assertArrayHasKey('loses',  $result);
        $this->assertArrayHasKey('avatar', $result);
    }

    public function test_decode_misc_zero_values(): void
    {
        $wire   = $this->buildMiscWire(0, 0, 0);
        $result = decode_trainervs_misc($wire);
        $this->assertSame(0, $result['wins']);
        $this->assertSame(0, $result['loses']);
        $this->assertSame(0, $result['avatar']);
    }

    public function test_decode_misc_large_values(): void
    {
        $wire   = $this->buildMiscWire(999, 500, 9);
        $result = decode_trainervs_misc($wire);
        $this->assertSame(999, $result['wins']);
        $this->assertSame(500, $result['loses']);
        $this->assertSame(9,   $result['avatar']);
    }
}
