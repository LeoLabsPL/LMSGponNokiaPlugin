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

$layout['pagetitle'] = 'GPON-ONU-MODELE';

if (!isset($_GET['o'])) {
    $SESSION->restore('mlo', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('mlo', $o);

$modellist = $GPON->GetGponOnuModelsList($o);
$listdata['total'] = $modellist['total'];
$listdata['order'] = $modellist['order'];
$listdata['direction'] = $modellist['direction'];
unset($modellist['total']);
unset($modellist['order']);
unset($modellist['direction']);

if (!isset($_GET['page'])) {
    $SESSION->restore('mlp', $_GET['page']);
}

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('gpon-nokia.onumodels_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('mlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', $start);
$SMARTY->assign('modellist', $modellist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('gponnokiaonumodelslist.html');
