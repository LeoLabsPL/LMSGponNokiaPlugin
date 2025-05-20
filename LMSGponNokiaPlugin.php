<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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
 * LMSGponNokiaPlugin
 *
 * @author Marcin Karwot <marcin.karwot@leolabs.pl>
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSGponNokiaPlugin extends LMSPlugin
{
    const PLUGIN_DIRECTORY_NAME = 'LMSGponNokiaPlugin';
    const PLUGIN_DB_VERSION = '2025051500';
    const PLUGIN_NAME = 'GPON Nokia';
    const PLUGIN_ALIAS = 'gpon-nokia';
    const PLUGIN_DESCRIPTION = 'GPON/XGSPON Nokia Hardware Support';
    const PLUGIN_VERSION = '1.0.2';
    const PLUGIN_AUTHOR = 'Marcin Karwot &lt;marcin.karwot@leolabs.pl&gt;<br>Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';
    //const PLUGIN_DOC_URL = '';
    const PLUGIN_REPO_URL = 'https://github.com/LeoLabsPL/LMSGponNokiaPlugin';

    private static $gpon = null;

    public static function getGponInstance()
    {
        if (empty(self::$gpon)) {
            self::$gpon = new GPON_NOKIA();
        }
        return self::$gpon;
    }

    public static function getRrdDirectory()
    {
        return ConfigHelper::getConfig('gpon-nokia.rrd_directory', PLUGINS_DIR . DIRECTORY_SEPARATOR
            . self::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'rrd');
    }

    public function registerHandlers()
    {
        $this->handlers = array(
            'smarty_initialized' => array(
                'class' => 'GponNokiaInitHandler',
                'method' => 'smartyInit'
            ),
            'modules_dir_initialized' => array(
                'class' => 'GponNokiaInitHandler',
                'method' => 'modulesDirInit'
            ),
            'menu_initialized' => array(
                'class' => 'GponNokiaInitHandler',
                'method' => 'menuInit'
            ),
            'access_table_initialized' => array(
                'class' => 'GponNokiaInitHandler',
                'method' => 'accessTableInit'
            ),
            'customerinfo_before_display' => array(
                'class' => 'GponNokiaCustomerHandler',
                'method' => 'customerInfoBeforeDisplay'
            ),
            'customeredit_before_display' => array(
                'class' => 'GponNokiaCustomerHandler',
                'method' => 'customerEditBeforeDisplay'
            ),
            'nodeadd_before_display' => array(
                'class' => 'GponNokiaNodeHandler',
                'method' => 'nodeAddBeforeDisplay'
            ),
            'nodeinfo_before_display' => array(
                'class' => 'GponNokiaNodeHandler',
                'method' => 'nodeInfoBeforeDisplay'
            ),
            'nodeedit_before_display' => array(
                'class' => 'GponNokiaNodeHandler',
                'method' => 'nodeEditBeforeDisplay'
            ),
            'nodescan_on_load' => array(
                'class' => 'GponNokiaNodeHandler',
                'method' => 'nodeScanOnLoad'
            ),
            'netdevinfo_before_display' => array(
                'class' => 'GponNokiaNetDevHandler',
                'method' => 'netdevinfoBeforeDisplay'
            ),
            'rtticketview_before_display' => array(
                'class' => 'GponNokiaTicketHandler',
                'method' => 'ticketViewBeforeDisplay'
            ),
        );
    }
}
