#!/usr/bin/env php
<?php

$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

define('START_TIME', microtime());
define('LMS-UI', true);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// find alternative config files:
if (is_readable('lms.ini')) {
    $CONFIG_FILE = 'lms.ini';
//} elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini')) {
//    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini';
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
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

if (empty($_SERVER['SHELL'])) {
    // Redirect to SSL
    $_FORCE_SSL = ConfigHelper::getConfig('phpui.force_ssl', false);

    if ($_FORCE_SSL && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit(0);
    }
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');

$AUTH = null;
$GPON1 = new GPON_NOKIA();

//-----------------------------------------//
$gpon_onu_autoscript_debug = ConfigHelper::getConfig('gpon-nokia.onu_autoscript_debug', 1);
$GPON1->GetGponAutoScript($gpon_onu_autoscript_debug);
//-----------------------------------------//
