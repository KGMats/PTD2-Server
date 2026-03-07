<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * Tests for the primitive codec functions in obfuscation.php.
 *
 * All expected values in this file are hand-verified by tracing through the
 * source code manually.
 *
 * Functions under test (all pure, no DB, no $_POST):
 *   - convertToString(int): string
 *   - convertToInt(string): string
 *   - convertStringToIntString(string): string
 *   - convertStringToInt(string): int
 *   - convertIntToString(int): string|'-100'
 *   - get_Length(int, int): string
 *   - create_Check_Sum(string): int
 */
class PrimitiveCodecTest extends TestCase
{
    // -- convertToString -------------------------------------------------------

    /**
     * The full alphabet is: ['m','y','w','c','q','a','p','r','e','o']
     * digit 0=m, 1=y, 2=w, 3=c, 4=q, 5=a, 6=p, 7=r, 8=e, 9=o
     *
     * @dataProvider alphabetProvider
     */
    public function test_convertToString_maps_digit_to_letter(int $digit, string $expected): void
    {
        $this->assertSame($expected, convertToString($digit));
    }

    public static function alphabetProvider(): array
    {
        return [
            [0, 'm'], [1, 'y'], [2, 'w'], [3, 'c'], [4, 'q'],
            [5, 'a'], [6, 'p'], [7, 'r'], [8, 'e'], [9, 'o'],
        ];
    }

    public function test_convertToString_returns_minus_one_for_digit_10(): void
    {
        $this->assertSame('-1', convertToString(10));
    }

    public function test_convertToString_returns_minus_one_for_large_value(): void
    {
        $this->assertSame('-1', convertToString(100));
    }

    // -- convertToInt ----------------------------------------------------------

    /**
     * @dataProvider alphabetProvider
     */
    public function test_convertToInt_maps_letter_to_digit_string(int $expectedDigit, string $letter): void
    {
        $this->assertSame((string)$expectedDigit, convertToInt($letter));
    }

    public function test_convertToInt_returns_minus_one_for_unknown_letter(): void
    {
        foreach (['b', 'd', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'n',
                  's', 't', 'u', 'v', 'x', 'z'] as $char) {
            $this->assertSame('-1', convertToInt($char), "Expected -1 for '$char'");
        }
    }

    public function test_convertToInt_returns_minus_one_for_uppercase(): void
    {
        $this->assertSame('-1', convertToInt('M'));
        $this->assertSame('-1', convertToInt('Y'));
    }

    // -- convertIntToString ----------------------------------------------------

    /**
     * Hand-verified:
     *   0   → 'm'
     *   1   → 'y'
     *   9   → 'o'
     *   10  → 'ym'
     *   42  → 'qw'
     *   99  → 'oo'
     *   100 → 'ymm'
     *   255 → 'waa'
     *   1000 → 'ymmm'
     *
     * @dataProvider intToStringProvider
     */
    public function test_convertIntToString_known_values(int $input, string $expected): void
    {
        $this->assertSame($expected, convertIntToString($input));
    }

    public static function intToStringProvider(): array
    {
        return [
            'zero'           => [0,    'm'],
            'one'            => [1,    'y'],
            'nine'           => [9,    'o'],
            'ten'            => [10,   'ym'],
            'forty-two'      => [42,   'qw'],
            'ninety-nine'    => [99,   'oo'],
            'one-hundred'    => [100,  'ymm'],
            'two-fifty-five' => [255,  'waa'],
            'one-thousand'   => [1000, 'ymmm'],
        ];
    }

    // -- convertStringToInt ----------------------------------------------------

    /**
     * @dataProvider intToStringProvider
     */
    public function test_convertStringToInt_known_values(int $expected, string $input): void
    {
        $this->assertSame($expected, convertStringToInt($input));
    }

    // -- convertStringToIntString ----------------------------------------------

    public function test_convertStringToIntString_returns_minus_100_on_unknown_char(): void
    {
        $this->assertSame('-100', convertStringToIntString('z'));
        $this->assertSame('-100', convertStringToIntString('myZ'));
        $this->assertSame('-100', convertStringToIntString('1')); // '1' is not in alphabet
    }

    public function test_convertStringToIntString_returns_empty_string_for_empty_input(): void
    {
        $this->assertSame('', convertStringToIntString(''));
    }

    public function test_convertStringToIntString_single_valid_char(): void
    {
        $this->assertSame('0', convertStringToIntString('m'));
        $this->assertSame('9', convertStringToIntString('o'));
    }

    // -- Alphabet bijection ----------------------------------------------------

    public function test_alphabet_is_a_bijection(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $letter = convertToString($i);
            $back   = convertToInt($letter);
            $this->assertSame((string)$i, $back, "Bijection failed for digit $i");
        }
    }

    public function test_convertIntToString_convertStringToInt_roundtrip(): void
    {
        $cases = array_merge(range(0, 99), [100, 255, 999, 1000, 9999, 10000]);
        foreach ($cases as $n) {
            $encoded = convertIntToString($n);
            $decoded = convertStringToInt($encoded);
            $this->assertSame($n, $decoded, "Roundtrip failed for n=$n (encoded='$encoded')");
        }
    }

    // -- get_Length ------------------------------------------------------------

    /**
     * Hand-verified:
     *   get_Length(1, 4) → loc3=1+4+1=6, '6', len=1==1 → '16'
     *   get_Length(1, 5) → loc3=7 → '17'
     *   get_Length(1, 7) → loc3=9 → '19'
     *   get_Length(1, 9) → loc3=11 (2 digits, 2≠1) → recurse(2,9): 2+9+1=12 → '212'
     *   get_Length(2, 5) → loc3=8 (1 digit, 1≠2) → recurse(1,5): 1+5+1=7 → '17'
     *
     * @dataProvider getLengthProvider
     */
    public function test_get_Length_known_values(int $p1, int $p2, string $expected): void
    {
        $this->assertSame($expected, get_Length($p1, $p2));
    }

    public static function getLengthProvider(): array
    {
        return [
            'p1=1,p2=4'        => [1, 4, '16'],
            'p1=1,p2=5'        => [1, 5, '17'],
            'p1=1,p2=7'        => [1, 7, '19'],
            'p1=1,p2=9_recurse'=> [1, 9, '212'],
            'p1=2,p2=5_recurse'=> [2, 5, '17'],
        ];
    }

    public function test_get_Length_output_is_self_describing(): void
    {
        $cases = [[1,4],[1,5],[1,9],[2,5],[1,6],[1,0],[1,1]];
        foreach ($cases as [$p1, $p2]) {
            $result     = get_Length($p1, $p2);
            $firstDigit = (int)$result[0];
            $rest       = substr($result, 1);
            $this->assertSame(
                strlen($rest),
                $firstDigit,
                "get_Length($p1,$p2)='$result': first digit should equal strlen of remainder"
            );
        }
    }

    // -- create_Check_Sum ------------------------------------------------------

    /**
     * Hand-verified:
     *   ''   -> 15 * 3 = 45
     *   'm'  -> (15 + (ord('m')-96)) * 3 = (15+13)*3 = 84
     *   'y'  -> (15 + (ord('y')-96)) * 3 = (15+25)*3 = 120
     *   '5'  -> (15 + 5) * 3 = 60   (numeric char)
     *   'my' -> (15 + 13 + 25) * 3 = 159
     */
    public function test_create_Check_Sum_empty_string(): void
    {
        $this->assertSame(45, create_Check_Sum(''));
    }

    public function test_create_Check_Sum_single_letter_m(): void
    {
        $this->assertSame(84, create_Check_Sum('m'));
    }

    public function test_create_Check_Sum_single_letter_y(): void
    {
        $this->assertSame(120, create_Check_Sum('y'));
    }

    public function test_create_Check_Sum_numeric_char(): void
    {
        $this->assertSame(60, create_Check_Sum('5'));
    }

    public function test_create_Check_Sum_multi_char(): void
    {
        $this->assertSame(159, create_Check_Sum('my'));
    }

    public function test_create_Check_Sum_is_deterministic(): void
    {
        $input = 'myqwerty';
        $this->assertSame(create_Check_Sum($input), create_Check_Sum($input));
    }
}
