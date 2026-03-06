<?php
/**********************************************************************
*                              General                               *
**********************************************************************/
define('ROOT_DIR', realpath(__DIR__));


/**********************************************************************
*                              Storage                               *
**********************************************************************/


define('STORAGE_METHOD', getenv('STORAGE_METHOD') ?: 'JSON'); // JSON (default) or MYSQL
define('JSON_ACCOUNTS_FILE', getenv('JSON_ACCOUNTS_FILE') ?: ROOT_DIR . '/accounts.json');
define('UNDER_MAINTENANCE', (getenv('UNDER_MAINTENANCE') ?? 'false') === 'true');

define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');
?>
