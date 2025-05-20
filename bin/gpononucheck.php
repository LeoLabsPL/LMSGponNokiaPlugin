#!/usr/bin/env php
<?php

if ($argc > 1 && is_readable($argv[1])) {
    $CONFIG_FILE = $argv[1];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// find alternative config files:
if (is_readable('lms.ini')) {
    $CONFIG_FILE = 'lms.ini';
} elseif (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file ['.$CONFIG_FILE.']!');
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

$AUTH = null;
$GPON = new GPON_NOKIA();

$netdevlist = $GPON->GetGponOnuCheckList();
die(serialize($netdevlist));
