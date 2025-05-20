<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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

/**
 * NetDevHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class GponNokiaNetDevHandler
{
    public function netdevinfoBeforeDisplay(array $hook_data)
    {
        $smarty = $hook_data['smarty'];
        $db = LMSDB::getInstance();

        $ifnetdevinfo = false;
        $netdevinfo = $smarty->getTemplateVars('netdevinfo');
        if (empty($netdevinfo)) {
            $netdevinfo = $smarty->getTemplateVars('netdev');
        } else {
            $ifnetdevinfo = true;
        }

        $netdevinfo['gponnokiaonuid'] = $db->GetOne(
            'SELECT id FROM ' . GPON_NOKIA::SQL_TABLE_GPONONU . ' o
			WHERE o.netdevid = ?',
            array($netdevinfo['id'])
        );
        $netdevinfo['gponnokiaoltid'] = $db->GetOne(
            'SELECT g.netdeviceid FROM ' . GPON_NOKIA::SQL_TABLE_GPONOLT . ' g
			WHERE netdeviceid = ?',
            array($netdevinfo['id'])
        );

        if ($ifnetdevinfo) {
            $smarty->assign('netdevinfo', $netdevinfo);
        } else {
            $smarty->assign('netdev', $netdevinfo);
        }

        return $hook_data;
    }
}
