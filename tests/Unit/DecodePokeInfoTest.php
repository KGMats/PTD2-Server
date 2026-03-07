<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for decode_pokeinfo(string $encoded_pokeinfo, int $firstAvailableSaveID): array
 *
 * decode_pokeinfo is now a pure codec — no DB, no $_POST, no credentials.
 * $firstAvailableSaveID is resolved by the caller and passed in directly.
 *
 * Wire format (sent by ActionScript client):
 *   header (get_Length style, 1-2 chars)
 *   pokes_count_len + pokes_count
 *   per poke:
 *     event_count_len + event_count
 *     saveID_len_len + saveID_len + saveID
 *     per event:
 *       type_len + type
 *       ... event-specific fields ...
 *
 * Test fixtures are built programmatically using the same primitive codec
 * functions (convertIntToString, get_Length etc.) so expected values are
 * correct by construction — no hand-trace arithmetic errors possible.
 *
 * Event types:
 *   1=Captured, 2=LevelUp, 3=XpUp, 4=ChangeMoves, 5=ChangeItem,
 *   6=Evolve, 7=ChangeNickname, 8=PosChange, 9=NeedTag, 10=NeedTrade
 */
class DecodePokeInfoTest extends TestCase
{
    // ── Wire-building helpers ─────────────────────────────────────────────────

    private function encodeWithLenLen(int $value): string
    {
        $encoded = convertIntToString($value);
        $len     = convertIntToString(strlen($encoded));
        $lenLen  = convertIntToString(strlen($len));
        return $lenLen . $len . $encoded;
    }

    private function encodeWithLen(int $value): string
    {
        $encoded = convertIntToString($value);
        $len     = convertIntToString(strlen($encoded));
        return $len . $encoded;
    }

    private function encodeStrWithLen(string $value): string
    {
        return convertIntToString(strlen($value)) . $value;
    }

    /**
     * Build a complete valid wire string.
     * Each poke: ['saveID' => int, 'events' => [['type' => int, ...fields], ...]]
     */
    private function buildWire(array $pokes): string
    {
        $body = '';

        $count    = convertIntToString(count($pokes));
        $countLen = convertIntToString(strlen($count));
        $body    .= $countLen . $count;

        foreach ($pokes as $poke) {
            $pokePart = '';
            $pokePart .= $this->encodeWithLenLen($poke['saveID']);

            foreach ($poke['events'] as $event) {
                $type    = convertIntToString($event['type']);
                $typeLen = convertIntToString(strlen($type));
                $pokePart .= $typeLen . $type;

                switch ($event['type']) {
                    case 1:
                        $pokePart .= $this->encodeWithLen($event['num']);
                        $pokePart .= $this->encodeWithLenLen($event['xp']);
                        $pokePart .= $this->encodeWithLen($event['lvl']);
                        $pokePart .= $this->encodeWithLen($event['move1']);
                        $pokePart .= $this->encodeWithLen($event['move2']);
                        $pokePart .= $this->encodeWithLen($event['move3']);
                        $pokePart .= $this->encodeWithLen($event['move4']);
                        $pokePart .= $this->encodeWithLen($event['targetingType']);
                        $pokePart .= $this->encodeWithLen($event['gender']);
                        $pokePart .= $this->encodeWithLen($event['pos']);
                        $pokePart .= $this->encodeWithLen($event['extra']);
                        $pokePart .= $this->encodeWithLen($event['item']);
                        $pokePart .= $this->encodeStrWithLen($event['tag']);
                        break;
                    case 2:
                        $pokePart .= $this->encodeWithLen($event['lvl']);
                        break;
                    case 3:
                        $pokePart .= $this->encodeWithLenLen($event['xp']);
                        break;
                    case 4:
                        $pokePart .= $this->encodeWithLen($event['move1']);
                        $pokePart .= $this->encodeWithLen($event['move2']);
                        $pokePart .= $this->encodeWithLen($event['move3']);
                        $pokePart .= $this->encodeWithLen($event['move4']);
                        break;
                    case 5:
                        $pokePart .= $this->encodeWithLen($event['item']);
                        break;
                    case 6:
                        $pokePart .= $this->encodeWithLen($event['num']);
                        break;
                    case 7:
                        // no fields
                        break;
                    case 8:
                        $pokePart .= $this->encodeWithLen($event['pos']);
                        break;
                    case 9:
                        $pokePart .= $this->encodeStrWithLen($event['tag']);
                        break;
                    case 10:
                        $pokePart .= $this->encodeWithLen($event['num']);
                        break;
                }
            }

            $evCount    = convertIntToString(count($poke['events']));
            $evCountLen = convertIntToString(strlen($evCount));
            $body      .= $evCountLen . $evCount . $pokePart;
        }

        $bodyLen    = strlen($body);
        $bodyLenLen = strlen((string)$bodyLen);
        $header     = convertIntToString((int) get_Length($bodyLenLen, $bodyLen));
        return $header . $body;
    }

    /** Minimal type-1 event array for convenience. */
    private function captureEvent(array $overrides = []): array
    {
        return array_merge([
            'type' => 1, 'num' => 1, 'xp' => 0, 'lvl' => 1,
            'move1' => 0, 'move2' => 0, 'move3' => 0, 'move4' => 0,
            'targetingType' => 0, 'gender' => 0, 'pos' => 0,
            'extra' => 0, 'item' => 0, 'tag' => '',
        ], $overrides);
    }

    // ── Empty list ────────────────────────────────────────────────────────────

    public function test_empty_list_returns_empty_array(): void
    {
        $this->assertSame([], decode_pokeinfo($this->buildWire([]), 1));
    }

    // ── Type 1: Captured ──────────────────────────────────────────────────────

    public function test_captured_uses_firstAvailableSaveID_as_key_and_saveID(): void
    {
        $wire   = $this->buildWire([['saveID' => 0, 'events' => [$this->captureEvent()]]]);
        $result = decode_pokeinfo($wire, 42);
        $this->assertArrayHasKey(42, $result);
        $this->assertSame(42, $result[42]['saveID']);
    }

    public function test_captured_fields_decoded_correctly(): void
    {
        $event = $this->captureEvent([
            'num' => 25, 'xp' => 1000, 'lvl' => 15,
            'move1' => 10, 'move2' => 20, 'move3' => 30, 'move4' => 40,
            'targetingType' => 1, 'gender' => 1, 'pos' => 2, 'item' => 5, 'tag' => 'ab',
        ]);
        $wire   = $this->buildWire([['saveID' => 0, 'events' => [$event]]]);
        $result = decode_pokeinfo($wire, 1);
        $poke   = $result[1];

        $this->assertSame(25,   $poke['num']);
        $this->assertSame(1000, $poke['xp']);
        $this->assertSame(15,   $poke['lvl']);
        $this->assertSame(10,   $poke['move1']);
        $this->assertSame(20,   $poke['move2']);
        $this->assertSame(30,   $poke['move3']);
        $this->assertSame(40,   $poke['move4']);
        $this->assertSame(1,    $poke['targetingType']);
        $this->assertSame(1,    $poke['gender']);
        $this->assertSame(2,    $poke['pos']);
        $this->assertSame(5,    $poke['item']);
        $this->assertSame('ab', $poke['tag']);
    }

    public function test_captured_sets_needNickname_to_poke_index_plus_one(): void
    {
        $wire   = $this->buildWire([['saveID' => 0, 'events' => [$this->captureEvent()]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(1, $result[1]['needNickname']);
    }

    public function test_captured_sets_needSaveID_equal_to_pos(): void
    {
        $wire   = $this->buildWire([['saveID' => 0, 'events' => [$this->captureEvent(['pos' => 3])]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(3, $result[1]['needSaveID']);
    }

    public function test_captured_reason_contains_1(): void
    {
        $wire   = $this->buildWire([['saveID' => 0, 'events' => [$this->captureEvent()]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertContains(1, $result[1]['reason']);
    }

    public function test_multiple_captures_increment_saveID(): void
    {
        $wire = $this->buildWire([
            ['saveID' => 0, 'events' => [$this->captureEvent(['pos' => 0])]],
            ['saveID' => 0, 'events' => [$this->captureEvent(['pos' => 1])]],
        ]);
        $result = decode_pokeinfo($wire, 10);
        $this->assertArrayHasKey(10, $result);
        $this->assertArrayHasKey(11, $result);
        $this->assertSame(10, $result[10]['saveID']);
        $this->assertSame(11, $result[11]['saveID']);
    }

    // ── Type 2: Level up ──────────────────────────────────────────────────────

    public function test_level_up_decodes_lvl(): void
    {
        $wire   = $this->buildWire([['saveID' => 5, 'events' => [['type' => 2, 'lvl' => 20]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(20, $result[5]['lvl']);
        $this->assertContains(2, $result[5]['reason']);
    }

    // ── Type 3: XP up ─────────────────────────────────────────────────────────

    public function test_xp_up_decodes_xp(): void
    {
        $wire   = $this->buildWire([['saveID' => 7, 'events' => [['type' => 3, 'xp' => 5000]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(5000, $result[7]['xp']);
        $this->assertContains(3, $result[7]['reason']);
    }

    // ── Type 4: Change moves ──────────────────────────────────────────────────

    public function test_change_moves_decodes_all_four(): void
    {
        $wire   = $this->buildWire([['saveID' => 3, 'events' => [['type' => 4, 'move1' => 11, 'move2' => 22, 'move3' => 33, 'move4' => 44]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(11, $result[3]['move1']);
        $this->assertSame(22, $result[3]['move2']);
        $this->assertSame(33, $result[3]['move3']);
        $this->assertSame(44, $result[3]['move4']);
        $this->assertContains(4, $result[3]['reason']);
    }

    // ── Type 5: Change item ───────────────────────────────────────────────────

    public function test_change_item_decodes_item(): void
    {
        $wire   = $this->buildWire([['saveID' => 2, 'events' => [['type' => 5, 'item' => 7]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(7, $result[2]['item']);
        $this->assertContains(5, $result[2]['reason']);
    }

    // ── Type 6: Evolve ────────────────────────────────────────────────────────

    public function test_evolve_decodes_num(): void
    {
        $wire   = $this->buildWire([['saveID' => 4, 'events' => [['type' => 6, 'num' => 6]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(6, $result[4]['num']);
        $this->assertContains(6, $result[4]['reason']);
    }

    // ── Type 7: Change nickname ───────────────────────────────────────────────

    public function test_change_nickname_sets_needNickname(): void
    {
        $wire   = $this->buildWire([['saveID' => 9, 'events' => [['type' => 7]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertArrayHasKey('needNickname', $result[9]);
        $this->assertContains(7, $result[9]['reason']);
    }

    // ── Type 8: Pos change ────────────────────────────────────────────────────

    public function test_pos_change_decodes_pos(): void
    {
        $wire   = $this->buildWire([['saveID' => 6, 'events' => [['type' => 8, 'pos' => 4]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(4, $result[6]['pos']);
        $this->assertContains(8, $result[6]['reason']);
    }

    // ── Type 9: Need tag ──────────────────────────────────────────────────────

    public function test_need_tag_decodes_tag(): void
    {
        $wire   = $this->buildWire([['saveID' => 8, 'events' => [['type' => 9, 'tag' => 'xy']]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame('xy', $result[8]['tag']);
        $this->assertContains(9, $result[8]['reason']);
    }

    // ── Type 10: Need trade ───────────────────────────────────────────────────

    public function test_need_trade_decodes_num(): void
    {
        $wire   = $this->buildWire([['saveID' => 11, 'events' => [['type' => 10, 'num' => 99]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertSame(99, $result[11]['num']);
        $this->assertContains(10, $result[11]['reason']);
    }

    // ── Structural ────────────────────────────────────────────────────────────

    public function test_result_is_keyed_by_saveID(): void
    {
        $wire   = $this->buildWire([['saveID' => 99, 'events' => [['type' => 2, 'lvl' => 5]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertArrayHasKey(99, $result);
    }

    public function test_result_always_has_reason_array(): void
    {
        $wire   = $this->buildWire([['saveID' => 1, 'events' => [['type' => 2, 'lvl' => 1]]]]);
        $result = decode_pokeinfo($wire, 1);
        $this->assertIsArray($result[1]['reason']);
    }
}
