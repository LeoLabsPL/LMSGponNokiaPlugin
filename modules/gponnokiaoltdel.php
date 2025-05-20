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

if (!$LMS->NetDevExists($_GET['id'])) {
    $SESSION->redirect('?m=gponnokiaoltlist');
}

$layout['pagetitle'] = 'GPON-OLT-'.trans('Deletion of Device with ID: $a', sprintf('%04d', $_GET['id']));
$SMARTY->assign('netdevid', $_GET['id']);

$onus = $GPON->GetGponOnuConnectedNames($_GET['id']);
if (!empty($onus)) {
    $body = '<P>'.trans('ONTs are connected to the OLT. Can not remove OLT.').'</P>';
} else {
    if (empty($_GET['is_sure'])) {
        $body = '<P>'.trans('Are you sure, you want to delete that device?').'</P>';
        $body .= '<P><A HREF="?m=gponnokiaoltdel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A></P>';
    } else {
        $body = '<P>'.trans('Device has been deleted.').'</P>';
        $GPON->DeleteGponOlt($_GET['id']);
        if (isset($_GET['netdev'])) {
            $LMS->DeleteNetDev($_GET['id']);
        }
        header('Location: ?m=gponnokiaoltlist');
    }
}

$SMARTY->assign('body', $body);
$SMARTY->display('dialog.html');
