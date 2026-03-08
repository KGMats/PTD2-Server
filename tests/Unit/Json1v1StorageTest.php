<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for 1v1 storage functions in json.php.
 *
 * Functions under test:
 *   get_1v1(string $email, string $pass): array
 *   update_account_data($email, $pass, ['1v1' => [...]]): bool
 *   delete_profile($email, $pass, '1v1', 'profileN'): bool
 *
 * The '1v1' data structure mirrors story profiles:
 *   ['1v1' => ['profile1' => ['money' => int, 'levelUnlocked' => int], ...]]
 */
class Json1v1StorageTest extends TestCase
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

    // ── get_1v1 ───────────────────────────────────────────────────────────────

    public function test_get_1v1_returns_empty_array_when_no_1v1_data(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->assertSame([], get_1v1('ash@pallet.com', 'pikachu'));
    }

    public function test_get_1v1_returns_empty_array_for_wrong_password(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 100, 'levelUnlocked' => 5]]
        ]);
        $this->assertSame([], get_1v1('ash@pallet.com', 'wrongpass'));
    }

    public function test_get_1v1_returns_saved_profile(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 100, 'levelUnlocked' => 5]]
        ]);
        $result = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertArrayHasKey('profile1', $result);
        $this->assertSame(100, $result['profile1']['money']);
        $this->assertSame(5,   $result['profile1']['levelUnlocked']);
    }

    public function test_get_1v1_returns_multiple_profiles(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => [
                'profile1' => ['money' => 100, 'levelUnlocked' => 5],
                'profile2' => ['money' => 200, 'levelUnlocked' => 10],
            ]
        ]);
        $result = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('profile1', $result);
        $this->assertArrayHasKey('profile2', $result);
    }

    public function test_get_1v1_does_not_leak_between_accounts(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->createAccount('misty@cerulean.com', 'starmie');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 500, 'levelUnlocked' => 8]]
        ]);
        $this->assertSame([], get_1v1('misty@cerulean.com', 'starmie'));
    }

    // ── update_account_data with 1v1 ─────────────────────────────────────────

    public function test_update_1v1_persists_money_and_level(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $result = update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 250, 'levelUnlocked' => 3]]
        ]);
        $this->assertTrue($result);
        $profiles = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertSame(250, $profiles['profile1']['money']);
        $this->assertSame(3,   $profiles['profile1']['levelUnlocked']);
    }

    public function test_update_1v1_overwrites_existing_profile(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 100, 'levelUnlocked' => 2]]
        ]);
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 999, 'levelUnlocked' => 15]]
        ]);
        $profiles = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertSame(999, $profiles['profile1']['money']);
        $this->assertSame(15,  $profiles['profile1']['levelUnlocked']);
    }

    public function test_update_1v1_returns_false_for_wrong_credentials(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $result = update_account_data('ash@pallet.com', 'wrongpass', [
            '1v1' => ['profile1' => ['money' => 100, 'levelUnlocked' => 1]]
        ]);
        $this->assertFalse($result);
        $this->assertSame([], get_1v1('ash@pallet.com', 'pikachu'));
    }

    public function test_update_1v1_does_not_affect_other_profiles(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => [
                'profile1' => ['money' => 100, 'levelUnlocked' => 2],
                'profile2' => ['money' => 200, 'levelUnlocked' => 4],
            ]
        ]);
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 999, 'levelUnlocked' => 20]]
        ]);
        $profiles = get_1v1('ash@pallet.com', 'pikachu');
        // profile2 must be untouched
        $this->assertSame(200, $profiles['profile2']['money']);
        $this->assertSame(4,   $profiles['profile2']['levelUnlocked']);
    }

    // ── delete_profile for 1v1 ────────────────────────────────────────────────

    public function test_delete_1v1_profile_removes_it(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 100, 'levelUnlocked' => 2]]
        ]);
        $result = delete_profile('ash@pallet.com', 'pikachu', '1v1', 'profile1');
        $this->assertTrue($result);
        $profiles = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertArrayNotHasKey('profile1', $profiles);
    }

    public function test_delete_1v1_profile_does_not_affect_other_profiles(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => [
                'profile1' => ['money' => 100, 'levelUnlocked' => 2],
                'profile2' => ['money' => 200, 'levelUnlocked' => 4],
            ]
        ]);
        delete_profile('ash@pallet.com', 'pikachu', '1v1', 'profile1');
        $profiles = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertArrayHasKey('profile2', $profiles);
        $this->assertSame(200, $profiles['profile2']['money']);
    }

    public function test_delete_1v1_profile_returns_false_for_wrong_credentials(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', [
            '1v1' => ['profile1' => ['money' => 100, 'levelUnlocked' => 2]]
        ]);
        $result = delete_profile('ash@pallet.com', 'wrongpass', '1v1', 'profile1');
        $this->assertFalse($result);
        $profiles = get_1v1('ash@pallet.com', 'pikachu');
        $this->assertArrayHasKey('profile1', $profiles);
    }
}
