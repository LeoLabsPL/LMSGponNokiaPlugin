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

function NetDevSearch($order = 'name,asc', $search = null, $sqlskey = 'AND')
{
    $DB = LMSDB::getInstance();

    $vaddresses_exists = $DB->ResourceExists('vaddresses', LMSDB::RESOURCE_TYPE_VIEW);

    list($order,$direction) = sscanf($order, '%[^,],%s');
    
    ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

    switch ($order) {
        case 'id':
                        $sqlord = ' ORDER BY id';
            break;
        case 'producer':
                $sqlord = ' ORDER BY producer';
            break;
        case 'model':
                $sqlord = ' ORDER BY model';
            break;
        case 'ports':
                $sqlord = ' ORDER BY ports';
            break;
        case 'takenports':
                $sqlord = ' ORDER BY takenports';
            break;
        case 'serialnumber':
                $sqlord = ' ORDER BY serialnumber';
            break;
        case 'location':
                $sqlord = ' ORDER BY location';
            break;
        default:
                $sqlord = ' ORDER BY name';
            break;
    }

    if (sizeof($search)) {
        foreach ($search as $idx => $value) {
            $value = trim($value);
            if ($value!='') {
                switch ($idx) {
                    case 'ipaddr':
                        $searchargs[] = '(inet_ntoa(n.ipaddr) ?LIKE? '.$DB->Escape("%$value%")
                        .' OR inet_ntoa(n.ipaddr_pub) ?LIKE? '.$DB->Escape("%$value%").')';
                        $nodes = true;
                        break;
                    case 'mac':
                        $searchargs[] = 'n.mac ?LIKE? '.$DB->Escape("%$value%");
                        $nodes = true;
                        break;
                    case 'name':
                        $searchargs[] = '(d.name ?LIKE? '.$DB->Escape("%$value%")
                        .' OR n.name ?LIKE? '.$DB->Escape("%$value%").')';
                        $nodes = true;
                        break;
                    case 'ports':
                        $searchargs[] = "ports = ".intval($value);
                        break;
                    case 'location':
                        $searchargs[] = "UPPER($idx) ?LIKE? UPPER(".$DB->Escape("%$value%").')';
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

    $netdevlist = $DB->GetAll('SELECT DISTINCT d.id, d.name, ' . ($vaddresses_exists ? 'va' : 'd') . '.location, d.description, d.producer,
		d.model, d.serialnumber, d.ports,
		(SELECT COUNT(*) FROM nodes WHERE netdev = d.id AND nodes.ownerid > 0)
	 		+ (SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id) AS takenports
		FROM netdevices d
		' . ($vaddresses_exists ? 'LEFT JOIN vaddresses va ON va.id = d.address_id' : '') . '
		JOIN ' . GPON_NOKIA::SQL_TABLE_GPONOLT . ' g ON g.netdeviceid = d.id '
        . (isset($nodes) ? ' LEFT JOIN nodes n ON (netdev = d.id AND (n.ownerid = 0 OR n.ownerid IS NULL))' : '')
        . ' WHERE 1=1 '
        . (isset($searchargs) ? $searchargs : '')
        . ($sqlord != '' ? $sqlord.' '.$direction : ''));

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
    $layout['pagetitle'] = 'GPON-OLT-'.trans('Network Devices Search Results');

    $netdevlist = NetDevSearch($o, $netdevsearch, $k);

    $listdata['total'] = $netdevlist['total'];
    $listdata['order'] = $netdevlist['order'];
    $listdata['direction'] = $netdevlist['direction'];

    unset($netdevlist['total']);
    unset($netdevlist['order']);
    unset($netdevlist['direction']);

    if ($listdata['total']==1) {
                $SESSION->redirect('?m=gponnokiaoltinfo&id='.$netdevlist[0]['id']);
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

        $SMARTY->display('gponnokiaoltsearchresults.html');
    }
} else {
    $layout['pagetitle'] = 'GPON-OLT-'.trans('Network Devices Search');

    $SESSION->remove('ndlsp');
    
    $SMARTY->assign('k', $k);
    $SMARTY->display('gponnokiaoltsearch.html');
}
