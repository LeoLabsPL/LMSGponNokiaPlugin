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

function NetDevSearch($order = 'name,asc', $search = null, $sqlskey = 'AND')
{
    $DB = LMSDB::getInstance();
    $GPON = LMSGponNokiaPlugin::getGponInstance();

    $vaddresses_exists = $DB->ResourceExists('vaddresses', LMSDB::RESOURCE_TYPE_VIEW);

    list($order,$direction) = sscanf($order, '%[^,],%s');
    
    ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

    switch ($order) {
        case 'id':
            $sqlord = ' ORDER BY d.id';
            break;
        case 'gponolt':
            $sqlord = ' ORDER BY gponolt';
            break;
        case 'profil':
            $sqlord = ' ORDER BY gp.name';
            break;
        case 'model':
            $sqlord = ' ORDER BY gm.name';
            break;
        case 'oltport':
            $sqlord = ' ORDER BY go.numport';
            break;
        case 'onuid':
            $sqlord = ' ORDER BY d.onuid';
            break;
        default:
            $sqlord = ' ORDER BY d.name';
            break;
    }

    if (sizeof($search)) {
        foreach ($search as $idx => $value) {
            $value = trim($value);
            if ($value!='') {
                switch ($idx) {
                    case 'name':
                        $searchargs[] = '(d.name ?LIKE? '.$DB->Escape("%$value%").')';
                        $nodes = true;
                        break;
                    case 'producer':
                        $searchargs[] = 'gm.producer LIKE '.$DB->Escape("%$value%").'';
                        break;
                    case 'model':
                        $searchargs[] = 'gm.name LIKE '.$DB->Escape("%$value%").'';
                        break;
                    case 'portolt':
                        if (preg_match('/\//', $value)) {
                            list($a,$b) = split('/', $value);
                            $value = ($a-1)*4 + $b;
                        }
                        if (intval($value) > 0) {
                            $searchargs[] = 'go.numport = '.intval($value);
                        }
                        break;
                    case 'onuid':
                        if (intval($value) > 0) {
                            $searchargs[] = 'd.onuid = '.intval($value);
                        }
                        break;
                    case 'profil':
                        $searchargs[] = 'gp.name LIKE '.$DB->Escape("%$value%").'';
                        break;

                    default:
                        // UPPER here is a postgresql ILIKE bug workaround
                        $searchargs[] = "UPPER(d.$idx) ?LIKE? UPPER(".$DB->Escape("%$value%").')';
                        break;
                }
            }
        }
    }
    
    if (isset($searchargs)) {
        $searchargs = ' AND ('.implode(' '.$sqlskey.' ', $searchargs).')';
    }
        $sql_query='SELECT DISTINCT d.id, d.name, ' . ($vaddresses_exists ? 'va' : 'nd') . '.location, d.onudescription, d.onuid, gm.producer, go.netdevicesid,
				nd2.name AS gponolt, go.numport, nd.model AS oltmodel, gp.name AS profil,
				gm.name as model, nd.serialnumber,
					(SELECT COUNT(gpononuportstypeid) FROM ' . GPON_NOKIA::SQL_TABLE_GPONONUPORTTYPE2MODELS . '
						WHERE gpononumodelsid=d.gpononumodelsid) AS ports
				FROM ' . GPON_NOKIA::SQL_TABLE_GPONONU . ' d
				JOIN ' . GPON_NOKIA::SQL_TABLE_GPONONUMODELS . ' gm on gm.id=d.gpononumodelsid
				LEFT JOIN ' . GPON_NOKIA::SQL_TABLE_GPONONU2OLT . ' go ON go.gpononuid=d.id
				LEFT JOIN netdevices nd ON nd.id = d.netdevid
				LEFT JOIN netdevices nd2 ON nd2.id = go.netdevicesid
				' . ($vaddresses_exists ? 'LEFT JOIN vaddresses va ON va.id = nd.address_id' : '') . '
				LEFT JOIN ' . GPON_NOKIA::SQL_TABLE_GPONOLTPROFILES . ' gp ON gp.id = d.gponoltprofilesid '
                .' WHERE 1=1 '
                .(isset($searchargs) ? $searchargs : '')
                .($sqlord != '' ? $sqlord.' '.$direction : '');
                //echo $sql_query;
    $netdevlist = $DB->GetAll($sql_query);
    if (!empty($netdevlist)) {
        foreach ($netdevlist as $idx => $row) {
            if (preg_match('/8240/', $row['oltmodel'])) {
                    $netdevlist[$idx]['numport'] = $row['numport'];
            }
        }
    }

    $netdevlist['total'] = empty($netdevlist) ? 0 : count($netdevlist);
    $netdevlist['order'] = $order;
    $netdevlist['direction'] = $direction;

    return $netdevlist;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['search'])) {
        $netdevsearch = $_POST['search'];
}
    
if (!isset($netdevsearch)) {
        $SESSION->restore('netdevsearch', $netdevsearch);
} else {
    $SESSION->save('netdevsearch', $netdevsearch);
}

if (!isset($_GET['o'])) {
    $SESSION->restore('ndlso', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('ndlso', $o);

if (!isset($_POST['k'])) {
        $SESSION->restore('ndlsk', $k);
} else {
    $k = $_POST['k'];
}
$SESSION->save('ndlsk', $k);

if (isset($_GET['search'])) {
    $layout['pagetitle'] = 'GPON-ONU: '.trans('Network Devices Search Results');
    $netdevlist = NetDevSearch($o, $netdevsearch, $k);

    $listdata['total'] = $netdevlist['total'];
    $listdata['order'] = $netdevlist['order'];
    $listdata['direction'] = $netdevlist['direction'];

    unset($netdevlist['total']);
    unset($netdevlist['order']);
    unset($netdevlist['direction']);

    if ($listdata['total']==1) {
                $SESSION->redirect('?m=gponnokiaonuinfo&id='.$netdevlist[0]['id']);
    } else {
        if (!isset($_GET['page'])) {
                $SESSION->restore('ndlsp', $_GET['page']);
        }
    
        $page = (! $_GET['page'] ? 1 : $_GET['page']);
        $pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
        $start = ($page - 1) * $pagelimit;

        $SESSION->save('ndlsp', $page);

        $SMARTY->assign('page', $page);
        $SMARTY->assign('pagelimit', $pagelimit);
        $SMARTY->assign('start', $start);
        $SMARTY->assign('netdevlist', $netdevlist);
        $SMARTY->assign('listdata', $listdata);

        $SMARTY->display('gponnokiaonusearchresults.html');
    }
} else {
    $layout['pagetitle'] = 'GPON-ONU: '.trans('Network Devices Search');

    $SESSION->remove('ndlsp');
    
    $SMARTY->assign('k', $k);
    $SMARTY->display('gponnokiaonusearch.html');
}
