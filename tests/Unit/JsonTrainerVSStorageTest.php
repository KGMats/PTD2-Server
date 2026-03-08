<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for trainerVS storage functions in json.php.
 *
 * Functions under test:
 *   get_trainerVS(string $email, string $pass): array|null
 *   get_trainerVS_opponent(): array
 *   update_account_data($email, $pass, ['trainerVS' => [...]]): bool
 *
 * The trainerVS data structure:
 *   ['trainerVS' => [
 *       'Nickname'  => string,
 *       'avatar'    => int,
 *       'wins'      => int,
 *       'loses'     => int,
 *       'poke'      => [... 3 pokes ...],
 *   ]]
 *
 * get_trainerVS_opponent() picks a random account that has trainerVS data
 * and returns it with an 'ID' key set to the array index.
 */
class JsonTrainerVSStorageTest extends TestCase
{
    private string $accountsFile;

    protected function setUp(): void
    {
        $this->accountsFile = JSON_ACCOUNTS_FILE;
        file_put_contents($this->accountsFile, json_encode([]));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->accountsFile)) {
            unlink($this->accountsFile);
        }
    }

    private function createAccount(string $email, string $plainPass): void
    {
        create_new_account([
            'email' => $email,
            'pass'  => password_hash($plainPass, PASSWORD_DEFAULT),
        ]);
    }

    private function minimalTrainerVS(array $overrides = []): array
    {
        return array_merge([
            'Nickname' => 'Ash',
            'avatar'   => 1,
            'wins'     => 3,
            'loses'    => 1,
            'poke'     => [
                ['num' => 25, 'lvl' => 50, 'move1' => 10, 'move2' => 20, 'move3' => 30, 'move4' => 40, 'gender' => 1, 'extra' => 0, 'item' => 0, 'selected_ability' => 0],
                ['num' => 4,  'lvl' => 40, 'move1' => 10, 'move2' => 20, 'move3' => 30, 'move4' => 40, 'gender' => 0, 'extra' => 0, 'item' => 0, 'selected_ability' => 0],
                ['num' => 7,  'lvl' => 35, 'move1' => 10, 'move2' => 20, 'move3' => 30, 'move4' => 40, 'gender' => 1, 'extra' => 0, 'item' => 0, 'selected_ability' => 0],
            ],
        ], $overrides);
    }

    // ── get_trainerVS ─────────────────────────────────────────────────────────

    public function test_get_trainerVS_returns_null_when_no_data(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->assertNull(get_trainerVS('ash@pallet.com', 'pikachu'));
    }

    public function test_get_trainerVS_returns_null_for_wrong_password(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS()
        ]);
        $this->assertNull(get_trainerVS('ash@pallet.com', 'wrongpass'));
    }

    public function test_get_trainerVS_returns_saved_data(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $trainer = $this->minimalTrainerVS();
        update_account_data('ash@pallet.com', 'pikachu', ['trainerVS' => $trainer]);

        $result = get_trainerVS('ash@pallet.com', 'pikachu');
        $this->assertNotNull($result);
        $this->assertSame('Ash', $result['Nickname']);
        $this->assertSame(1,     $result['avatar']);
        $this->assertSame(3,     $result['wins']);
        $this->assertSame(1,     $result['loses']);
    }

    public function test_get_trainerVS_returns_poke_data(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS()
        ]);
        $result = get_trainerVS('ash@pallet.com', 'pikachu');
        $this->assertArrayHasKey('poke', $result);
        $this->assertCount(3, $result['poke']);
        $this->assertSame(25, $result['poke'][0]['num']);
    }

    public function test_get_trainerVS_does_not_leak_between_accounts(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->createAccount('misty@cerulean.com', 'starmie');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS()
        ]);
        $this->assertNull(get_trainerVS('misty@cerulean.com', 'starmie'));
    }

    // ── update_account_data with trainerVS ────────────────────────────────────

    public function test_update_trainerVS_persists(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $result = update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS(['wins' => 10, 'loses' => 2])
        ]);
        $this->assertTrue($result);
        $trainer = get_trainerVS('ash@pallet.com', 'pikachu');
        $this->assertSame(10, $trainer['wins']);
        $this->assertSame(2,  $trainer['loses']);
    }

    public function test_update_trainerVS_overwrites_previous_data(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS(['wins' => 1])
        ]);
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS(['wins' => 99])
        ]);
        $trainer = get_trainerVS('ash@pallet.com', 'pikachu');
        $this->assertSame(99, $trainer['wins']);
    }

    public function test_update_trainerVS_returns_false_for_wrong_credentials(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $result = update_account_data('ash@pallet.com', 'wrongpass', [
            'trainerVS' => $this->minimalTrainerVS()
        ]);
        $this->assertFalse($result);
        $this->assertNull(get_trainerVS('ash@pallet.com', 'pikachu'));
    }

    // ── get_trainerVS_opponent ────────────────────────────────────────────────

    public function test_get_trainerVS_opponent_returns_a_profile(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS()
        ]);
        $opponent = get_trainerVS_opponent();
        $this->assertIsArray($opponent);
        $this->assertArrayHasKey('Nickname', $opponent);
        $this->assertArrayHasKey('wins',     $opponent);
        $this->assertArrayHasKey('loses',    $opponent);
        $this->assertArrayHasKey('poke',     $opponent);
    }

    public function test_get_trainerVS_opponent_includes_ID_key(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS()
        ]);
        $opponent = get_trainerVS_opponent();
        $this->assertArrayHasKey('ID', $opponent);
    }

    public function test_get_trainerVS_opponent_only_picks_accounts_with_trainerVS(): void
    {
        // ash has no trainerVS, misty does
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->createAccount('misty@cerulean.com', 'starmie');
        update_account_data('misty@cerulean.com', 'starmie', [
            'trainerVS' => $this->minimalTrainerVS(['Nickname' => 'Misty'])
        ]);
        // Should always return Misty since she's the only one with trainerVS
        $opponent = get_trainerVS_opponent();
        $this->assertSame('Misty', $opponent['Nickname']);
    }

    public function test_get_trainerVS_opponent_returns_one_of_multiple_profiles(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->createAccount('misty@cerulean.com', 'starmie');
        update_account_data('ash@pallet.com', 'pikachu', [
            'trainerVS' => $this->minimalTrainerVS(['Nickname' => 'Ash'])
        ]);
        update_account_data('misty@cerulean.com', 'starmie', [
            'trainerVS' => $this->minimalTrainerVS(['Nickname' => 'Misty'])
        ]);
        $opponent = get_trainerVS_opponent();
        $this->assertContains($opponent['Nickname'], ['Ash', 'Misty']);
    }

    public function test_get_trainerVS_opponent_nickname_matches_saved_data(): void
    {
        $this->createAccount('brock@pewter.com', 'onix');
        update_account_data('brock@pewter.com', 'onix', [
            'trainerVS' => $this->minimalTrainerVS(['Nickname' => 'Brock', 'wins' => 7])
        ]);
        $opponent = get_trainerVS_opponent();
        $this->assertSame('Brock', $opponent['Nickname']);
        $this->assertSame(7, $opponent['wins']);
    }
}
