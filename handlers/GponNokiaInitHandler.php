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
 * InitHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class GponNokiaInitHandler
{
    /**
     * Sets plugin Smarty templates directory
     *
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
    public function smartyInit(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);

        $SMARTY = $hook_data;

        require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR
            . 'lib' . DIRECTORY_SEPARATOR . 'definitions.php');

        $SMARTY->registerClass('LMSGponNokiaPlugin', 'LMSGponNokiaPlugin');

        return $hook_data;
    }

    /**
     * Sets plugin Smarty modules directory
     *
     * @param array $hook_data Hook data
     * @return array Hook data
     */
    public function modulesDirInit(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    /**
     * Sets plugin menu entries
     *
     * @param array $hook_data Hook data
     * @return array Hook data
     */
    public function menuInit(array $hook_data = array())
    {
        $menu_gpon = array(
            'gpon-nokia' => array(
                'name' => 'GPON NOKIA',
                'css' => 'lms-ui-icon-fiberoptic',
                'link' =>'?m=gponnokiaoltlist',
                'tip' => trans('GPON Nokia Management'),
                'accesskey' =>'k',
                'prio' => 11,
                'submenu' => array(
                    'gponnokiaoltlist' => array(
                        'name' => trans('OLT list'),
                        'link' => '?m=gponnokiaoltlist',
                        'tip' => trans('OLT list'),
                        'prio' => 10,
                    ),
                    'gponnokiaoltadd' => array(
                        'name' => trans('New OLT'),
                        'link' => '?m=gponnokiaoltadd',
                        'tip' => trans('New OLT'),
                        'prio' => 20,
                    ),
                    'gponnokiaoltsearch' => array(
                        'name' => trans('OLT search'),
                        'link' => '?m=gponnokiaoltsearch',
                        'tip' => trans('OLT search'),
                        'prio' => 30,
                    ),
                    'gponnokia-menu-break-1' => array(
                        'name' => '------------',
                        'prio' => 35,
                    ),
                    'gponnokiaonucheck' => array(
                        'name' => trans('Detect ONU'),
                        'link' => '?m=gponnokiaonucheck',
                        'tip' => trans('Detect ONU'),
                        'prio' => 37,
                    ),
                    'gponnokiaonulist' => array(
                        'name' => trans('ONU list'),
                        'link' => '?m=gponnokiaonulist',
                        'tip' => trans('ONU list'),
                        'prio' => 40,
                    ),
                    'gponnokiaonuadd' => array(
                        'name' => trans('New ONU'),
                        'link' => '?m=gponnokiaonuadd',
                        'tip' => trans('New ONU'),
                        'prio' => 50,
                    ),
                    'gponnokiaonusearch' => array(
                        'name' => trans('ONU search'),
                        'link' => '?m=gponnokiaonusearch',
                        'tip' => trans('ONU search'),
                        'prio' => 60,
                    ),
                    'gponnokia-menu-break-2' => array(
                        'name' => '------------',
                        'prio' => 65,
                    ),
                    'gponnokiaonumodelslist' => array(
                        'name' => trans('ONU model list'),
                        'link' => '?m=gponnokiaonumodelslist',
                        'tip' => trans('ONU model list'),
                        'prio' => 70,
                    ),
                    'gponnokiaonumodelsadd' => array(
                        'name' => trans('New ONU model'),
                        'link' => '?m=gponnokiaonumodelsadd',
                        'tip' => trans('New ONU model'),
                        'prio' => 80,
                    ),
                    'gponnokia-menu-break-3' => array(
                        'name' => '------------',
                        'prio' => 85,
                    ),
                    /*'gponnokiaonutvlist' => array(
                        'name' => trans('TV channel list'),
                        'link' => '?m=gponnokiaonutvlist',
                        'tip' => trans('TV channel list'),
                        'prio' => 90,
                    ),
                    'gponnokiaonutvadd' => array(
                        'name' => trans('New TV channel'),
                        'link' => '?m=gponnokiaonutvadd',
                        'tip' => trans('New TV channel'),
                        'prio' => 100,
                    ),
                    'gponnokia-menu-break-4' => array(
                        'name' => '------------',
                        'prio' => 110,
                    ),*/
                    'gponnokia-configlist' => array(
                        'name' => trans('Configuration'),
                        'link' => '?m=configlist&s=gpon-nokia',
                        'tip' => trans('Configuration'),
                        'prio' => 120,
                    ),
                ),
            ),
        );


        $menu_keys = array_keys($hook_data);
        $i = array_search('netdevices', $menu_keys);
        return array_slice($hook_data, 0, $i, true) + $menu_gpon + array_slice($hook_data, $i, null, true);
    }

    /**
     * Modifies access table
     *
     */
    public function accessTableInit()
    {
        $access = AccessRights::getInstance();

        if (DBVERSION >= '2020060900') {
            $permission = new Permission(
                'gpon_nokia_full_access',
                trans('GPON DASAN - module management'),
                '^gponnokia.*$',
                null,
                array('gpon-nokia' => Permission::MENU_ALL)
            );
        } else {
            $permission = new Permission(
                'gpon_nokia_full_access',
                trans('GPON DASAN - module management'),
                '^gponnokia.*$'
            );
        }
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        if (DBVERSION >= '2020060900') {
            $permission = new Permission(
                'gpon_nokia_read_only',
                trans('GPON DASAN - information review'),
                '^((gponnokiaolt|gponnokiaonu|gponnokiaonumodels)(info|list|search|tvinfo|tvlist|signalimage)|customername|customerselect)$',
                null,
                array('gpon-nokia' => array(
                    'gponnokiaoltlist',
                    'gponnokiaoltsearch',
                    'gponnokia-menu-break-1',
                    'gponnokiaonulist',
                    'gponnokiaonusearch',
                    'gponnokia-menu-break-2',
                    'gponnokiaonumodelslist',
                    'gponnokia-menu-break-3',
                    'gponnokiaonutvlist',
                    'gponnokia-menu-break-4',
                    'gponnokia-configlist',
                ))
            );
        } else {
            $permission = new Permission(
                'gpon_nokia_read_only',
                trans('GPON DASAN - information review'),
                '^((gponnokiaolt|gponnokiaonu|gponnokiaonumodels)(info|list|search|tvinfo|tvlist|signalimage)|customername|customerselect)$'
            );
        }
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        if (DBVERSION >= '2020060900') {
            $permission = new Permission(
                'gpon_nokia_auto_provisioning',
                trans('GPON DASAN - auto provisioning (new ONU)'),
                '^(gponnokiaonu(add|script|edit|check))$',
                null,
                array('gpon-nokia' => array(
                    'gponnokiaonucheck',
                    'gponnokiaonuadd',
                    'gponnokiaonuscript',
                ))
            );
        } else {
            $permission = new Permission(
                'gpon_nokia_auto_provisioning',
                trans('GPON DASAN - auto provisioning (new ONU)'),
                '^(gponnokiaonu(add|script|edit|check))$'
            );
        }
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);
    }
}
