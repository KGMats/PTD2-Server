<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for encode_pokemons($pokemons): [string, string] in obfuscation.php.
 *
 * Returns [$encoded_string, $pokenicks_string].
 *
 * Key behaviours under test:
 *
 * 1. Normal encoding — output is alphabet-only, deterministic, pokenicks
 *    contains each pokemon's nickname keyed by (pos+1).
 *
 * 2. Position ordering — $parts is keyed by $poke['pos'] then ksort()'d,
 *    so the encoded string always contains pokes in ascending pos order
 *    regardless of the input array order.
 *
 * 3. Collision resolution — when two pokes claim the same pos, the second
 *    one is reassigned to the first available slot (scanning 0,1,2,...).
 *    The reassignment affects both the encoded pos field and the pokenicks
 *    entry. Resolution is based on $parts as it's built (iteration order
 *    matters).
 */
class EncodePokemonsTest extends TestCase
{
    // ── Fixtures ──────────────────────────────────────────────────────────────

    private function poke(array $overrides = []): array
    {
        return array_merge([
            'num'           => 1,
            'xp'            => 0,
            'lvl'           => 1,
            'move1'         => 0,
            'move2'         => 0,
            'move3'         => 0,
            'move4'         => 0,
            'targetingType' => 0,
            'gender'        => 0,
            'saveID'        => 1,
            'pos'           => 0,
            'extra'         => 0,
            'item'          => 0,
            'tag'           => '',
            'Nickname'      => 'Test',
        ], $overrides);
    }

    // ── Return structure ──────────────────────────────────────────────────────

    public function test_returns_array_of_two_elements(): void
    {
        $result = encode_pokemons([$this->poke()]);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_first_element_is_string(): void
    {
        [$encoded] = encode_pokemons([$this->poke()]);
        $this->assertIsString($encoded);
    }

    public function test_second_element_is_string(): void
    {
        [, $nicks] = encode_pokemons([$this->poke()]);
        $this->assertIsString($nicks);
    }

    // ── Alphabet purity ───────────────────────────────────────────────────────

    public function test_encoded_string_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        [$encoded]  = encode_pokemons([$this->poke(['num' => 25, 'xp' => 1000, 'lvl' => 50])]);
        for ($i = 0; $i < strlen($encoded); $i++) {
            $this->assertStringContainsString(
                $encoded[$i], $validChars,
                "Unexpected char '{$encoded[$i]}' at position $i"
            );
        }
    }

    // ── Pokenicks ─────────────────────────────────────────────────────────────

    public function test_pokenicks_contains_nickname(): void
    {
        [, $nicks] = encode_pokemons([$this->poke(['Nickname' => 'Pikachu', 'pos' => 0])]);
        $this->assertStringContainsString('Pikachu', $nicks);
    }

    public function test_pokenicks_key_is_pos_plus_one(): void
    {
        // pos=0 → PN1, pos=2 → PN3
        [, $nicks] = encode_pokemons([$this->poke(['Nickname' => 'Bulba', 'pos' => 2])]);
        $this->assertStringContainsString('PN3=Bulba', $nicks);
    }

    public function test_pokenicks_contains_all_nicknames(): void
    {
        $pokes = [
            $this->poke(['Nickname' => 'Pika',  'pos' => 0, 'saveID' => 1]),
            $this->poke(['Nickname' => 'Bulba', 'pos' => 1, 'saveID' => 2]),
            $this->poke(['Nickname' => 'Squirt','pos' => 2, 'saveID' => 3]),
        ];
        [, $nicks] = encode_pokemons($pokes);
        $this->assertStringContainsString('Pika',   $nicks);
        $this->assertStringContainsString('Bulba',  $nicks);
        $this->assertStringContainsString('Squirt', $nicks);
    }

    // ── Determinism ───────────────────────────────────────────────────────────

    public function test_is_deterministic(): void
    {
        $pokes = [$this->poke(['num' => 4, 'xp' => 500])];
        $this->assertSame(encode_pokemons($pokes), encode_pokemons($pokes));
    }

    // ── Position ordering (ksort) ─────────────────────────────────────────────

    public function test_output_is_same_regardless_of_input_order(): void
    {
        $poke0 = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'A', 'num' => 1]);
        $poke1 = $this->poke(['pos' => 1, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);

        [$enc_forward] = encode_pokemons([$poke0, $poke1]);
        [$enc_reverse] = encode_pokemons([$poke1, $poke0]);

        $this->assertSame($enc_forward, $enc_reverse);
    }

    public function test_pokenicks_contains_correct_pos_keys_regardless_of_input_order(): void
    {
        $poke0 = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'First',  'num' => 1]);
        $poke1 = $this->poke(['pos' => 1, 'saveID' => 2, 'Nickname' => 'Second', 'num' => 4]);

        // Input in reverse order — pokenicks are built during iteration (input order),
        // so 'Second' appears before 'First' in the string. But the PN keys must
        // still match each poke's actual pos.
        [, $nicks] = encode_pokemons([$poke1, $poke0]);

        $this->assertStringContainsString('PN1=First',  $nicks);
        $this->assertStringContainsString('PN2=Second', $nicks);
        // Second was input first, so its nick entry comes first in the string
        $this->assertLessThan(strpos($nicks, 'PN1=First'), strpos($nicks, 'PN2=Second'));
    }

    // ── Collision resolution ──────────────────────────────────────────────────

    public function test_collision_second_poke_gets_reassigned_to_first_free_slot(): void
    {
        // Both pokes claim pos=0. Second should be reassigned to pos=1.
        $poke_a = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'Alpha', 'num' => 1]);
        $poke_b = $this->poke(['pos' => 0, 'saveID' => 2, 'Nickname' => 'Beta',  'num' => 4]);

        [$encoded, $nicks] = encode_pokemons([$poke_a, $poke_b]);

        // Both nicknames must appear
        $this->assertStringContainsString('Alpha', $nicks);
        $this->assertStringContainsString('Beta',  $nicks);

        // Alpha stays at pos=0 → PN1, Beta gets reassigned to pos=1 → PN2
        $this->assertStringContainsString('PN1=Alpha', $nicks);
        $this->assertStringContainsString('PN2=Beta',  $nicks);
    }

    public function test_collision_result_has_correct_poke_count_in_encoded_string(): void
    {
        // Regardless of collision, both pokes must be encoded
        $poke_a = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'A', 'num' => 1]);
        $poke_b = $this->poke(['pos' => 0, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);

        [$encoded] = encode_pokemons([$poke_a, $poke_b]);

        // The encoded string must be longer than a single-poke encoding
        [$single] = encode_pokemons([$poke_a]);
        $this->assertGreaterThan(strlen($single), strlen($encoded));
    }

    public function test_collision_reassigned_poke_differs_from_non_colliding_encoding(): void
    {
        // poke_b at pos=1 naturally vs poke_b reassigned from pos=0 to pos=1
        // The encoded pos field inside the string will differ if reassignment changed pos
        $poke_a       = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'A', 'num' => 1]);
        $poke_b_nat   = $this->poke(['pos' => 1, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);
        $poke_b_coll  = $this->poke(['pos' => 0, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);

        [$enc_natural]   = encode_pokemons([$poke_a, $poke_b_nat]);
        [$enc_collision] = encode_pokemons([$poke_a, $poke_b_coll]);

        // Both should produce the same encoding since poke_b ends up at pos=1 either way
        $this->assertSame($enc_natural, $enc_collision);
    }

    public function test_three_way_collision_all_get_distinct_slots(): void
    {
        // All three pokes claim pos=0
        // First → pos=0, second → pos=1, third → pos=2
        $poke_a = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'A', 'num' => 1]);
        $poke_b = $this->poke(['pos' => 0, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);
        $poke_c = $this->poke(['pos' => 0, 'saveID' => 3, 'Nickname' => 'C', 'num' => 7]);

        [$encoded, $nicks] = encode_pokemons([$poke_a, $poke_b, $poke_c]);

        $this->assertStringContainsString('PN1=A', $nicks);
        $this->assertStringContainsString('PN2=B', $nicks);
        $this->assertStringContainsString('PN3=C', $nicks);
    }

    public function test_collision_with_non_zero_base_pos(): void
    {
        // poke_a at pos=2, poke_b also at pos=2
        // poke_b should be reassigned to pos=0 (first free slot)
        $poke_a = $this->poke(['pos' => 2, 'saveID' => 1, 'Nickname' => 'A', 'num' => 1]);
        $poke_b = $this->poke(['pos' => 2, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);

        [, $nicks] = encode_pokemons([$poke_a, $poke_b]);

        $this->assertStringContainsString('PN3=A', $nicks); // pos=2 → PN3
        $this->assertStringContainsString('PN1=B', $nicks); // reassigned to pos=0 → PN1
    }

    public function test_no_collision_encoding_is_unaffected(): void
    {
        // Sanity check — distinct positions produce same result with or without collision logic
        $poke_a = $this->poke(['pos' => 0, 'saveID' => 1, 'Nickname' => 'A', 'num' => 1]);
        $poke_b = $this->poke(['pos' => 1, 'saveID' => 2, 'Nickname' => 'B', 'num' => 4]);

        [$enc1] = encode_pokemons([$poke_a, $poke_b]);
        [$enc2] = encode_pokemons([$poke_a, $poke_b]);

        $this->assertSame($enc1, $enc2);
        $this->assertNotEmpty($enc1);
    }
}
