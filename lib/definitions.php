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
/*
function parse_lan_networks($name = '')
{
    $lan_networks = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_lan_networks', '', true);

    $nets = preg_split('/(\s+|[;,])/', $lan_networks);
    if (empty($nets)) {
        return array();
    }
    $lan_networks = array();
    foreach ($nets as $net) {
        if (!preg_match('/^(?:(?<gateway>(?:(?:\d|[1-9]\d|1\d\d|2(?:[0-4]\d|5[0-5]))\.){3}(?:\d|[1-9]\d|1\d\d|2(?:[0-4]\d|5[0-5])))\|)?'
            . '(?:(?<net>(?:(?:\d|[1-9]\d|1\d\d|2(?:[0-4]\d|5[0-5]))\.){3}(?:\d|[1-9]\d|1\d\d|2(?:[0-4]\d|5[0-5])))'
            . '-(?<lastaddress>(?:\d|[1-9]\d|1\d\d|2(?:[0-4]\d|5[0-5]))(?:\.(?:\d|[1-9]\d|1\d\d|2(?:[0-4]\d|5[0-5]))){0,3}))?'
            . '\|(?<mask>(?:[8-9]|[1-2]\d|30))\|(?<name>.+)$/', $net, $m)) {
            continue;
        }

        $netmask = prefix2mask($m['mask']);
        $netname = $m['name'];

        if (empty($m['net'])) {
            $gateway = $m['gateway'];
            if (empty($gateway)) {
                continue;
            }

            $first_dhcp_ip = $last_dhcp_ip = '';
            $netaddr = getnetaddr($gateway, $netmask);
        } else {
            $first_dhcp_ip = $m['net'];
            $first_dhcp_ip_octets = explode('.', $first_dhcp_ip);
            $last_dhcp_ip_octets = explode('.', $m['lastaddress']);
            $last_dhcp_ip_octets = array_merge(array_slice($first_dhcp_ip_octets, 0, 4 - count($last_dhcp_ip_octets)), $last_dhcp_ip_octets);
            $last_dhcp_ip = implode('.', $last_dhcp_ip_octets);

            $first_dhcp_ip_long = ip_long($first_dhcp_ip);
            $last_dhcp_ip_long = ip_long($last_dhcp_ip);
            if ($last_dhcp_ip_long - $first_dhcp_ip_long < 4) {
                continue;
            }

            $netaddr = getnetaddr($first_dhcp_ip, $netmask);
            $braddr = getbraddr($first_dhcp_ip, $netmask);
            $lastaddr = ip_long($braddr) - 1;
            $gateway = !empty($m['gateway']) ? ip_long($m['gateway']) : ip_long($netaddr) + 1;
            if ($gateway >= $first_dhcp_ip_long) {
                if ($lastaddr <= $last_dhcp_ip_long) {
                    continue;
                }
                $gateway = $lastaddr;
            }
            $gateway = long2ip($gateway);
        }

        if (!empty($name) && $netname != $name) {
            continue;
        }

        $lan_networks[] = array(
            'net' => $netaddr,
            'gateway' => $gateway,
            'first_dhcp_ip' => $first_dhcp_ip,
            'last_dhcp_ip' => $last_dhcp_ip,
            'mask' => $netmask,
            'name' => $netname,
        );
    }
    return $lan_networks;
}

function validate_lan_network(&$properties, &$error)
{
    $fields = array('lan_netaddress', 'lan_netmask', 'lan_gateway');
    if ($properties['lan_firstdhcpip'] != '' || $properties['lan_lastdhcpip'] != '') {
        $fields = array_merge($fields, array('lan_firstdhcpip', 'lan_lastdhcpip'));
    }

    $return = false;
    $empties = 0;
    foreach ($fields as $field) {
        if ($properties[$field] == '') {
            $error[$field] = trans('Field should not be empty!');
            $empties++;
            $return = true;
        } elseif (!check_ip($properties[$field])) {
            $error[$field] = trans('Invalid format!');
            $return = true;
        }
    }
    if ($empties == count($fields)) {
        foreach ($fields as $field) {
            unset($error[$field]);
        }
        $return = true;
    }
    if ($return) {
        return;
    }

    $netaddress = getnetaddr($properties['lan_netaddress'], $properties['lan_netmask']);
    if ($netaddress != $properties['lan_netaddress']) {
        $error['lan_netaddress'] = trans('Network address is IP address inside the network!');
        return;
    }
    $netgateway = getnetaddr($properties['lan_gateway'], $properties['lan_netmask']);
    if ($netgateway != $properties['lan_netaddress']) {
        $error['lan_gateway'] = trans('Gateway address outside the network!');
        return;
    }
    if ($properties['lan_gateway'] == $netaddress) {
        $error['lan_gateway'] = trans('Gateway address the same as network address!');
        return;
    }
    $braddress = getbraddr($properties['lan_netaddress'], $properties['lan_netmask']);
    if ($properties['lan_gateway'] == $braddress) {
        $error['lan_gateway'] = trans('Gateway address the same as broadcast address!');
        return;
    }

    if ($properties['lan_firstdhcpip'] == '' && $properties['lan_lastdhcpip'] == '') {
        return;
    }

    $netaddress_long = ip_long($netaddress);
    $braddress_long = ip_long($braddress);
    $gateway_long = ip_long($properties['lan_gateway']);
    $firstdhcpip_long = ip_long($properties['lan_firstdhcpip']);
    $lastdhcpip_long = ip_long($properties['lan_lastdhcpip']);
    if ($firstdhcpip_long <= $netaddress_long || $firstdhcpip_long >= $braddress_long) {
        $error['lan_firstdhcpip'] = trans('First DHCP address outside the network!');
        return;
    }
    if ($lastdhcpip_long <= $netaddress_long || $lastdhcpip_long >= $braddress_long) {
        $error['lan_lastdhcpip'] = trans('Last DHCP address outside the network!');
        return;
    }
    if ($firstdhcpip_long > $lastdhcpip_long) {
        $error['lan_firstdhcpip'] = trans('First DHCP address greater than last DHCP address!');
        return;
    }
    if ($firstdhcpip_long <= $gateway_long && $gateway_long <= $lastdhcpip_long) {
        $error['lan_gateway'] = trans('Gateway address between first and last DHCP addresses!');
    }
}

function parse_vlans()
{
    $vlans = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_vlans', '', true);
    if (empty($vlans)) {
        return null;
    }

    $vlans = preg_split('/(\s+|[;,])/', $vlans);
    $result = array();
    foreach ($vlans as &$vlan) {
        if (!preg_match('/^(?<id>[0-9]{1,4})\|(?<name>.+)$/', $vlan, $m)) {
            continue;
        }
        $vlanid = intval($m['id']);
        if ($vlanid < 1 || $vlanid > 4095) {
            continue;
        }
        $result[$m['name']] = $vlanid;
    }
    ksort($result);
    return $result;
}

*/
