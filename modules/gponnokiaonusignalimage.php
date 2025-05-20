<?php

$rrd_dir = LMSGponNokiaPlugin::getRrdDirectory();

$rrdtool = ConfigHelper::getConfig('gpon-nokia.rrdtool_binary', '/usr/bin/rrdtool');

$filerrd = $rrd_dir . DIRECTORY_SEPARATOR . 'signal_onu_' . intval($_GET['id']) . '.rrd';

if (!file_exists($filerrd)) {
    die;
}

$period = isset($_GET['period']) ? $_GET['period'] : '1d';
if (!preg_match('/^[0-9]+d$/', $period)) {
    $period = '7d';
}

$titles = array("7d" => "tydzień",
        "60d" => "2 miesiące",
        "730d" => "2 lata");
$timestamp = "now-".$period;
$title = $titles[$period];

$quote = "\"";

if (isset($_GET['ext']) && $_GET['ext'] == 1) {
    $opts = array(
      "--imgformat=PNG",
      "-e now",
      "-s $timestamp",
      "-t ".$quote."RxPower - $title".$quote,
      "-r",
      "-b 1000",
      "-h 150",
      "-w 730",
      "-u 0",
      "-l -40",
      "-n LEGEND:7",
      "-n TITLE:8",
      "--slope-mode",
      "-cBACK#DFD5BD",
      "-cSHADEA#CEBD9B",
      "-cSHADEB#CEBD9B",
      "-cCANVAS#efe5cd",
      "DEF:signal=".$quote."$filerrd".$quote.":Signal:AVERAGE",
      "DEF:signal_min=".$quote."$filerrd".$quote.":Signal:MIN",
      "DEF:signal_max=".$quote."$filerrd".$quote.":Signal:MAX",
      "DEF:oltrx=".$quote."$filerrd".$quote.":oltrx:AVERAGE",
      "AREA:signal#dcdcdc:",
      "CDEF:max=signal_max,signal,-",
      "CDEF:red=signal_min,signal,-",
      "CDEF:min=signal_min,100,-",
      "CDEF:off=signal,-40,EQ,-40,0,IF",

      "AREA:max#f1a0a0::STACK",
      "AREA:red#f15858::STACK",
      "AREA:min#f10000:ONU:STACK",
      "LINE2:signal#000000:",
      "LINE1:signal_max#888888:",
      "LINE1:signal_min#888888:",

      "AREA:off#bbbbbb:",
      "GPRINT:signal:MIN:".$quote."Min\: %.1lfdBm\g".$quote,
      "GPRINT:signal:MAX:".$quote."Max\: %.1lfdBm\g".$quote,
      "GPRINT:signal:LAST:".$quote."Last\: %.1lfdBm".$quote,
      "LINE2:oltrx#00c080:OLT",
      "GPRINT:oltrx:MIN:".$quote."Min\: %.1lfdBm\g".$quote,
      "GPRINT:oltrx:MAX:".$quote."Max\: %.1lfdBm\g".$quote,
      "GPRINT:oltrx:LAST:".$quote."Last\: %.1lfdBm".$quote,
      "HRULE:-30#aaaaaa:",
      "HRULE:-20#aaaaaa:",
      "HRULE:-10#aaaaaa:",
      "HRULE:0#aaaaaa:"
    );
} else {
    $opts = array(
      "--imgformat=PNG",
      "-e now",
      "-s $timestamp",
      "-r",
      "-b 1000",
      "-h 90",
      "-w 340",
      "-u 0",
      "-l -40",
      "-n LEGEND:7",
      "--slope-mode",
      "-cBACK#DFD5BD",
      "-cSHADEA#CEBD9B",
      "-cSHADEB#CEBD9B",
      "-cCANVAS#efe5cd",
      "DEF:signal=".$quote."$filerrd".$quote.":Signal:AVERAGE",
      "DEF:oltrx=".$quote."$filerrd".$quote.":oltrx:AVERAGE",
      "CDEF:off=signal,-40,EQ,-40,0,IF",
      "AREA:signal#dcdcdc:",
      "CDEF:red=signal,50,-",

      "LINE2:signal#000000:",
      "AREA:red#f10000::STACK",
      "AREA:off#bbbbbb:",
      "LINE1:oltrx#00c080:",
      "GPRINT:signal:LAST:".$quote."Last\: ONU rx\: %.1lfdBm   ".$quote,
      "GPRINT:oltrx:LAST:".$quote."OLT rx\: %.1lfdBm    ".$quote,
      "HRULE:-30#aaaaaa:",
      "HRULE:-20#aaaaaa:",
      "HRULE:-10#aaaaaa:",
      "HRULE:0#aaaaaa:"
    );
}

putenv('LC_TIME=pl_PL.UTF-8');
$cmd = $rrdtool . ' graph - ' . implode(" ", $opts);
$imgstring = shell_exec($cmd);
$source = imagecreatefromstring($imgstring);

if (isset($_GET['ext']) && $_GET['ext'] == 1) {
    header("Content-type: image/png");
    imagepng($source);
} else {
   /* $sx = 15;
    $sy = 15;
    $ex = 397;
    $ey = 143;
    $nw = $ex - $sx;
    $nh = $ey - $sy;
    $thumb = imagecreatetruecolor($nw, $nh);
    imagecopyresized($thumb, $source, 0, 0, $sx, $sy, $nw, $nh, $nw, $nh);
*/
    header("Content-type: image/png");
    imagepng($source);
}

imagedestroy($source);
