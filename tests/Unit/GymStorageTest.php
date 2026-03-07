<?php

declare(strict_types=1);

namespace PTD2\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for gym_challenges storage functions in json.php.
 *
 * Functions under test:
 *   get_gym(string $email, string $pass): int
 *   update_account_data($email, $pass, ['gym_challenges' => int]): bool
 */
class JsonGymStorageTest extends TestCase
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

    // ── get_gym ───────────────────────────────────────────────────────────────

    public function test_get_gym_returns_zero_when_no_gym_data_exists(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->assertSame(0, get_gym('ash@pallet.com', 'pikachu'));
    }

    public function test_get_gym_returns_zero_for_wrong_password(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 5]);
        // Wrong pass — get_account returns null, no gym_challenges key → returns 0
        $this->assertSame(0, get_gym('ash@pallet.com', 'wrongpass'));
    }

    public function test_get_gym_returns_saved_beaten_count(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 3]);
        $this->assertSame(3, get_gym('ash@pallet.com', 'pikachu'));
    }

    public function test_get_gym_returns_updated_beaten_count(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 3]);
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 7]);
        $this->assertSame(7, get_gym('ash@pallet.com', 'pikachu'));
    }

    public function test_get_gym_does_not_affect_other_accounts(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $this->createAccount('misty@cerulean.com', 'starmie');
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 8]);
        $this->assertSame(0, get_gym('misty@cerulean.com', 'starmie'));
    }

    // ── update_account_data with gym_challenges ───────────────────────────────

    public function test_update_gym_challenges_persists(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $result = update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 5]);
        $this->assertTrue($result);
        $this->assertSame(5, get_gym('ash@pallet.com', 'pikachu'));
    }

    public function test_update_gym_challenges_returns_false_for_wrong_credentials(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        $result = update_account_data('ash@pallet.com', 'wrongpass', ['gym_challenges' => 5]);
        $this->assertFalse($result);
        $this->assertSame(0, get_gym('ash@pallet.com', 'pikachu'));
    }

    public function test_update_gym_challenges_zero(): void
    {
        $this->createAccount('ash@pallet.com', 'pikachu');
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 5]);
        update_account_data('ash@pallet.com', 'pikachu', ['gym_challenges' => 0]);
        $this->assertSame(0, get_gym('ash@pallet.com', 'pikachu'));
    }
}
