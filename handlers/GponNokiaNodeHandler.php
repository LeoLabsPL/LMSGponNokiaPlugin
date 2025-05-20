<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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
 * NodeHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class GponNokiaNodeHandler
{
    public function nodeAddBeforeDisplay(array $hook_data)
    {
        global $LMS;

        $GPON = LMSGponNokiaPlugin::getGponInstance();

        $SMARTY = $hook_data['smarty'];
        $oldid = isset($_GET['id']) ? $_GET['id'] : null;
        $_GET['id'] = isset($_GET['ownerid']) ? $_GET['ownerid'] : null;

        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');
        if (!isset($resource_tabs['gpon-nokia-onu']) || $resource_tabs['gpon-nokia-onu']) {
            require_once(PLUGINS_DIR . '/' . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . '/modules/gponnokiaonu.inc.php');
        }

        $_GET['id'] = $oldid;

        return $hook_data;
    }

    public function nodeInfoBeforeDisplay(array $hook_data)
    {
        global $LMS;

        $GPON = LMSGponNokiaPlugin::getGponInstance();

        $SMARTY = $hook_data['smarty'];
        $oldid = $_GET['id'];
        $_GET['id'] = LMSDB::GetInstance()->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array($_GET['id']));

        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');
        if (!isset($resource_tabs['gpon-nokia-onu']) || $resource_tabs['gpon-nokia-onu']) {
            require_once(PLUGINS_DIR . '/' . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . '/modules/gponnokiaonu.inc.php');
        }

        $_GET['id'] = $oldid;

        return $hook_data;
    }

    public function nodeEditBeforeDisplay(array $hook_data)
    {
        global $LMS;

        $GPON = LMSGponNokiaPlugin::getGponInstance();

        $SMARTY = $hook_data['smarty'];
        $oldid = $_GET['id'];
        $_GET['id'] = LMSDB::GetInstance()->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array($_GET['id']));

        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');
        if (!isset($resource_tabs['gpon-nokia-onu']) || $resource_tabs['gpon-nokia-onu']) {
            require_once(PLUGINS_DIR . '/' . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . '/modules/gponnokiaonu.inc.php');
        }

        $_GET['id'] = $oldid;

        return $hook_data;
    }

    public function nodeScanOnLoad()
    {
        global $SMARTY, $LMS;

        $GPON = LMSGponNokiaPlugin::getGponInstance();

        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');
        if (!isset($resource_tabs['gpon-nokia-onu']) || $resource_tabs['gpon-nokia-onu']) {
            require_once(PLUGINS_DIR . '/' . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . '/modules/gponnokiaonu.inc.php');
        }
    }
}
