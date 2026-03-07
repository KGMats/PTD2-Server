<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for encode_story() and encode_story_profile() in obfuscation.php.
 *
 * -- encode_story(array $story_data): string -----------------------------------
 * Pure function. Iterates profiles 1-3. Encodes Money + badge count per profile.
 * NOTE: PA appears TWICE in output by design:
 *   $encoded_data = $encoded_len . $PA . $encoded_data
 *   where $encoded_data already has $PA appended at the end of the loop.
 *
 * Hand-verified (profile1, Money=0, extra=[]):
 *   badges=0, whichProfile='y', money='m'(len'y'), badges='m'(len'y')
 *   loop body: 'y'+'y'+'m'+'y'+'m' = 'yymym'
 *   PA='y', encoded_data='yymym'+'y'='yymymy'(6)
 *   get_Length(1,6): 1+6+1=8 → 'ye'
 *   FINAL: 'ye'+'y'+'yymymy' = 'yeyyymymy'
 *
 * -- encode_story_profile(array $profile): array -------------------------------
 * Returns [$extra, $extra2, $extra3, $extra4, $extra5] where:
 *   [0] $extra  = encoded MapLoc+MapSpot (header + Map_len+Map + Spot_len+Spot)
 *   [1] $extra2 = encode_inventory($profile['extra'])
 *   [2] $extra3 = encode_pokemons($profile['poke'])  → [encoded_string, pokenicks_string]
 *   [3] $extra4 = encode_inventory($profile['items'])
 *   [4] $extra5 = convertIntToString(create_Check_Sum($extra3[0] . $profile['CurrentSave']))
 */
class StoryCodecTest extends TestCase
{
    // -- Fixtures --------------------------------------------------------------

    private function minimalPoke(array $overrides = []): array
    {
        return array_merge([
            'num' => 1, 'xp' => 0, 'lvl' => 1,
            'move1' => 0, 'move2' => 0, 'move3' => 0, 'move4' => 0,
            'targetingType' => 0, 'gender' => 0,
            'saveID' => 1, 'pos' => 0,
            'extra' => 0, 'item' => 0,
            'tag' => '', 'Nickname' => 'Test',
        ], $overrides);
    }

    private function minimalProfile(array $overrides = []): array
    {
        return array_merge([
            'MapLoc'      => 3,
            'MapSpot'     => 1,
            'CurrentSave' => '',
            'extra'       => [],
            'items'       => [],
            'poke'        => [$this->minimalPoke()],
        ], $overrides);
    }

    // -- encode_story ----------------------------------------------------------

    public function test_encode_story_single_profile1_zero_money_known_output(): void
    {
        // Hand-verified: 'yeyyymymy' (see class docblock)
        $story = ['profile1' => ['Money' => 0, 'extra' => []]];
        $this->assertSame('yeyyymymy', encode_story($story));
    }

    public function test_encode_story_output_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        $story      = ['profile1' => ['Money' => 100, 'extra' => [64 => 8]]];
        $encoded    = encode_story($story);
        for ($i = 0; $i < strlen($encoded); $i++) {
            $this->assertStringContainsString(
                $encoded[$i], $validChars,
                "Unexpected char '{$encoded[$i]}' at position $i"
            );
        }
    }

    public function test_encode_story_is_deterministic(): void
    {
        $story = ['profile1' => ['Money' => 50, 'extra' => []]];
        $this->assertSame(encode_story($story), encode_story($story));
    }

    public function test_encode_story_skips_missing_profiles(): void
    {
        // profile2 missing — only profile1 and profile3 encoded
        $story1 = ['profile1' => ['Money' => 0, 'extra' => []]];
        $story13 = [
            'profile1' => ['Money' => 0, 'extra' => []],
            'profile3' => ['Money' => 0, 'extra' => []],
        ];
        // They should differ (PA count differs)
        $this->assertNotSame(encode_story($story1), encode_story($story13));
    }

    public function test_encode_story_three_profiles_produce_longer_output(): void
    {
        $one   = ['profile1' => ['Money' => 0, 'extra' => []]];
        $three = [
            'profile1' => ['Money' => 0, 'extra' => []],
            'profile2' => ['Money' => 0, 'extra' => []],
            'profile3' => ['Money' => 0, 'extra' => []],
        ];
        $this->assertGreaterThan(strlen(encode_story($one)), strlen(encode_story($three)));
    }

    public function test_encode_story_profile_order_is_always_1_2_3(): void
    {
        // Profiles provided out-of-order — output must encode in order 1,2,3
        $inOrder  = ['profile1' => ['Money' => 5, 'extra' => []], 'profile2' => ['Money' => 0, 'extra' => []]];
        $reversed = ['profile2' => ['Money' => 0, 'extra' => []], 'profile1' => ['Money' => 5, 'extra' => []]];
        $this->assertSame(encode_story($inOrder), encode_story($reversed));
    }

    public function test_encode_story_uses_get_badges_for_badge_count(): void
    {
        // With extra[64]=12 → 8 badges vs no badges → output differs
        $noBadges  = ['profile1' => ['Money' => 0, 'extra' => []]];
        $allBadges = ['profile1' => ['Money' => 0, 'extra' => [64 => 12]]];
        $this->assertNotSame(encode_story($noBadges), encode_story($allBadges));
    }

    // -- encode_story_profile --------------------------------------------------

    public function test_encode_story_profile_returns_array_of_5_elements(): void
    {
        $result = encode_story_profile($this->minimalProfile());
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    public function test_encode_story_profile_extra_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        $result     = encode_story_profile($this->minimalProfile());
        $extra      = $result[0];
        for ($i = 0; $i < strlen($extra); $i++) {
            $this->assertStringContainsString(
                $extra[$i], $validChars,
                "Unexpected char '{$extra[$i]}' in extra at position $i"
            );
        }
    }

    public function test_encode_story_profile_extra2_matches_encode_inventory_of_extra(): void
    {
        $profile = $this->minimalProfile(['extra' => [5 => 3, 10 => 1]]);
        $result  = encode_story_profile($profile);
        $this->assertSame(encode_inventory($profile['extra']), $result[1]);
    }

    public function test_encode_story_profile_extra3_is_array_with_two_elements(): void
    {
        $result = encode_story_profile($this->minimalProfile());
        $this->assertIsArray($result[2]);
        $this->assertCount(2, $result[2]);
    }

    public function test_encode_story_profile_extra3_pokenicks_contains_nickname(): void
    {
        $profile = $this->minimalProfile(['poke' => [$this->minimalPoke(['Nickname' => 'Pikachu', 'pos' => 0])]]);
        $result  = encode_story_profile($profile);
        $this->assertStringContainsString('Pikachu', $result[2][1]);
    }

    public function test_encode_story_profile_extra4_matches_encode_inventory_of_items(): void
    {
        $profile = $this->minimalProfile(['items' => [3 => 2]]);
        $result  = encode_story_profile($profile);
        $this->assertSame(encode_inventory($profile['items']), $result[3]);
    }

    public function test_encode_story_profile_extra5_is_valid_encoded_integer(): void
    {
        $result  = encode_story_profile($this->minimalProfile());
        $extra5  = $result[4];
        // extra5 = convertIntToString(create_Check_Sum(...)) — must be alphabet-only
        $validChars = 'mywcqapreo';
        for ($i = 0; $i < strlen($extra5); $i++) {
            $this->assertStringContainsString($extra5[$i], $validChars);
        }
    }

    public function test_encode_story_profile_extra5_matches_checksum_of_pokes_and_currentsave(): void
    {
        $profile  = $this->minimalProfile(['CurrentSave' => 'abc123']);
        $result   = encode_story_profile($profile);
        $expected = convertIntToString(create_Check_Sum($result[2][0] . $profile['CurrentSave']));
        $this->assertSame($expected, $result[4]);
    }

    public function test_encode_story_profile_maploc_and_mapspot_encoded_in_extra(): void
    {
        $p3  = $this->minimalProfile(['MapLoc' => 3, 'MapSpot' => 1]);
        $p10 = $this->minimalProfile(['MapLoc' => 10, 'MapSpot' => 5]);
        $this->assertNotSame(
            encode_story_profile($p3)[0],
            encode_story_profile($p10)[0]
        );
    }

    public function test_encode_story_profile_is_deterministic(): void
    {
        $profile = $this->minimalProfile();
        $this->assertSame(
            encode_story_profile($profile),
            encode_story_profile($profile)
        );
    }
}
