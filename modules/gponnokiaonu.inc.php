<?php
/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
$gponnokiaonus = $GPON->GetGponOnuCustomersNames($_GET['id']);

/* Using AJAX plugins */
function ONU_nokia_get_param_Xj($gponoltid, $OLT_id, $ONU_id, $id, $ONU_name = '')
{
    // xajax response
    $GPON = LMSGponNokiaPlugin::getGponInstance();
    $objResponse = new xajaxResponse();
    $options_snmp = $GPON->GetGponOlt($gponoltid);
    $GPON->snmp->set_options($options_snmp);
    $error_snmp = $GPON->snmp->get_correct_connect_snmp();
    $table_param = $GPON->snmp->ONU_get_param_table($OLT_id, $ONU_id, $ONU_name);
    $objResponse->script("document.getElementById('pokaz_parametry_" . $id . "').value='" . trans("Hide SNMP settings") . "';");
    $objResponse->script("document.getElementById('pokaz_parametry_" . $id . "').onclick=function()"
        . "{document.getElementById('ONU_param_" . $id . "').innerHTML='';"
        . "document.getElementById('pokaz_parametry_" . $id . "').value='" . trans("Show SNMP settings") . "';"
        . "document.getElementById('pokaz_parametry_" . $id . "').onclick=function()"
        . "{xajax_ONU_nokia_get_param_Xj(" . $gponoltid . ",'" . $OLT_id . "'," . $ONU_id . "," . $id . ",'" . $ONU_name . "');}};");
    $objResponse->assign("ONU_param_" . $id, "innerHTML", $error_snmp . $table_param);
    return $objResponse;
}

$LMS->RegisterXajaxFunction('ONU_nokia_get_param_Xj');
/* end AJAX plugin stuff */

$SMARTY->assign('gponnokiaonus', $gponnokiaonus);
