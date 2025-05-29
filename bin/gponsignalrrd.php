#!/usr/bin/env php
<?php

$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

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

define('RRD_DIR', LMSGponNokiaPlugin::getRrdDirectory());
define('RRDTOOL_BINARY', ConfigHelper::getConfig('gpon-nokia.rrdtool_binary', '/usr/bin/rrdtool'));

$stat_freq = ConfigHelper::getConfig('gpon-nokia.stat_freq', 3600);
$stat_divider = 3600 / $stat_freq;

if (!file_exists(RRDTOOL_BINARY)) {
    die("No rrdtool binary found on path " . RRDTOOL_BINARY . "!" . PHP_EOL);
}

$AUTH = null;
$GPON = new GPON_NOKIA();

$rrdtool_process = proc_open(
    RRDTOOL_BINARY . ' -',
    array(
        0 => array('pipe', 'r'),
        1 => array('file', '/dev/null', 'w'),
        2 => array('file', '/dev/null', 'w'),
    ),
    $rrdtool_pipes
);
if (!is_resource($rrdtool_process)) {
    die("Couldn't open " . RRDTOOL_BINARY . "!" . PHP_EOL);
}
function wait2olt($oltid, $GPON, $wait = 60)
{
    $sleep = 0;
    while($GPON->get_bussy($oltid) == 1) {
        echo "OLT $oltid is bussy, waiting..." . PHP_EOL;
        sleep(1);
        $sleep++;
        if ($sleep > $wait) {
            echo "OLT $oltid is bussy for $wait seconds, exiting..." . PHP_EOL;
            break;
        }
    }
}

function update_signal_onu_rrd($onuid, $signal, $oltrx)
{
    global $stat_freq, $stat_divider, $rrdtool_pipes;

    if ((strlen($onuid) == 0) || (strlen($signal) == 0)) {
        return;
    }

    $fname = RRD_DIR . DIRECTORY_SEPARATOR . 'signal_onu_' . $onuid . '.rrd';
    if (!file_exists($fname) || !filesize($fname)) {
        if (file_exists($fname) && !filesize($fname)) {
            @unlink($fname);
        }
        //create rrd
        //$cmd  = RRDTOOL_BINARY . " create $fname --step " . $stat_freq . " ";
        $cmd = "create $fname --step " . $stat_freq . " ";
        $cmd .= "DS:Signal:GAUGE:" . ($stat_freq * 2) . ":-50:10 ";
        $cmd .= "DS:oltrx:GAUGE:" . ($stat_freq * 2) . ":-50:10 ";
        $cmd .= "RRA:AVERAGE:0.5:1:" . round(288 * $stat_divider) . " "; //12 dni co godzine
        $cmd .= "RRA:AVERAGE:0.7:6:" . round(268 * $stat_divider) . " "; //cwierc dnia ~ 2mce
        $cmd .= "RRA:AVERAGE:0.8:24:" . round(1095 * $stat_divider) . " "; //3year - 1day
        $cmd .= "RRA:MIN:0.5:1:" . round(288 * $stat_divider) . " ";
        $cmd .= "RRA:MIN:0.7:6:" . round(268 * $stat_divider) . " ";
        $cmd .= "RRA:MIN:0.8:24:" . round(1095 * $stat_divider) . " ";
        $cmd .= "RRA:MAX:0.5:1:" . round(288 * $stat_divider) . " ";
        $cmd .= "RRA:MAX:0.7:6:" . round(268 * $stat_divider) . " ";
        $cmd .= "RRA:MAX:0.8:24:" . round(1095 * $stat_divider) . " ";
        //exec($cmd);
        fwrite($rrdtool_pipes[0], $cmd . PHP_EOL);
    }
    //update rrd file
    //$cmd  = RRDTOOL_BINARY . " update $fname N:$signal:$oltrx";
    $cmd  = "update $fname N:$signal:$oltrx";

    echo $cmd . PHP_EOL;
    //update via rrdcached deamon, tylko ze jesli to dziala raz na godzine to nie ma to sensu ;)
    //$cmd  = "/usr/bin/rrdupdate $fname --daemon /var/run/rrdcached.sock N:$signal:$oltrx";
    //exec($cmd);
    fwrite($rrdtool_pipes[0], $cmd . PHP_EOL);
}

$olts = $DB->GetAll(
    "SELECT
        g.*,
        nd.id AS netdevid,
        nd.name
    FROM " . GPON_NOKIA::SQL_TABLE_GPONOLT . " g
    JOIN netdevices nd ON nd.id = g.netdeviceid"
);
if (!empty($olts)) {
    $pids = array();

    foreach ($olts as $olt) {
        print_r($olt);
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('pcntl_fork() failed!' . PHP_EOL);
        } elseif (empty($pid)) {
            // child process
            $results = array();

            $GPON->snmp->clear_options();
            if (is_array($olt) && count($olt)) {
                $GPON->snmp->set_options($olt);
                $olt_name = $olt['name'];
            }



            try {
                $DB = LMSDB::getInstance(true);
            } catch (Exception $ex) {
                trigger_error($ex->getMessage(), E_USER_WARNING);
                // can't work without database
                die("Fatal error: cannot connect to database!" . PHP_EOL);
            }
            $sleep = 0;
            
            wait2olt($olt['id'], $GPON, 30);

            $olt_ports = $GPON->snmp->walk('1.3.6.1.4.1.637.61.1.35.10.4.1.2', 'x'); 
            if (empty($olt_ports) || !is_array($olt_ports)) {
                continue;
            }
            foreach ($olt_ports as $port => $olt_port) {
                $port = end(explode('.', $port));
                echo "Port: $port" . PHP_EOL;

        
                $onu_id = $GPON->decode_ont_index($port, 'onu_id');
                $port_decocded = $GPON->decode_ont_index($port, 'port');


                $onuid = $DB->GetOne(
                    "SELECT o.id FROM " . GPON_NOKIA::SQL_TABLE_GPONONU . " o
                    JOIN " . GPON_NOKIA::SQL_TABLE_GPONONU2OLT . " p ON p.gpononuid=o.id
                    WHERE netdevicesid = ? AND numport = ? AND onuid = ?",
                    array($olt['netdevid'], $port_decocded, $onu_id)
                );
                if (!$onuid) {
                    continue;
                }
                
                wait2olt($olt['id'], $GPON, 2);

                $signal=($GPON->snmp->get('1.3.6.1.4.1.637.61.1.35.10.14.1.2.'.$port, 'x'));
                //echo "Signal: $signal" . PHP_EOL;

                if($signal == 32768)
                {
                    $signal = '';
                }
                else
                {
                    $signal = round($signal, 1)*0.002;
                }

                $oltrx=($GPON->snmp->get('.1.3.6.1.4.1.637.61.1.35.10.18.1.2.'.$port, 'x'));
                if($oltrx == 65534)
                {
                    $oltrx = '';
                }
                else
                {
                    $oltrx = round($oltrx*0.1, 1);
                }

             
                $signal = str_replace(',', '.', $signal);
                $oltrx = str_replace(',', '.', $oltrx);

                if ($signal != '' and $oltrx != '') {
                
                    $results[$onuid] = array(
                        'signal' => $signal,
                        'oltrx' => $oltrx,
                    );
                }
                
            }
            print_r($results);
            foreach ($results as $onuid => $result) {
                update_signal_onu_rrd($onuid, $result['signal'], $result['oltrx']);
            }

            exit(0);
        } else {
            // parent process
            $pids[$pid] = $olt['id'];
        }
    }
    
    do {
        $pid_finished = pcntl_wait($status);
        if ($pid_finished == -1) {
            die('pcntl_wait() failed!'. PHP_EOL);
        }
        if (isset($pids[$pid_finished])) {
            unset($pids[$pid_finished]);
        }
    } while (!empty($pids));
}

proc_close($rrdtool_process);
