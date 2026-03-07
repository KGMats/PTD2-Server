<?php

/**
 * PHPUnit bootstrap for PTD2 Server tests.
 *
 * Problems this file solves:
 *
 * 1. obfuscation.php starts with:
 *      if (STORAGE_METHOD === 'MYSQL') { require MySQL.php }
 *      else { require json.php }
 *    MySQL.php runs `new mysqli(...)` at the top level on require. We cannot
 *    allow that. We set STORAGE_METHOD='TEST' so MYSQL branch wont fire.
 *    We then require json.php separately for JsonStorageTest.
 *
 * 2. json.php needs JSON_ACCOUNTS_FILE to be defined.
 *    We point it at a writable temp file so tests never touch the real data.
 *
 * 3. config.php defines constants from env vars. We define them here directly
 *    so config.php is never required (it would redefine constants already set).
 *
 * 4. get_available_saveID() is defined in both MySQL.php and json.php.
 *    Since we require json.php below, it will be defined. If json.php's
 *    version reads from $_POST, the refactor (accepting a parameter) will
 *    fix that. Until then, the stub below is used if the function is not
 *    yet defined when this file runs.
 */

// -- Constants -----------------------------------------------------------------
define('ROOT_DIR', realpath(__DIR__ . '/../../app/public'));
define('STORAGE_METHOD', 'TEST');   // prevents MySQL.php and json.php from being
                                    // auto-required inside obfuscation.php
define('JSON_ACCOUNTS_FILE', sys_get_temp_dir() . '/ptd2_test_accounts.json');
define('UNDER_MAINTENANCE', false);
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

// -- Load storage layer (JSON only as we have no MySQL in tests yet) ------------------------
// We require json.php directly here so JsonStorageTest can call its functions.
// MySQL.php is intentionally NOT required — it would try to connect.
require_once ROOT_DIR . '/json.php';

// -- Stub: get_available_saveID ------------------------------------------------
// json.php's get_available_saveID() reads $_POST['whichProfile'] directly.
// Once you refactor it to accept $whichProfile as a parameter, delete this stub.
// Until then, this stub provides a deterministic starting saveID for tests.
if (!function_exists('get_available_saveID')) {
    function get_available_saveID(string $email): int
    {
        return 1;
    }
}

// -- Load the codec ------------------------------------------------------------
require_once ROOT_DIR . '/obfuscation.php';
