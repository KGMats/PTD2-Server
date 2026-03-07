<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for encode_gym() and decode_gym() in obfuscation.php.
 *
 * Both functions are pure (no DB, no $_POST) and form a true roundtrip pair:
 *   encode_gym(int): string   — server → client
 *   decode_gym(string): int   — client → server
 *
 * ASYMMETRY NOTE — like encode_1v1/decode_1v1, these are NOT a roundtrip pair:
 *   encode_gym  (server→client): header + beaten_len_len + beaten_len + beaten
 *   decode_gym  (client→server): header + beaten_len + beaten
 *   (client sends a simpler format with no beaten_len_len)
 *
 * Hand-verified encode_gym(0):
 *   encoded_beaten='m'(1), beaten_len='y', beaten_len_len='y'
 *   body='yym'(3), get_Length(1,3)=5→'ya'
 *   FINAL: 'yayym'
 *
 * Hand-verified encode_gym(10):
 *   encoded_beaten='ym'(2), beaten_len='w', beaten_len_len='y'
 *   body='ywym'(4), get_Length(1,4)=6→'yp'
 *   FINAL: 'ypywym'
 *
 * Client wire for decode_gym (from ActionScript save_Info):
 *   _loc5_ = convertIntToString(beaten_len) + convertIntToString(beaten)
 *   header = convertIntToString(get_Length(len(_loc5_).len, len(_loc5_)))
 *   wire = header + _loc5_
 *
 * decode_gym(0): beaten='m'(1), beaten_len='y', body='ym'(2)
 *   get_Length(1,2): 1+2+1=4→'yq'  FINAL: 'yqym'
 *
 * decode_gym(10): beaten='ym'(2), beaten_len='w', body='wym'(3)
 *   get_Length(1,3): 1+3+1=5→'ya'  FINAL: 'yawym'
 */
class GymCodecTest extends TestCase
{
    // -- Wire builder for decode_gym (client format) ---------------------------

    private function buildClientWire(int $beaten): string
    {
        $encoded  = convertIntToString($beaten);
        $len      = convertIntToString(strlen($encoded));
        $body     = $len . $encoded;
        $bodyLen    = strlen($body);
        $bodyLenLen = strlen((string)$bodyLen);
        $header   = convertIntToString((int) get_Length($bodyLenLen, $bodyLen));
        return $header . $body;
    }

    // -- encode_gym ------------------------------------------------------------

    public function test_encode_zero_beaten_known_output(): void
    {
        $this->assertSame('yayym', encode_gym(0));
    }

    public function test_encode_ten_beaten_known_output(): void
    {
        $this->assertSame('ypywym', encode_gym(10));
    }

    public function test_encode_output_contains_only_alphabet_chars(): void
    {
        $validChars = 'mywcqapreo';
        $encoded = encode_gym(42);
        for ($i = 0; $i < strlen($encoded); $i++) {
            $this->assertStringContainsString(
                $encoded[$i], $validChars,
                "Unexpected char '{$encoded[$i]}' at position $i"
            );
        }
    }

    public function test_encode_is_deterministic(): void
    {
        $this->assertSame(encode_gym(7), encode_gym(7));
    }

    public function test_encode_larger_beaten_produces_longer_or_equal_output(): void
    {
        $this->assertGreaterThanOrEqual(strlen(encode_gym(0)), strlen(encode_gym(10)));
    }

    // -- decode_gym — client wire format --------------------------------------

    public function test_decode_client_wire_zero(): void
    {
        // 'yqym': header='yq', beaten_len='y'(1), beaten='m'(0)
        $this->assertSame(0, decode_gym('yqym'));
    }

    public function test_decode_client_wire_ten(): void
    {
        // 'yawym': header='ya', beaten_len='w'(2), beaten='ym'(10)
        $this->assertSame(10, decode_gym('yawym'));
    }

    public function test_decode_built_wire_zero(): void
    {
        $this->assertSame(0, decode_gym($this->buildClientWire(0)));
    }

    public function test_decode_built_wire_one(): void
    {
        $this->assertSame(1, decode_gym($this->buildClientWire(1)));
    }

    public function test_decode_built_wire_typical_values(): void
    {
        foreach ([0, 1, 5, 10, 15, 20, 99] as $beaten) {
            $this->assertSame(
                $beaten,
                decode_gym($this->buildClientWire($beaten)),
                "decode_gym failed for gym_beaten=$beaten"
            );
        }
    }

    public function test_decode_built_wire_large_value(): void
    {
        $this->assertSame(255, decode_gym($this->buildClientWire(255)));
    }
}
