<?php

/**********************************************************************
*                              General                               *
**********************************************************************/
define('ROOT_DIR', realpath(__DIR__));


/**********************************************************************
*                              Storage                               *
**********************************************************************/

const STORAGE_METHOD = 'JSON'; // JSON (default) or MYSQL

const JSON_ACCOUNTS_FILE = ROOT_DIR . '/accounts.json';

const UNDER_MAINTENANCE = false;


# Uncomment the lines below only if you are using SQL-Based Saves
#const DB_HOST = '';
#const DB_USER = '';
#const DB_PASS = '';
#const DB_NAME = '';
?>
