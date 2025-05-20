<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

if (!$LMS->NetDevExists($_GET['id'])) {
    $SESSION->redirect('?m=gponnokiaoltlist');
}

$GPON = LMSGponNokiaPlugin::getGponInstance();

if (isset($_GET['ajax']) && isset($_GET['port'])) {
    $gponoltid = $GPON->GetGponOltIdByNetdeviceId($_GET['id']);
    $options_snmp = $GPON->GetGponOlt($gponoltid);
    $GPON->snmp->set_options($options_snmp);
    $OLT_ONU = $GPON->snmp->OLT_ONU_walk_get_param($_GET['port'], $gponoltid);
    $result = array();

    if (is_array($OLT_ONU) && !empty($OLT_ONU)) {
        foreach ($OLT_ONU as $k => $v) {
            if (is_array($v) && !empty($v)) {
                foreach ($v as $k1 => $v1) {
                    $elemid = 'onu-' . str_replace('/', '-', $GPON->decode_ont_index($k1, 'ont'));
                    if (!isset($elemid)) {
                        $result[$elemid] = array();
                    }
                    switch ($k) {
                        case 'Distance':
                            $result[$elemid]['data-distance'] = preg_replace('/[a-z]+$/i', '', $v1);
                            break;
                        case 'RxPower':
                            $result[$elemid]['data-rx-power'] = preg_replace(array('/dbm/i', '/,/'), array('', '.'), $v1);
                            $v1 = '<font color="' . $GPON->snmp->style_gpon_rx_power($v1, 0) . '">' . $v1 . '</font>';
                            break;
                        case 'OLTrxPower':
                            $result[$elemid]['data-olt-rx-power'] = preg_replace(array('/dbm/i', '/,/'), array('', '.'), $v1);
                            $v1 = '<font color="' . $GPON->snmp->style_gpon_rx_power($v1, 0) . '">' . $v1 . '</font>';
                            break;
                        case 'Profile':
                            $result[$elemid]['data-profile'] = $v1;
                            break;
                        case 'Status':
                            $result[$elemid]['data-status'] = preg_replace('/^.+\(([0-9]+)\).*$/', '$1', $v1);
                            break;
                        case 'DeactiveReason':
                            $result[$elemid]['data-deactive-reason'] = preg_replace('/^.+\(([0-9]+)\).*$/', '$1', $v1);
                            break;
                        case 'ActiveOS':
                            $result[$elemid]['data-active-os'] = $v1;
                            break;
                        case 'InactiveTime':
                            $result[$elemid]['data-inactive-time'] = $v1;
                            if ($v1 > 86400) {
                                $v1 = sprintf("%dd %02dh", floor($v1 / 86400), ($v1 % 86400) / 3600);
                            } elseif ($v1 > 3600) {
                                $v1 = sprintf("%dh %02dm", floor($v1 / 3600), ($v1 % 3600) / 60);
                            } elseif ($v1 > 0) {
                                $v1 = sprintf("%dm %02ds", floor($v1 / 60), $v1 % 60);
                            }
                            break;
                    }
                    $result[$elemid][$k] = $v1;
                }
            }
        }
    }

    $error_snmp = $GPON->snmp->get_correct_connect_snmp();

    die(json_encode(array('gpon' => $result, 'error' => $error_snmp)));
}

$netdevinfo = $LMS->GetNetDev($_GET['id']);
//-GPON-OLT
//Dane OLT
$netdevinfo['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevinfo['id']);
if ($netdevinfo['gponoltid']) {
    $gponoltdata = $GPON->GetGponOlt($netdevinfo['gponoltid']);
    $netdevinfo = array_merge($gponoltdata, $netdevinfo);
    $gponoltportsdata = $GPON->GetGponOltPorts($netdevinfo['gponoltid']);
   
} else {
    $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
}
//-GPON-OLT

if (!isset($_GET['o'])) {
    $SESSION->restore('goltio', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('goltio', $o);

if (!isset($_GET['f'])) {
    $SESSION->restore('goltif', $f);
} else {
    $f = $_GET['f'];
}
$SESSION->save('goltif', $f);

$netdevconnected = $GPON->GetGponOnuConnectedNames($_GET['id'], $o, $f);

$netdevlist = $GPON->GetNotConnectedOnu();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = 'GPON - OLT -'.trans('Device Info: $a $b $c', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

$netdevinfo['id'] = $_GET['id'];

list($order,$d) = sscanf($o, '%[^,],%s');
($d=='desc') ? $d = 'desc' : $d = 'asc';

$listdata['order'] = $order;
$listdata['direction'] = $d;
$listdata['filtr'] = $f;

$GPON->snmp->set_options($gponoltdata);
$error_snmp = $GPON->snmp->get_correct_connect_snmp();
if (empty($error_snmp)) {
    $table_OLT_param = $GPON->snmp->OLT_get_param_table($netdevinfo['gponoltid']);
} else {
    $table_OLT_param = '';
}
$SMARTY->assign('table_OLT_param', $error_snmp . $table_OLT_param);

//nie wszyscy maja wezly
if ($DB->GetOne("SELECT count(*) FROM information_schema.tables WHERE table_name = 'netdevnodes'") > 0) {
    $q="SELECT * from netdevnodes where id=?";
    $lok=$DB->GetAll($q, array($netdevinfo['netdevnodeid']));
    $netdevinfo['lokalizacja']=$lok[0];
}

$SMARTY->assign('netdevinfo', $netdevinfo);
//-GPON-OLT
//Dane OLTPORTS


$SMARTY->assign('gponoltportsinfo', $gponoltportsdata);

//-GPON-OLT
if (is_array($netdevconnected) && count($netdevconnected)>0) {
    foreach ($netdevconnected as $k => $v) {
        $netdevconnected[$k]['gpononu2customers']=$GPON->GetGponOnu2Customers($v['id']);
        $netdevconnected[$k]['numport_js'] = str_replace('/', '-', $v['numport']);
    }
}
//echo '<pre>';
//print_r($netdevconnected);
//die;
$SMARTY->assign('netdevlist', $netdevconnected);
$SMARTY->assign('restnetdevlist', $netdevlist);
$SMARTY->assign('devlinktype', $SESSION->get('devlinktype'));
$SMARTY->assign('listdata', $listdata);

$SMARTY->display('gponnokiaoltinfo.html');
