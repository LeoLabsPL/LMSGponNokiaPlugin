<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

$GPON = LMSGponNokiaPlugin::getGponInstance();

if (!$LMS->NetDevExists($_GET['id'])) {
    $SESSION->redirect('?m=gponnokiaoltlist');
}

/* Using AJAX plugins */
function OLT_ONU_walk_Xj($gponoltid, $port = null, $callback = null)
{
    // xajax response
    $GPON = LMSGponNokiaPlugin::getGponInstance();
    $objResponse = new xajaxResponse();
    $options_snmp = $GPON->GetGponOlt($gponoltid);
    $GPON->snmp->set_options($options_snmp);
    $OLT_ONU = $GPON->snmp->OLT_ONU_walk_get_param($port);
    if (is_array($OLT_ONU) && !empty($OLT_ONU)) {
        foreach ($OLT_ONU as $k => $v) {
            if (is_array($v) && !empty($v)) {
                foreach ($v as $k1 => $v1) {
                    if ($k == 'RxPower') {
                        $objResponse->script("\$('#onu-" . str_replace('.', '-', $k1) . "').attr(
							'data-rx-power', '" . preg_replace(array('/dbm/i', '/,/'), array('', '.'), $v1) . "');");
                        $v1 = '<font color="' . $GPON->snmp->style_gpon_rx_power($v1, 0) . '">' . $v1 . '</font>';
                    }
                    $objResponse->assign($k . "_ONU_" . $k1, "innerHTML", $v1);
                }
            }
        }
    }
    $error_snmp = $GPON->snmp->get_correct_connect_snmp();

    if ($callback === null) {
        $objResponse->assign("OLT_ONU_date", "innerHTML", $error_snmp.trans('Data of:').' <b>'.date('Y-m-d H:i:s').'</b>');
    } else {
        $objResponse->call($callback);
    }

    return $objResponse;
}

function OLT_get_param_Xj($gponoltid, $id)
{
    // xajax response
    $GPON = LMSGponNokiaPlugin::getGponInstance();
    $objResponse = new xajaxResponse();
    $options_snmp=$GPON->GetGponOlt($gponoltid);
    $GPON->snmp->set_options($options_snmp);
    $error_snmp=$GPON->snmp->get_correct_connect_snmp();
    $table_param=$GPON->snmp->OLT_get_param_table();
    $objResponse->script("document.getElementById('pokaz_dane_OLT_".$id."').value='".trans("Refresh SNMP data")."';");
    $objResponse->assign("OLT_dane_".$id, "innerHTML", $error_snmp.$table_param);
    return $objResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('OLT_ONU_walk_Xj', 'OLT_get_param_Xj'));
$SMARTY->assign('xajax', $LMS->RunXajax());

/* end AJAX plugin stuff */

$action = !empty($_GET['action']) ? $_GET['action'] : '';
$edit = '';
$subtitle = '';

switch ($action) {
    case 'writememory':
        $GPON->snmp->clear_options();
        $netdevdata = $LMS->GetNetDev($_GET['id']);
        $netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
        if ($netdevdata['gponoltid']) {
            $options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
            $GPON->snmp->set_options($options_snmp);
            $GPON->snmp->OLT_write_config();
            $GPON->Log(4, GPON_NOKIA::SQL_TABLE_GPONOLT, $netdevdata['gponoltid'], 'SNMP: write memory');
        }
        $SESSION->redirect('?m=gponnokiaoltinfo&id=' . $_GET['id']);
        break;
    case 'updateportlist':
        $GPON->snmp->clear_options(); 
        $netdevdata = $LMS->GetNetDev($_GET['id']);
        $netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
        if ($netdevdata['gponoltid']) {
            $options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
            $GPON->snmp->set_options($options_snmp);

            $gponoltports = array();
            
            $gponoltportsdata = $GPON->snmp->OLT_get_ports_maxonu();
            if (is_array($gponoltportsdata) && count($gponoltportsdata)) {
                foreach ($gponoltportsdata as $k => $v) {
                    $gponoltports[$k]['gponoltid'] = $netdevdata['gponoltid'];
                    $gponoltports[$k]['numport'] = $GPON->decode_ont_index($k);
                    $gponoltports[$k]['maxonu'] = $v;
                    $gponoltports[$k]['desc'] = '';
                }
            }

            //print_r($gponoltports);
            //die;
            $gponoltportsdesc = $GPON->snmp->OLT_get_ports_description();
            if (is_array($gponoltportsdesc) && count($gponoltportsdesc)) {
                foreach ($gponoltportsdesc as $k => $v) {
                    $gponoltports[$k]['desc'] = str_replace('&nbsp;', '', $gponoltportsdesc[$k]);
                }
            }

            if (!empty($gponoltports)) {
                $GPON->GponOltPortsUpdate($gponoltports, $netdevdata['gponoltid']);
            }

            $GPON->Log(4, GPON_NOKIA::SQL_TABLE_GPONOLT, $netdevdata['gponoltid'], 'All ports updated');
        }
        $SESSION->redirect('?m=gponnokiaoltinfo&id=' . $_GET['id']);
        break;
    case 'disconnect':
        $GPON->snmp->clear_options();
        $netdevdata = $LMS->GetNetDev($_GET['id']);
        $netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
        if ($netdevdata['gponoltid']) {
            $options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
            $GPON->snmp->set_options($options_snmp);
            $gpon_onu=$GPON->GetGponOnu($_GET['devid']);
            $snmp_result=$GPON->snmp->ONU_delete($_GET['numport'], $gpon_onu['onuid']);
            $snmp_error=$GPON->snmp->parse_result_error($snmp_result);
            if (strlen($snmp_error)) {
                $dev['linkolt'] = trans('Unable to remove assignment of this ONU - SNMP error.').$snmp_error;
                $SMARTY->assign('connect', $dev);
            } else {
                $GPON->GponOnuUnLink($_GET['id'], $_GET['numport'], $_GET['devid']);
                $SESSION->redirect('?m=gponnokiaoltinfo&id='.$_GET['id']);
            }
        }
        break;
    case 'connect':
        $portexist = intval($GPON->GetGponOltPortsExists($_GET['id'], $_GET['numport']));
        if (!$portexist) {
            $error['numport'] = trans('Such a port does not exist.');
        } else {
            $maxonu = $GPON->GetGponOltPortsMaxOnu($_GET['id'], $_GET['numport']);
            $onucountonport = $GPON->GetGponOnuCountOnPort($_GET['id'], $_GET['numport']);
            if ($onucountonport >= $maxonu) {
                $error['numport'] = trans('This port reached its maximum. The ONU can no longer be assigned.');
            }
            $gponlink = $GPON->IsGponOnuLink2olt($_GET['gpononu']);
            if ($gponlink) {
                $error['linkolt'] = trans('It is no longer possible to assign a previously selected ONU - it has been assigned a moment.');
                $dev['linkolt'] = $error['linkolt'];
            }
        }
        $dev['id'] = !empty($_GET['gpononu']) ? intval($_GET['gpononu']) : '0';
        $dev['numport'] = !empty($_GET['numport']) ? intval($_GET['numport']) : '0';
        if (!$error) {
            $GPON->snmp->clear_options();
            $netdevdata = $LMS->GetNetDev($_GET['id']);
            $netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
            if ($netdevdata['gponoltid']) {
                $options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
                $error_option = $GPON->snmp->set_options($options_snmp);
                if (strlen($error_option)) {
                    $dev['linkolt'] = trans('Can not assign this ONU - SNMP error.').$error_option;
                } else {
                    $gpon_onu = $GPON->GetGponOnu($_GET['gpononu']);
                    $snmp_result = $GPON->snmp->ONU_add($_GET['numport'], $gpon_onu['name'], $gpon_onu['password'], $gpon_onu['onu_desc']);
                    $snmp_error = $GPON->snmp->parse_result_error($snmp_result);
                    if (strlen($snmp_error)) {
                        $dev['linkolt'] = trans('Can not assign this ONU - SNMP error.').$snmp_error;
                    } else if ($snmp_result['ONU_id']) {
                        $GPON->GponOnuUpdateOnuId($_GET['gpononu'], $snmp_result['ONU_id']);
                        $GPON->GponOnuLink($_GET['id'], $dev['numport'], $_GET['gpononu']);
                        $SESSION->redirect('?m=gponnokiaoltinfo&id=' . $_GET['id']);
                    } else {
                        $dev['linkolt'] = trans('Unable to assign ONU ID.');
                    }
                }
            }
        }

        $SMARTY->assign('connect', $dev);
        break;
    case 'switchlinktype':
        $LMS->SetNetDevLinkType($_GET['devid'], $_GET['id'], $_GET['linktype']);
        $SESSION->redirect('?m=gponnokiaoltinfo&id=' . $_GET['id']);
        break;
    default:
        $edit = 'data';
        break;
}


if (isset($_POST['snmpsend']) && $_POST['snmpsend'] == 1) {
    $GPON->snmp->clear_options();
    $netdevdata = $LMS->GetNetDev($_GET['id']);
    $netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
    if ($netdevdata['gponoltid']) {
        $options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
        $GPON->snmp->set_options($options_snmp);

        $GPON->snmp->OLT_set_defaultServiceProfile($_POST['serviceProfile']);
        $GPON->snmp->OLT_set_radiususernametype($_POST['olt_radiususernametype']);

        if (check_ip($_POST['olt_radiusAddress']) && strlen(trim($_POST['olt_radiusKey']))) {
            $GPON->snmp->OLT_add_radius($_POST['olt_radiusAddress'], $_POST['olt_radiusKey'], intval($_POST['olt_radiusPort']));
        }

        if (strlen(trim($_POST['new_autotime_ModelName'])) && $_POST['new_autotime_Start']>-1 && $_POST['new_autotime_Stop']>-1) {
            $GPON->snmp->OLT_set_autoupgrade_time($_POST['new_autotime_ModelName'], $_POST['new_autotime_Start'], $_POST['new_autotime_Stop'], $_POST['new_autotime_Reboot']);
        }

        if (check_ip($_POST['new_autoupgrade_address']) && $_POST['new_autoupgrade_ModelName'] && $_POST['new_autoupgrade_FW']) {
            $GPON->snmp->OLT_set_autoupgrade_model(
                $_POST['new_autoupgrade_ModelName'],
                $_POST['new_autoupgrade_FW'],
                $_POST['new_autoupgrade_address'],
                $_POST['new_autoupgrade_method'],
                $_POST['new_autoupgrade_user'],
                $_POST['new_autoupgrade_passwd'],
                $_POST['new_autoupgrade_version'],
                $_POST['new_autoupgrade_exclude']
            );
        }

        foreach ($_POST as $k => $v) {
            if (preg_match('/radiusid\_/', $k)) {
                preg_match('/radiusid\_(.+)/', $k, $match);
                $num = intval($match[1]);

                $GPON->snmp->OLT_del_radius($num);
            }

            if (preg_match('/aging\_/', $k)) {
                preg_match('/aging\_(.+)/', $k, $match);
                $port = intval($match[1]);

                $GPON->snmp->OLT_set_AgingTime($port, intval($v));
            }

            if (preg_match('/authmode\_/', $k)) {
                preg_match('/authmode\_(.+)/', $k, $match);
                $port = intval($match[1]);

                $GPON->snmp->OLT_set_AuthMode($port, intval($v));
            }

            if (preg_match('/modelProfile\_/', $k)) {
                preg_match('/modelProfile_(.+)/', $k, $match);
                $model = $match[1];

                $GPON->snmp->OLT_set_ModelServiceProfile($model, $v);
            }

            if (preg_match('/^autoupgrade\_/', $k)) {
                preg_match('/^autoupgrade\_(.+)/', $k, $match);
                $port = intval($match[1]);

                $GPON->snmp->OLT_set_FWAutoUpgrade($port, intval($v));
            }

            if (preg_match('/^autoupgrademodel\_/', $k)) {
                preg_match('/^autoupgrademodel\_(.+)/', $k, $match);
                $model = $match[1];

                $GPON->snmp->OLT_del_autoupgrade_model($model);
            }

            if (preg_match('/^autotime\_/', $k)) {
                preg_match('/^autotime\_(.+)/', $k, $match);
                $model = $match[1];

                $GPON->snmp->OLT_del_autoupgrade_time($model);
            }
        }
        $dump = var_export($_POST, true);
        $GPON->Log(4, GPON_NOKIA::SQL_TABLE_GPONOLT, $netdevdata['gponoltid'], 'SNMP set', $dump);
    }
}

if (isset($_POST['netdev'])) {
    $netdevdata = $_POST['netdev'];
    $netdevdata['oldid'] = $_GET['id'];
    $netdevdata['id'] = $netdevdata['netdevid'];

    if ($netdevdata['name'] == '') {
        $error['name'] = trans('Device name is required!');
    } elseif (strlen($netdevdata['name']) > 32) {
        $error['name'] =  trans('Specified name is too long (max.$a characters)!', '32');
    }

    //-GPON-OLT
    //walidacja parametrów SNMP
    if (intval($netdevdata['snmp_version'])>0 && strlen(trim($netdevdata['snmp_host']))==0) {
        $error['snmp_host'] = trans('Enter the host IP address');
    }
    if (intval($netdevdata['snmp_version'])>2) {
        if (strlen(trim($netdevdata['snmp_username']))==0) {
            $error['snmp_username'] = trans('Enter Username (login)');
        }
        if (strlen(trim($netdevdata['snmp_password']))==0) {
            $error['snmp_password'] = trans('Enter Password');
        }
    } elseif (intval($netdevdata['snmp_version'])>0) {
        if (strlen(trim($netdevdata['snmp_community']))==0) {
            $error['snmp_community'] = trans('Please enter the Community');
        }
    }
    //-GPON-OLT

    if (!$error) {
        //-GPON-OLT
        //Update OLT
        $GPON->GponOltUpdate($netdevdata);
        $gponoltportsdata = $_POST['gponoltports'];
        if ($netdevdata['gponoltid'] && is_array($gponoltportsdata) && !empty($gponoltportsdata)) {
            foreach ($gponoltportsdata as $k => $v) {
                $gponoltports[$k]['gponoltid'] = $netdevdata['gponoltid'];
                $gponoltports[$k]['numport'] = $k;
                $gponoltports[$k]['maxonu'] = $v;
            }
            $GPON->GponOltPortsUpdate($gponoltports, $netdevdata['gponoltid']);
        }
        //-GPON-OLT
        $SESSION->redirect('?m=gponnokiaoltinfo&id=' . ($netdevdata['oldid'] != $netdevdata['id'] ? $netdevdata['id'] : $_GET['id']));
    }
} else {
    $netdevdata = $LMS->GetNetDev($_GET['id']);
    $netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
}

$netdevdata['id'] = $_GET['id'];

$netdevconnected = $GPON->GetGponOnuConnectedNames($_GET['id']);
$netdevlist = $GPON->GetNotConnectedOnu();

//-GPON-OLT
//Dane OLT
$gponoltdata = $GPON->GetGponOlt($netdevdata['gponoltid']);
$netdevdata = array_merge($gponoltdata, $netdevdata);
$gponoltportsdata = $GPON->GetGponOltPorts($netdevdata['gponoltid']);
//-GPON-OLT

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);




$layout['pagetitle'] = 'GPON - OLT - '.trans('Device Edit: $a ($b)', $netdevdata['name'], $netdevdata['producer']);

if ($subtitle) {
    $layout['pagetitle'] .= ' - '.$subtitle;
}

if ($DB->GetOne("SELECT count(*) FROM information_schema.tables WHERE table_name = 'netdevnodes'") > 0) {
    $q="SELECT * from netdevnodes order by symbol";
    $nd[]=array("id"=>0,"nazwa"=>trans("— select —"), "symbol"=>"---");
    $nd=array_merge($nd, $DB->GetAll($q));
    $netdevdata['lok']=$nd;
}

$SMARTY->assign('error', $error);
$SMARTY->assign('netdevinfo', $netdevdata);
//-GPON-OLT
//Dane OLTPORTS
$SMARTY->assign('gponoltportsinfo', $gponoltportsdata);
//-GPON-OLT
if (is_array($netdevconnected) && count($netdevconnected)>0) {
    foreach ($netdevconnected as $k => $v) {
        $netdevconnected[$k]['gpononu2customers']=$GPON->GetGponOnu2Customers($v['id']);
    }
}

$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
$GPON->snmp->set_options($options_snmp);
$error_snmp=$GPON->snmp->get_correct_connect_snmp();

$SMARTY->assign('error_snmp', $error_snmp);

$SMARTY->assign('netdevlist', $netdevconnected);
$SMARTY->assign('restnetdevlist', $netdevlist);
$SMARTY->assign('notgponoltdevices', $GPON->GetNotGponOltDevices($netdevdata['gponoltid']));

switch ($edit) {
    case 'data':
        $SMARTY->display('gponnokiaoltedit.html');
        break;
    default:
        $SMARTY->display('gponnokiaoltinfo.html');
        break;
}
