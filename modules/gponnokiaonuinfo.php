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

function gpononu_reset($id)
{
    $GPON = LMSGponNokiaPlugin::getGponInstance();

    $netdevdata = $GPON->GetGponOnu($id);

    $options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
    $GPON->snmp->set_options($options_snmp);

    $res = $GPON->snmp->ONU_Reset($netdevdata['gponoltnumport'], $netdevdata['onuid']);
    return $res;
}

function ONU_reset($id)
{
    $res = gpononu_reset($id);
    if (!is_array($res) || isset($res[0]) && $res[0] != 1) {
        die(json_encode(array('error' => trans('<!gpon-nokia>Failed!'))));
    }
}

function gpononu_factory_settings($id)
{
    $GPON = LMSGponNokiaPlugin::getGponInstance();

    $netdevdata = $GPON->GetGponOnu($id);

    $options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
    $GPON->snmp->set_options($options_snmp);
    $res = $GPON->snmp->ONU_FactorySettings($netdevdata['gponoltnumport'], $netdevdata['onuid']);
    return $res;
}

function ONU_factory_settings($id)
{
    $res = gpononu_factory_settings($id);
    if (!is_array($res) || $res[0] != 1) {
        die(json_encode(array('error' => trans('<!gpon-nokia>Failed!'))));
    }
}

function ONU_radius_disconnect($id)
{
    $GPON = LMSGponNokiaPlugin::getGponInstance();

    $res = $GPON->GponOnuRadiusDisconnect($id);
    if ($res) {
        die(json_encode(array('error' => trans('<!gpon-nokia>Failed!'))));
    }
}

function ONU_xml_provisioning($id)
{
    $GPON = LMSGponNokiaPlugin::getGponInstance();

    $res = $GPON->GponOnuXmlProvisioning(array('id' => $id));
    if (!$res) {
        if (ConfigHelper::checkConfig('gpon-nokia.use_radius')) {
            $res = $GPON->GponOnuRadiusDisconnect($id);
        } else {
            $res = gpononu_reset($id);
            if (is_array($res) && $res[0] == 1) {
                $res = 0;
            } else {
                $res = 1;
            }
        }
    }

    if ($res) {
        die(json_encode(array('error' => trans('<!gpon-nokia>Failed!'))));
    }
}

if (isset($_GET['ajax'])) {

    
    header('Content-Type: application/json');
    if (!isset($_GET['id']) || !intval($_GET['id']) || !isset($_GET['op'])) {
        die;
    }
    $id = intval($_GET['id']);
    switch ($_GET['op']) {
        case 'onu-reset':
            ONU_reset($id);
            break;
        case 'onu-factory-settings':
            ONU_factory_settings($id);
            break;
        case 'onu-radius-disconnect':
            ONU_radius_disconnect($id);
            break;
        case 'onu-xml-provisioning':
            ONU_xml_provisioning($id);
            break;
    }
    die('[]');
}

$GPON = LMSGponNokiaPlugin::getGponInstance();

if (!$GPON->GponOnuExists($_GET['id'])) {
    $SESSION->redirect('?m=gponnokiaonulist');
}

/* Using AJAX plugins */
function GetFreeOltPort_Xj($netdevicesid)
{
    // xajax response
    $GPON = LMSGponNokiaPlugin::getGponInstance();
    $objResponse = new xajaxResponse();
    $freeports=$GPON->GetFreeOltPort($netdevicesid);
    if (is_array($freeports) && count($freeports)>0) {
        $objResponse->script("document.getElementById('numport').options.length=0;");
        $i=0;
        foreach ($freeports as $value) {
            $objResponse->script('xajax.$("numport").options['.$i.'] = new Option("'.$value['numport'].'","'.$value['numport'].'");');
            $i++;
        }
    }
    $objResponse->call("GetFreeOltPort_Xj");
    return $objResponse;
}

function ONU_nokia_get_param_Xj($gponoltid, $OLT_id, $ONU_id, $id, $ONU_name = '')
{
    // xajax response
    $GPON = LMSGponNokiaPlugin::getGponInstance();
    $objResponse = new xajaxResponse();
    $options_snmp=$GPON->GetGponOlt($gponoltid);
    $GPON->snmp->set_options($options_snmp);
    $error_snmp=$GPON->snmp->get_correct_connect_snmp();
    $table_param=$GPON->snmp->ONU_get_param_table($OLT_id, $ONU_id, $ONU_name);
    $objResponse->script("document.getElementById('pokaz_parametry_".$id."').value='" . trans("Hide SNMP settings") . "';");
    $objResponse->script("document.getElementById('odswiez_parametry_".$id."').style.display='';");
    $objResponse->script("document.getElementById('pokaz_parametry_".$id."').onclick=function()"
        . "{document.getElementById('ONU_param_".$id."').innerHTML='';"
        . "document.getElementById('pokaz_parametry_".$id."').value='" . trans("Show SNMP settings") . "';"
        . "document.getElementById('odswiez_parametry_".$id."').style.display='none';"
        . "document.getElementById('pokaz_parametry_".$id."').onclick=function()"
        . "{xajax_ON_nokia_get_param_Xj(".$gponoltid.",'".$OLT_id."',".$ONU_id.",".$id.",'".$ONU_name."');}};");
    $objResponse->assign("ONU_param_".$id, "innerHTML", $error_snmp.$table_param);
    return $objResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(
    array(
        'GetFreeOltPort_Xj',
        'ONU_nokia_get_param_Xj',
    )
);
$SMARTY->assign('xajax', $LMS->RunXajax());
/* end AJAX plugin stuff */

$netdevinfo = $GPON->GetGponOnu($_GET['id']);

$netdevconnected = $GPON->GetGponOltConnectedNames($_GET['id']);
$netdevlist = $GPON->GetNotConnectedOlt();
if (is_array($netdevlist) && !empty($netdevlist)) {
    $numports = $GPON->GetFreeOltPort($netdevlist[0]['id']);
    //print_r($numports);
    //die;
}

$netcomplist = $LMS->GetNetdevLinkedNodes($_GET['id']);

$nodelist = $LMS->GetUnlinkedNodes();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = 'GPON-ONU: '.trans('$a ($b/$c)', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

$netdevinfo['id'] = $_GET['id'];
$netdevinfo['rrd'] = isset($_GET['rrd']) ? $_GET['rrd'] : 0;

$gpononu2customers=$GPON->GetGponOnu2Customers($_GET['id']);
/*
//tak nie działa na wielu klientów - trzeba bardziej inwazyjnie - a miało być jak najmniej inwazyjnie
if(count($gpononu2customers)>0)
{
    foreach($gpononu2customers as $k=>$v)
    {
        $customerid = $v['customersid'];
        include(MODULES_DIR.'/customer.inc.php');
    }
}
$SMARTY->assign('gpononu2customerscount', count($gpononu2customers));
*/

$modelports = $GPON->GetGponOnuModelPorts($netdevinfo['gpononumodelsid']);
$onuports = $GPON->GetGponOnuPorts($_GET['id']);
$netdevinfo['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, $onuports);

/*$vlans = parse_vlans();
if (empty($vlans)) {
    $vlans = array();
}
$SMARTY->assign('vlans', array_flip($vlans));
*/
$onulastauth = $GPON->GetGponOnuLastAuth($_GET['id']);
if (!empty($onulastauth) && count($onulastauth)) {
    $SMARTY->assign('onulastauth', $onulastauth);
}
//$SMARTY->assign('onusyslog', $GPON->GetGponOnuSyslog($_GET['id']));
$SMARTY->assign('gpononu2customerscount', 0);
$SMARTY->assign('gpononu2customers', $gpononu2customers);

$SMARTY->assign('netdevinfo', $netdevinfo);
$SMARTY->assign('numports', $numports);
//print_r($netdevconnected) ;
//die;
$SMARTY->assign('netdevlist', $netdevconnected);
$SMARTY->assign('netcomplist', $netcomplist);
$SMARTY->assign('restnetdevlist', $netdevlist);
$SMARTY->assign('nodelist', $nodelist);
$SMARTY->assign('devlinktype', $SESSION->get('devlinktype'));
$SMARTY->assign('nodelinktype', $SESSION->get('nodelinktype'));

$SMARTY->assign('gponnokiaonuinfo_sortable_order', $SESSION->get_persistent_setting('gpon-nokia-onu-info-sortable-order'));

$SMARTY->display('gponnokiaonu/gponnokiaonuinfo.html');
