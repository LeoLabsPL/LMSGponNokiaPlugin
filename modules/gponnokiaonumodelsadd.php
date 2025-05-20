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

if (isset($_POST['netdev'])) {
    $netdevdata = $_POST['netdev'];
    
            
    if ($netdevdata['name'] == '') {
        $error['name'] = trans('Device name is required!');
    } elseif (strlen($netdevdata['name'])>32) {
        $error['name'] = trans('Device name is too long (max.32 characters)!');
    }

    if (!$error) {
        $netdevid = $GPON->GponOnuModelsAdd($netdevdata);
        $GPON->SetGponOnuPortsType2Models($netdevid, $_POST['portstype']);
        $SESSION->redirect('?m=gponnokiaonumodelsinfo&id='.$netdevid);
    }
    
    $SMARTY->assign('error', $error);
    $SMARTY->assign('netdev', $netdevdata);
}
$portstype = $GPON->GetGponOnuPortsType();
$SMARTY->assign('portstype', $portstype);
$layout['pagetitle'] = 'GPON-ONU-MODEL';
$SMARTY->display('gponnokiaonumodelsadd.html');
