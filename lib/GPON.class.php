<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

class GPON_NOKIA
{
    const SQL_TABLE_SYSLOG = 'syslog';
    const SQL_TABLE_GPONOLT = 'gponnokiaolts';
    const SQL_TABLE_GPONOLTPORTS = 'gponnokiaoltports';
    const SQL_TABLE_GPONONU2OLT = 'gponnokiaonu2olts';
    const SQL_TABLE_GPONOLTPROFILES = 'gponnokiaoltprofiles';
    const SQL_TABLE_GPONONU = 'gponnokiaonus';
    const SQL_TABLE_GPONONU2CUSTOMERS = 'gponnokiaonu2customers';
    const SQL_TABLE_GPONONUMODELS = 'gponnokiaonumodels';
    const SQL_TABLE_GPONONUPORTTYPE2MODELS = 'gponnokiaonuporttype2models';
    const SQL_TABLE_GPONONUPORTTYPES = 'gponnokiaonuporttypes';
    const SQL_TABLE_GPONONUPORTS = 'gponnokiaonuports';
    const SQL_TABLE_GPONONUTV = 'gponnokiaonutv';
    const SQL_TABLE_GPONAUTHLOG = 'gponnokiaauthlog';
    //const SQL_TABLE_GPONONUSYSLOG = 'gponnokiaonusyslog';

    private $DB;            // database object
    public $snmp;

    public function __construct()
    {
    // class variables setting
        $this->DB = LMSDB::getInstance();

        $options = array();
        $this->snmp = new GPON_NOKIA_SNMP($options, $this);
    }

    /*
    public function GetGponOnuSyslog($onuid)
    {
        return $this->DB->GetAll(
            "SELECT
                o.*,
                nd.id AS oltid,
                nd.producer AS oltproducer,
                nd.model AS oltmodel
            FROM " . self::SQL_TABLE_GPONONUSYSLOG . " o
            JOIN " . self::SQL_TABLE_GPONONU2OLT . " go2o ON go2o.gpononuid = o.onuid
            JOIN netdevices nd ON nd.id = go2o.netdevicesid
            WHERE onuid = ?
            ORDER BY time DESC",
            array($onuid)
        );
    }*/

    public function Log($loglevel = 0, $what = null, $xid = null, $message = null, $detail = null)
    {
        if (isset($detail)) {
            $detail = str_replace("'", '"', $detail);
        }
        if (ConfigHelper::getConfig('gpon-nokia.syslog')) {
            $this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_SYSLOG . ' (time, userid, level, what, xid, message, detail)
				VALUES (?NOW?, ?, ?, ?, ?, ?, ?)', array(Auth::GetCurrentUser(), $loglevel, $what, $xid, $message, $detail));
        }
    }
    public function set_bussy($OLT_id, $state)
    {
        if($state != 0 AND $state != 1) {
            return false;
        }
        $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLT . ' SET snmp_is_bussy = ? WHERE id = ?', array($state, $OLT_id));
    }
    public function get_bussy($OLT_id)
    {
        return $this->DB->GetOne('SELECT snmp_is_bussy FROM ' . self::SQL_TABLE_GPONOLT . ' WHERE id = ?', array($OLT_id));
    }   

    //--------------OLT----------------
    public function GponOltAdd($gponoltdata)
    {
        if ($this->DB->Execute(
            'INSERT INTO ' . self::SQL_TABLE_GPONOLT . ' (snmp_version, snmp_description, snmp_host,
				snmp_community, snmp_auth_protocol, snmp_username, snmp_password, snmp_sec_level,
				snmp_privacy_passphrase, snmp_privacy_protocol, netdeviceid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array(
                    $gponoltdata['snmp_version'],
                    $gponoltdata['snmp_description'],
                    $gponoltdata['snmp_host'],
                    $gponoltdata['snmp_community'],
                    $gponoltdata['snmp_auth_protocol'],
                    $gponoltdata['snmp_username'],
                    $gponoltdata['snmp_password'],
                    $gponoltdata['snmp_sec_level'],
                    $gponoltdata['snmp_privacy_passphrase'],
                    $gponoltdata['snmp_privacy_protocol'],
                    $gponoltdata['netdevid'],
            )
        )) {
            $id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONOLT);
            $dump = var_export($gponoltdata, true);
            $this->Log(4, self::SQL_TABLE_GPONOLT, $id, 'added ' . $gponoltdata['snmp_host'], $dump);
            return $id;
        } else {
            return false;
        }
    }

    public function NetDevUpdate($netdevdata)
    {
        $this->DB->Execute(
            'UPDATE ' . self::SQL_TABLE_GPONOLT . ' SET netdeviceid = ? WHERE id = ?',
            array($netdevdata['id'], $netdevdata['gponoltid'])
        );
    }

    public function GetGponOltIdByNetdeviceId($netdeviceid)
    {
        return $this->DB->GetOne('SELECT id FROM ' . self::SQL_TABLE_GPONOLT
            . ' WHERE netdeviceid = ?', array($netdeviceid));
    }

    public function GetGponOlt($id)
    {
        $result = $this->DB->GetRow('SELECT g.*
			FROM ' . self::SQL_TABLE_GPONOLT . ' g
			WHERE g.id = ?', array($id));
        return $result;
    }

    public function GetGponOltList($order = 'name,asc')
    {
        $vaddresses_exists = $this->DB->ResourceExists('vaddresses', LMSDB::RESOURCE_TYPE_VIEW);

        if (!isset($order) || empty($order)) {
            $order = 'name,asc';
        }

        list ($order, $direction) = sscanf($order, '%[^,],%s');

        switch ($order) {
            case 'id':
                $order = 'id';
                break;
            case 'name':
                $order = 'name';
                break;
            case 'producer':
                $order = 'producer';
                break;
            case 'model':
                $order = 'model';
                break;
            case 'location':
                $order = 'location';
                break;
            case 'ports':
                $order = 'ports';
                break;
            case 'serialnumber':
                $order = 'serialnumber';
                break;
            default:
                $order = 'id';
                break;
        }

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        $olts = $this->DB->GetAll('SELECT nd.id, COUNT(gp.id) AS gponports,
				nd.name, ' . ($vaddresses_exists ? 'va' : 'nd') . '.location, nd.description, nd.producer, nd.model, nd.serialnumber,
				nd.ports, go.id AS gponoltid
			FROM ' . self::SQL_TABLE_GPONOLT . ' go
			JOIN netdevices nd ON nd.id = go.netdeviceid
			' . ($vaddresses_exists ? 'LEFT JOIN vaddresses va ON va.id = nd.address_id' : '') . '
			LEFT JOIN ' . self::SQL_TABLE_GPONOLTPORTS . ' gp ON gp.gponoltid = go.id
			GROUP BY nd.id, nd.name, ' . ($vaddresses_exists ? 'va' : 'nd') . '.location, nd.description, nd.producer, nd.model,
				nd.serialnumber, nd.ports, go.id
			ORDER BY ' . $order . ' ' . $direction);

        $olts['total'] = empty($olts) ? 0 : count($olts);
        $olts['order'] = $order;
        $olts['direction'] = $direction;

        return $olts;
    }

    public function GponOltUpdate($gponoltdata)
    {
        $dump = var_export($gponoltdata, true);
        $this->Log(4, self::SQL_TABLE_GPONOLT, $gponoltdata['gponoltid'], 'updated '. $gponoltdata['snmp_host'], $dump);

        $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLT . ' SET snmp_version=?, snmp_description=?, snmp_host=?, snmp_community=?,
			snmp_auth_protocol=?, snmp_username=?, snmp_password=?, snmp_sec_level=?, snmp_privacy_passphrase=?,
			snmp_privacy_protocol=?, netdeviceid = ? WHERE id = ?', array(
                    $gponoltdata['snmp_version'],
                    $gponoltdata['snmp_description'],
                    $gponoltdata['snmp_host'],
                    $gponoltdata['snmp_community'],
                    $gponoltdata['snmp_auth_protocol'],
                    $gponoltdata['snmp_username'],
                    $gponoltdata['snmp_password'],
                    $gponoltdata['snmp_sec_level'],
                    $gponoltdata['snmp_privacy_passphrase'],
                    $gponoltdata['snmp_privacy_protocol'],
                    $gponoltdata['id'],
                    $gponoltdata['gponoltid']
                ));
        if ($gponoltdata['id'] != $gponoltdata['oldid']) {
            $this->DB->Execute(
                'UPDATE netdevices SET name = ? WHERE id = ?',
                array($gponoltdata['name'], $gponoltdata['id'])
            );
            $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU2OLT
                . ' SET netdevicesid = ? WHERE netdevicesid = ?', array($gponoltdata['id'], $gponoltdata['oldid']));
        } else {
            $this->DB->Execute('UPDATE netdevices SET name = ? WHERE id = ?', array($gponoltdata['name'], $gponoltdata['id']));
        }
    }

    public function DeleteGponOlt($id)
    {
        $this->DB->BeginTrans();
        $gponoltid = $this->GetGponOltIdByNetdeviceId($id);
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONOLT . ' WHERE id = ?', array($gponoltid));
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' WHERE gponoltid = ?', array($gponoltid));
        $this->Log(4, self::SQL_TABLE_GPONOLT, $gponoltid, 'deleted, devid: '.$id);
        $this->DB->CommitTrans();
    }

    public function GponOltPortsAdd($gponoltportsdata)
    {
        if (is_array($gponoltportsdata) && count($gponoltportsdata)) {
            $logolt=0;
            foreach ($gponoltportsdata as $v) {
                $this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONOLTPORTS . ' (gponoltid, numport, maxonu)
					VALUES (?, ?, ?)', array($v['gponoltid'], $v['numport'], $v['maxonu']));
                $logolt = $v['gponoltid'];
            }
            $dump = var_export($gponoltportsdata, true);
            $this->Log(4, self::SQL_TABLE_GPONOLT, $logolt, 'ports added', $dump);
        }
    }

    public function GetGponOltPorts($gponoltid)
    {
        if ($result = $this->DB->GetAll(
            'SELECT gp.*, nd.model,
			(SELECT COUNT(go2o.gpononuid) FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				JOIN ' . self::SQL_TABLE_GPONOLT . ' g ON g.netdeviceid = nd.id
				WHERE g.id = gp.gponoltid AND go2o.numport = gp.numport
			) AS countlinkport
			FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' gp
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = gp.gponoltid
			JOIN netdevices nd ON nd.id = go.netdeviceid
			WHERE gp.gponoltid = ? ORDER BY gp.numport ASC',
            array($gponoltid)
        )) {
            foreach ($result as $idx => $row) {
                $row['numportf'] = $row['numport'];

                $result_out[$this->snmp->calc_pon_index($row['numport'])] = $row;
            }
            ksort($result_out); // sortowanie aby łądnie były poukładane
        }
       
        return $result_out;
    }

    public function decodeOntSerialNumber($hexString) {
        // Usuń spacje z ciągu heksadecymalnego
        $hexString = str_replace(' ', '', $hexString);
        
        // Przygotuj wynikowy ciąg ASCII dla pierwszych 4 znaków
        $serialAscii = '';
        
        // Konwertuj pierwsze 4 bajty (8 znaków hex) na ASCII, jeśli możliwe
        $maxAsciiBytes = min(8, strlen($hexString)); // Max 4 bajty lub długość ciągu
        for ($i = 0; $i < $maxAsciiBytes; $i += 2) {
            $hex = substr($hexString, $i, 2);
            $dec = hexdec($hex);
            
            // Jeśli to drukowalny znak ASCII, dodaj go do wyniku
            if ($dec >= 32 && $dec <= 126) { 
                $serialAscii .= chr($dec);
            }
        }
        
        // Formatuj pozostałą część numeru seryjnego jako HEX
        $restHex = '';
        if (strlen($hexString) > 8) {
            $rest = substr($hexString, 8);
            for ($i = 0; $i < strlen($rest); $i += 2) {
                $restHex .= substr($rest, $i, 2);
                if ($i < strlen($rest) - 2) {
                    $restHex ;
                }
            }
        }
        
        // Zwróć wynik w formacie mieszanym: część ASCII + reszta w HEX
        //if (strlen($serialAscii) >= 4) {
            return $serialAscii . $restHex;
        //} else {
            // Jeśli nie ma wystarczająco dużo znaków ASCII, zwróć oryginalny format hex
          //  return strtoupper($hexString);
        //}
    }

    public function decode_ont_index($index, $typ = 'port')
    {
        /*  
        $bin = str_pad(decbin($index), 32, "0", STR_PAD_LEFT); 
        $card = bindec(substr($bin, -30, 5)) - 1; 
        $port = bindec(substr($bin, -21, 5)) + 1; 
        return "1/1/".$card."/".$port;
        */
        // Konwersja indeksu na 32-bitowy ciąg binarny
        $bin = str_pad(decbin($index), 32, "0", STR_PAD_LEFT); 
        // Wyciąganie poszczególnych fragmentów zgodnie z formatem w calc_ont_index
        $slot = bindec(substr($bin, 2, 5)) - 1; // Bity 2-6, slot - 1
        // Bity 7-10 to "1110" - stały element
        $pon = bindec(substr($bin, 11, 5)) + 1;  // Bity 11-15, pon + 1
        $ont = bindec(substr($bin, 16, 7)) + 1;  // Bity 16-22, ont + 1
        // Bity 23-31 to "000000000" - stały element
        
        // Budowanie ścieżki ONT w formacie 1/1/slot/pon/ont
        if ($typ == 'port') {
            return "1/1/".$slot."/".$pon;
        } else  if ($typ == 'onu_id') {
            return $ont;
        }else {
            return "1/1/".$slot."/".$pon."/".$ont;
        }
   
    }

    

    public function GponOltPortsUpdate($gponoltportsdata, $oltid)
    {
        $this->DB->BeginTrans();
        if (is_array($gponoltportsdata) && count($gponoltportsdata)) {
            $allports = $this->DB->GetAllByKey('SELECT numport FROM ' . self::SQL_TABLE_GPONOLTPORTS . '
				WHERE gponoltid = ?', 'numport', array($oltid));
            //print_r($gponoltportsdata);
            foreach ($gponoltportsdata as $v) {
                $numport = $this->DB->GetOne('SELECT numport FROM ' . self::SQL_TABLE_GPONOLTPORTS
                    . ' WHERE gponoltid = ? AND numport = ?', array($v['gponoltid'], $v['numport']));
                if ($numport) {
                    $this->DB->Execute(
                        'UPDATE ' . self::SQL_TABLE_GPONOLTPORTS . ' SET maxonu = ?, description = ?
                        WHERE gponoltid = ? AND numport = ?',
                        array($v['maxonu'], $v['desc'], $v['gponoltid'], $v['numport'])
                    );
                } else {
                    $this->DB->Execute(
                        'INSERT INTO ' . self::SQL_TABLE_GPONOLTPORTS . ' (gponoltid, description, numport, maxonu)
                        VALUES (?, ?, ?, ?)',
                        array($v['gponoltid'], $v['desc'], $v['numport'], $v['maxonu'])
                    );
                }
                if (isset($allports[$v['numport']])) {
                    unset($allports[$v['numport']]);
                }
            }
            if (is_array($allports) && count($allports)) {
                foreach ($allports as $k => $v) {
                    $this->DB->Execute(
                        'DELETE FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' WHERE gponoltid = ? AND numport = ?',
                        array($oltid, $k)
                    );
                }
            }
        }
        $dump = var_export($gponoltportsdata, true);
        //$this->Log(4, self::SQL_TABLE_GPONOLT, $gponoltportsdata[1]['gponoltid'], 'ports updated', $dump);
        $this->DB->CommitTrans();
    }

    public function GetGponOltPortsMaxOnu($netdeviceid, $numport)
    {
        $netdeviceid = intval($netdeviceid);
        //$numport = intval($numport);
        return $this->DB->GetOne('SELECT gop.maxonu FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' gop
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = gop.gponoltid
			JOIN netdevices nd ON nd.id = go.netdeviceid
			WHERE nd.id = ? AND gop.numport = ?', array($netdeviceid, $numport));
    }

    public function GetGponOltPortsExists($netdeviceid, $numport)
    {
        $netdeviceid = intval($netdeviceid);
        //$numport = intval($numport);
        return $this->DB->GetOne('SELECT gop.id FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' gop
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = gop.gponoltid
			JOIN netdevices nd ON nd.id = go.netdeviceid
			WHERE nd.id = ? AND gop.numport = ?', array($netdeviceid, $numport));
    }

    public function GetNotConnectedOlt()
    {
        return $this->DB->GetAll('SELECT DISTINCT nd.id, nd.name
			FROM netdevices nd
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
			JOIN ' . self::SQL_TABLE_GPONOLTPORTS . ' p ON p.gponoltid = go.id
			WHERE p.maxonu > (SELECT COUNT(id) FROM ' . self::SQL_TABLE_GPONONU2OLT . ' WHERE netdevicesid = nd.id AND numport = p.numport)
			ORDER BY nd.name ASC');
    }

    public function GetFreeOltPort($netdeviceid)
    {
        return $this->DB->GetAll('SELECT DISTINCT nd.id, gop.numport
			FROM netdevices nd
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
			JOIN ' . self::SQL_TABLE_GPONOLTPORTS . ' gop ON gop.gponoltid = go.id
			WHERE gop.maxonu > (SELECT COUNT(id) FROM ' . self::SQL_TABLE_GPONONU2OLT
                    . ' WHERE netdevicesid = nd.id AND numport = gop.numport)
				AND nd.id = ?
			ORDER BY gop.numport', array($netdeviceid));
    }

    public function GetGponOltConnectedNames($gpononuid)
    {
        if ($list = $this->DB->GetAll('SELECT nd.*, go2o.numport, g.id AS gponoltid
			FROM ' . self::SQL_TABLE_GPONOLT . ' g
			JOIN netdevices nd ON nd.id = g.netdeviceid
			JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.netdevicesid = nd.id
			WHERE go2o.gpononuid = ?', array($gpononuid))) {
            //foreach ($list as &$row) {
            //    $row['numportf'] = $row['numport'];
           // }
        }
        return $list;
    }

    public function GetGponOltProfiles($gponoltid = null)
    {
        $result = $this->DB->GetAllByKey('SELECT p.id, p.name' . (empty($gponoltid) ? ', nd.name AS oltname' : '') . '
			FROM ' . self::SQL_TABLE_GPONOLTPROFILES . ' p
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = p.gponoltid
			LEFT JOIN netdevices nd ON nd.id = go.netdeviceid '
            . (empty($gponoltid) ? '' : 'WHERE p.gponoltid IS NULL OR p.gponoltid = ' . intval($gponoltid)) . '
			ORDER BY p.name ASC', 'id');

        return $result;
    }

    public function AddGponOltProfile($name, $gponoltid)
    {
        $name = trim($name);
        if (!strlen($name)) {
            return;
        }

        if ($pid = $this->DB->GetOne(
            'SELECT id FROM ' . self::SQL_TABLE_GPONOLTPROFILES . '
			WHERE name = ? AND (gponoltid IS NULL OR gponoltid = ?) LIMIT 1',
            array($name, $gponoltid)
        )) {
            $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLTPROFILES . ' SET name = ?, gponoltid = ?
				WHERE id = ?', array($name, $gponoltid, $pid));
            $this->Log(4, self::SQL_TABLE_GPONOLTPROFILES, $pid, 'updated ' . $name
                . ' oltid ' . $gponoltid);
        } else {
            $this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONOLTPROFILES . ' (name, gponoltid)
				VALUES (?, ?)', array($name, $gponoltid));
            $pid = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONOLTPROFILES);
            $this->Log(4, self::SQL_TABLE_GPONOLTPROFILES, $pid, 'added ' . $name
                . ' oltid ' . $gponoltid);
        }
    }

    // uaktualnienie profili ONU pobranych z OLT w bazie danych
    public function UpdateGponOltProfiles($gponoltid)
    {
        // gets onu profiles from database - we'll use it soon to delete
        // olt profiles from database which already does not exist on olt
        $db_profiles = $this->DB->GetAllByKey('SELECT id, name FROM ' . self::SQL_TABLE_GPONOLTPROFILES . '
			WHERE gponoltid = ?', 'name', array($gponoltid));
        if (empty($db_profiles)) {
            $db_profiles = array();
        }

        // gets onu profiles from olt
        $profiles = $this->snmp->walk('.1.3.6.1.4.1.637.61.1.47.3.24.1.2' ,'x');
        if ($profiles === false) {
            // in case of serious snmp communication error we avoid olt onu profile removes
            return;
        }

        if (is_array($profiles) && count($profiles)) {
            foreach ($profiles as $profile) {
                $profile = $this->snmp->clean_snmp_value($profile);

                if (isset($db_profiles[$profile])) {
                    unset($db_profiles[$profile]);
                }

                $this->AddGponOltProfile($profile, $gponoltid);
            }
        }

        // removes onu profile (which does not exist on olt) from database
        foreach ($db_profiles as $profile_name => $profile) {
            $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONOLTPROFILES . '
				WHERE id = ?', array($profile['id']));
            $this->Log(4, self::SQL_TABLE_GPONOLTPROFILES, $profile['id'], 'deleted ' . $profile_name
                . ' oltid ' . $gponoltid);
        }
    }

    public function GetNotGponOltDevices($gponoltid = null)
    {
        return $this->DB->GetAll('SELECT n.id, n.name FROM netdevices n
			LEFT JOIN ' . self::SQL_TABLE_GPONOLT . ' g ON g.netdeviceid = n.id
			WHERE g.netdeviceid IS NULL OR g.id = ?
			ORDER BY name', array($gponoltid));
    }

    //--------------ONU----------------
    public function DeleteGponOnu($id)
    {
        $this->DB->BeginTrans();
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONU
            . ' WHERE id=? AND id NOT IN (SELECT DISTINCT gpononuid FROM ' . self::SQL_TABLE_GPONONU2OLT . ')', array($id));
        $this->Log(4, self::SQL_TABLE_GPONONU, $id, 'deleted');
        $this->DB->CommitTrans();
    }

    public function IsGponOnuLink2olt($gpononuid)
    {
        $gpononuid = intval($gpononuid);
        return $this->DB->GetOne('SELECT COUNT(id) AS liczba FROM ' . self::SQL_TABLE_GPONONU2OLT
            . ' WHERE gpononuid=?', array($gpononuid));
    }

    public function IsGponOnuLink($netdeviceid, $numport, $gpononuid)
    {
        $netdeviceid = intval($netdeviceid);
        //$numport = intval($numport);
        $gpononuid = intval($gpononuid);
        return $this->DB->GetOne(
            'SELECT COUNT(id) AS liczba FROM ' . self::SQL_TABLE_GPONONU2OLT
            . ' WHERE netdevicesid = ? AND numport = ? AND gpononuid = ?',
            array($netdeviceid, $numport, $gpononuid)
        );
    }

    public function GponOnuLink($netdeviceid, $numport, $gpononuid)
    {
        //echo $numport; die;
        $netdeviceid = intval($netdeviceid);
        //$numport = intval($numport);
        $gpononuid = intval($gpononuid);
        if ($netdeviceid && $numport && $gpononuid && !$this->IsGponOnuLink($netdeviceid, $numport, $gpononuid)) {
            $this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'link to ' .$netdeviceid. ', port ' .$numport);

            return $this->DB->Execute(
                'INSERT INTO ' . self::SQL_TABLE_GPONONU2OLT
                . ' (netdevicesid, numport, gpononuid) VALUES (?, ?, ?)',
                array($netdeviceid, $numport, $gpononuid)
            );
        }

        return false;
    }

    public function GponOnuUnLink($netdeviceid, $numport, $gpononuid)
    {
        $netdeviceid = intval($netdeviceid);
        //$numport = intval($numport);
        $gpononuid = intval($gpononuid);
        $this->DB->Execute(
            'DELETE FROM ' . self::SQL_TABLE_GPONONU2OLT
            . ' WHERE netdevicesid = ? AND numport = ? AND gpononuid = ?',
            array($netdeviceid, $numport, $gpononuid)
        );
        $this->DB->Execute('update ' . self::SQL_TABLE_GPONONU
            . ' SET onuid = 0, autoscript = 0 WHERE id = ?', array($gpononuid));
        $this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'unlink with ' .$netdeviceid. ', port ' .$numport);
    }

    public function GponOnuUnLinkAll($gpononuid)
    {
        $gpononuid = intval($gpononuid);
        $this->DB->Execute(
            'DELETE FROM ' . self::SQL_TABLE_GPONONU2OLT . ' WHERE gpononuid = ?',
            array($gpononuid)
        );
        $this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'unlink with all');
    }

    public function GponOnuUpdateOnuId($gpononuid, $onuid)
    {
        $gpononuid=intval($gpononuid);
        $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET onuid = ?
				WHERE id = ?', array($onuid, $gpononuid));
        $this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'onuid updated:'.$onuid);
    }

    public function GetGponOnuConnectedNames($netdeviceid, $order = 'numport,asc', $oltport = 0)
    {
        list ($order, $direction) = sscanf($order, '%[^,],%s');
        ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY id';
                break;
            case 'numport':
                $sqlord = ' ORDER BY go2o.numport';
                break;
            case 'onuid':
                $sqlord = ' ORDER BY onuid';
                break;
            default:
                $sqlord = ' ORDER BY id';
                break;
        }

        //$oltport = intval($oltport);
        if ($oltport) {
            $where = " AND go2o.numport='" . $oltport ."' ";
        } else {
            $where = ' ';
        }

        if ($list = $this->DB->GetAll('SELECT go.id AS gponoltid, n.model AS oltmodel, g.*, gom.name AS model, gom.producer, go2o.numport,
			(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid
			JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.gpononuid = g.id
			JOIN netdevices n ON n.id = go2o.netdevicesid
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = n.id
			WHERE go2o.netdevicesid=? '. $where . $sqlord .' '. $direction, array($netdeviceid))) {
            foreach ($list as $idx => $row) {
                $list[$idx]['numportf'] = $row['numport'];
            }
        }

        return $list;
    }

    public function GetGponOnuCustomersNames($ownerid)
    {
        if ($list = $this->DB->GetAll('SELECT g.*, gom.name AS model, gom.producer, n.model AS oltmodel, gp.name AS profil,
			(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' WHERE gpononumodelsid=g.gpononumodelsid) AS ports,
			(SELECT nd.name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				WHERE go2o.gpononuid = g.id) AS gponolt,
			(SELECT go.id AS gponoltid FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
				WHERE go2o.gpononuid = g.id) AS gponoltid,
			(SELECT go2o.numport FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				WHERE go2o.gpononuid = g.id) AS gponoltnumport,
			(SELECT nd.id AS name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id=g.gpononumodelsid
			JOIN ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' g2c ON g2c.gpononuid=g.id
			LEFT JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.gpononuid=g.id
			LEFT JOIN netdevices n ON n.id=go2o.netdevicesid
			LEFT JOIN ' . self::SQL_TABLE_GPONOLTPROFILES . ' gp ON gp.id = g.gponoltprofilesid
			WHERE g2c.customersid=?', array($ownerid))) {
            foreach ($list as $idx => $row) {
                $list[$idx]['gponoltnumportf'] = $row['gponoltnumport'];
            }
        }

        return $list;
    }

    public function GetGponOnu2Customers($gpononuid)
    {
        return $this->DB->GetAll("SELECT g2c.id,c.id as customersid,
				(" . $this->DB->Concat('c.lastname', "' '", 'c.name') . ") as customersname
			FROM " . self::SQL_TABLE_GPONONU2CUSTOMERS . " g2c
			JOIN customers c On c.id = g2c.customersid
			WHERE g2c.gpononuid = ? ORDER BY g2c.id ASC", array($gpononuid));
    }

    public function GponOnuClearCustomers($gpononuid)
    {
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONU2CUSTOMERS
            . ' WHERE gpononuid = ?', array($gpononuid));
        $this->Log(4, 'gpononu', $gpononuid, 'customers removed');
    }

    public function GponOnuAddCustomer($gpononuid, $customerid)
    {
        $gpononuid = intval($gpononuid);
        $customerid = intval($customerid);
        if ($gpononuid && $customerid && !($this->DB->GetOne('SELECT COUNT(id) AS liczba FROM ' . self::SQL_TABLE_GPONONU2CUSTOMERS
                . ' WHERE gpononuid=? AND customersid=?', array($gpononuid, $customerid)))) {
                $this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' (gpononuid, customersid)
					VALUES (?, ?)', array($gpononuid, $customerid));
                $this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'customers added: ' . $customerid);
        }
    }

    public function GetGponOnuForCustomer($ownerid)
    {
        $result = $this->DB->GetRow('SELECT g.*, gom.name AS model,
				(SELECT sum(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                    . ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports,
					gom.producer,
					(SELECT nd.name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
						JOIN netdevices nd ON nd.id = go2o.netdevicesid
						WHERE go2o.gpononuid = g.id) AS gponolt,
					(SELECT go2o.numport FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
						WHERE go2o.gpononuid=g.id) AS gponoltnumport,
					(SELECT nd.id AS name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
						JOIN netdevices nd ON nd.id = go2o.netdevicesid
						WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid
			JOIN ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' g2c ON g2c.gpononuid = g.id
			WHERE g2c.customersid = ?', array($ownerid));

        return $result;
    }

    public function GetGponOnuPhoneVoip($gpononuid)
    {
        $result = $this->DB->GetAll('SELECT v.id, v.login AS phone
			FROM voipaccounts v
			JOIN customers c ON c.id = v.ownerid
			JOIN ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' g2c ON g2c.customersid = c.id
			WHERE g2c.gpononuid = ?', array($gpononuid));

        return $result;
    }

    public function GetPhoneVoip($id)
    {
        if (intval($id)) {
            $result = $this->DB->GetRow('SELECT v.id, v.login, v.passwd, v.login AS phone
			FROM voipaccounts v
			WHERE v.id = ?', array($id));
        } else {
            $result = array();
        }

        return $result;
    }

    public function GetPhoneVoipForCustomer($ownerid)
    {
        if (intval($ownerid)) {
            $result = $this->DB->GetAll('SELECT v.id, v.login AS phone
				FROM voipaccounts v
				JOIN customers c ON c.id = v.ownerid
				WHERE c.id=?', array($ownerid));
        } else {
            $result = array();
        }
        return $result;
    }

    public function GetHostNameForCustomer($ownerid)
    {
        if (intval($ownerid)) {
            $result = $this->DB->GetAll("SELECT n.id, (" . $this->DB->Concat('n.name', "' / '", 'INET_NTOA(ipaddr)') . ") AS host
				FROM nodes n
				JOIN customers c ON c.id = n.ownerid
				WHERE c.id=?", array($ownerid));
        } else {
            $result = array();
        }
        return $result;
    }

    public function GetHostForNetdevices()
    {
        return $this->DB->GetAll("SELECT n.id, (" . $this->DB->Concat('n.name', "' / '", 'INET_NTOA(ipaddr)') . ") AS host
			FROM nodes n
			LEFT JOIN " . self::SQL_TABLE_GPONONU . " g1 ON g1.host_id1 = n.id
			LEFT JOIN " . self::SQL_TABLE_GPONONU . " g2 ON g2.host_id2 = n.id
			WHERE g1.host_id1 IS NULL AND g2.host_id2 IS NULL AND (n.ownerid IS NULL OR n.ownerid = 0)
			ORDER BY host");
    }

    public function IsNodeIdNetDevice($id)
    {
        if ($this->DB->GetOne("SELECT id FROM nodes WHERE (ownerid = 0 OR ownerid IS NULL) AND id = ?", array($id))) {
            return true;
        } else {
            return false;
        }
    }

    public function GetGponOnuCountOnPort($netdeviceid, $numport)
    {
        $netdeviceid = intval($netdeviceid);
        //$numport = intval($numport);
        return $this->DB->GetOne('SELECT COUNT(gpononuid) AS CountOnPort FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
			WHERE go2o.netdevicesid = ? AND go2o.numport = ?', array($netdeviceid, $numport));
    }

    public function GetGponOnuList($order = 'name,asc')
    {
        list ($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY id';
                break;
            case 'profil':
                $sqlord = ' ORDER BY gp.name';
                break;
            case 'model':
                $sqlord = ' ORDER BY gom.name';
                break;
            case 'ports':
                $sqlord = ' ORDER BY ports';
                break;
            case 'serialnumber':
                $sqlord = ' ORDER BY serialnumber';
                break;
            case 'location':
                $sqlord = ' ORDER BY location';
                break;
            case 'owner':
                $sqlord = ' ORDER BY owner';
                break;
            case 'gponolt':
                $sqlord = ' ORDER BY gponolt';
                break;
            case 'onudescription':
                $sqlord = ' ORDER BY onudescription';
                break;
            default:
                $sqlord = ' ORDER BY name';
                break;
        }
        $where = ' WHERE 1=1 ';

        $vaddresses_exists = $this->DB->ResourceExists('vaddresses', LMSDB::RESOURCE_TYPE_VIEW);

        if ($netdevlist = $this->DB->GetAll('
			SELECT g.*, nd2.serialnumber, nd.name AS gponolt, go2o.numport AS gponoltnumport,
				nd.model AS gponoltmodel, ' . ($vaddresses_exists ? 'va' : 'nd2') . '.location,
				gom.name AS model, nd.id AS gponoltnetdevicesid, go.id AS gponoltid, gom.producer,
				gp.name AS profil, pc.portcount AS ports
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			LEFT JOIN (
				SELECT gpononumodelsid AS modelid, SUM(portscount) AS portcount FROM  ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                    . ' GROUP BY gpononumodelsid
			) pc ON pc.modelid = g.gpononumodelsid
			LEFT JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.gpononuid = g.id
			LEFT JOIN netdevices nd ON nd.id = go2o.netdevicesid
			LEFT JOIN netdevices nd2 ON g.netdevid = nd2.id
			' . ($vaddresses_exists ? 'LEFT JOIN vaddresses va ON va.id = nd2.address_id' : '') . '
			LEFT JOIN ' . self::SQL_TABLE_GPONOLTPROFILES . ' gp ON gp.id = g.gponoltprofilesid
			LEFT JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = go2o.netdevicesid
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid ' . $where
            . ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
            foreach ($netdevlist as $idx => $row) {
                $netdevlist[$idx]['gponoltnumportf'] = $row['gponoltnumport'];
            }
        }

        $netdevlist['total'] = empty($netdevlist) ? 0 : count($netdevlist);
        $netdevlist['order'] = $order;
        $netdevlist['direction'] = $direction;
        return $netdevlist;
    }

   
    public function GetNotConnectedOnu()
    {
        return $this->DB->GetAll('SELECT g.*, gom.name AS model, gom.producer,
				(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                    . ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid
			WHERE g.id NOT IN (SELECT DISTINCT gpononuid FROM ' . self::SQL_TABLE_GPONONU2OLT . ')
			ORDER BY name');
    }

    public function GetGponOnu($id)
    {
        $result = $this->DB->GetRow("SELECT g.*, d.model AS oltmodel, d.name AS netdevname, gom.name AS model, gom.xgspon, gom.swverpland,
				(SELECT SUM(portscount) FROM " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                    . " WHERE gpononumodelsid = g.gpononumodelsid) AS ports, gom.producer,
				(SELECT nd.name FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponolt,
				(SELECT nd.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid,
				(SELECT go2o.numport FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					WHERE go2o.gpononuid = g.id) AS gponoltnumport,
				(SELECT go.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					JOIN " . self::SQL_TABLE_GPONOLT . " go ON go.netdeviceid = nd.id
					WHERE go2o.gpononuid = g.id) AS gponoltid,
				(SELECT gop.name FROM " . self::SQL_TABLE_GPONOLTPROFILES . " gop
					WHERE gop.id = g.gponoltprofilesid) AS profil_olt,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id = g.voipaccountsid1) AS voipaccountsid1_phone,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id = g.voipaccountsid2) AS voipaccountsid2_phone,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id1) AS host_id1_host,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id2) AS host_id2_host
			FROM " . self::SQL_TABLE_GPONONU . " g
			JOIN " . self::SQL_TABLE_GPONONUMODELS . " gom ON gom.id = g.gpononumodelsid
			LEFT JOIN netdevices d ON d.id = g.netdevid
			WHERE g.id = ?", array($id));
        $result['portdetails'] = $this->DB->GetAllByKey("SELECT pt.name, portscount FROM " . self::SQL_TABLE_GPONONU . " o
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . " t2m ON o.gpononumodelsid = t2m.gpononumodelsid
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . "  pt ON pt.id = gpononuportstypeid
			WHERE o.id = ?", 'name', array($id));

        $result['properties'] = empty($result['properties']) ? array() : unserialize($result['properties']);
        $users_table = $this->DB->ResourceExists('vusers', LMSDB::RESOURCE_TYPE_VIEW)
            ? 'vusers' : 'users';
        $result['createdby'] = $this->DB->GetOne('SELECT name FROM ' . $users_table . ' WHERE id=?', array($result['creatorid']));
        $result['modifiedby'] = $this->DB->GetOne('SELECT name FROM ' . $users_table . ' WHERE id=?', array($result['modid']));
        $result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
        $result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
        $result['gponoltnumportf'] = $result['gponoltnumport'];

        return $result;
    }

    public function getGponOnuModelManagementUrls()
    {
        return $this->DB->GetAllByKey("SELECT name, urltemplate FROM " . self::SQL_TABLE_GPONONUMODELS, 'name');
    }

    public function GetGponOnuProperties($id)
    {
        $properties = $this->DB->GetOne("SELECT properties FROM " . self::SQL_TABLE_GPONONU
            . " WHERE id = ?", array($id));
        return unserialize($properties);
    }

    public function GetGponOnuFromName($name)
    {
        $result = $this->DB->GetRow("SELECT g.*,
				(SELECT SUM(portscount) FROM " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                    . " WHERE gpononumodelsid = g.gpononumodelsid) AS ports,
				(SELECT nd.name FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponolt,
				(SELECT nd.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid,
				(SELECT go2o.numport FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					WHERE go2o.gpononuid = g.id) AS gponoltnumport,
				(SELECT go.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					JOIN " . self::SQL_TABLE_GPONOLT . " go ON go.netdeviceid = nd.id
					WHERE go2o.gpononuid=g.id) AS gponoltid,
				(SELECT gop.name FROM " . self::SQL_TABLE_GPONOLTPROFILES . " gop
					WHERE gop.id = g.gponoltprofilesid) AS profil_olt,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id=g.voipaccountsid1) AS voipaccountsid1_phone,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id=g.voipaccountsid2) AS voipaccountsid2_phone,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id1) AS host_id1_host,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id2) AS host_id2_host
			FROM " . self::SQL_TABLE_GPONONU . " g
			WHERE g.name = ?", array($name));
        if (!empty($result)) {
            $result['portdetails'] = $this->DB->GetAllByKey("
				SELECT pt.name, portscount FROM " . self::SQL_TABLE_GPONONU . " o
				JOIN " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . " t2m ON o.gpononumodelsid = t2m.gpononumodelsid
				JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . " pt ON pt.id = gpononuportstypeid
				WHERE o.id = ?", 'name', array($result['id']));
        }

        return $result;
    }

    public function GponOnuNameExists($name)
    {
        return ($this->DB->GetOne("SELECT * FROM " . self::SQL_TABLE_GPONONU
            . " WHERE name = ?", array($name)) ? true : false);
    }

    public function GponOnuExists($id)
    {
        return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONU
            . ' WHERE id = ?', array($id)) ? true : false);
    }

    public function GponOnuAdd($gpononudata)
    {
        $gpononudata['onu_description'] = iconv('UTF-8', 'ASCII//TRANSLIT', $gpononudata['onu_description']);
        $gpononudata['gpononumodelsid'] = intval($gpononudata['gpononumodelid']);
        $gpononumodelid = 1;
        if (empty($gpononudata['gpononumodelsid'])) {
            $gpononudata['onu_model'] = trim($gpononudata['onu_model']);
            if (strlen($gpononudata['onu_model'])) {
                $result = $this->DB->GetRow("SELECT id FROM " . self::SQL_TABLE_GPONONUMODELS
                . " WHERE name = ?", array($gpononudata['onu_model']));
                $gpononudata['gpononumodelsid'] = intval($result['id']);
                if (empty($gpononudata['gpononumodelsid'])) {
                    if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUMODELS . ' (name)
						VALUES (?)', array($gpononudata['onu_model']))) {
                        $gpononudata['gpononumodelsid'] = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONUMODELS);
                        $this->Log(4, self::SQL_TABLE_GPONONUMODELS, $gpononudata['gpononumodelsid'], 'model added via onuadd: ' . $gpononudata['onu_model']);
                    }
                }
            }
        }
        $gpononudata['gpononumodelsid'] = intval($gpononudata['gpononumodelsid']);
        if (empty($gpononudata['gpononumodelsid'])) {
            $gpononudata['gpononumodelsid'] = 1;
        }
        $gpononudata['gponoltprofilesid'] = intval($gpononudata['gponoltprofilesid']) ? $gpononudata['gponoltprofilesid']: null;
        $gpononudata['voipaccountsid1'] = intval($gpononudata['voipaccountsid1']) ? $gpononudata['voipaccountsid1']: null;
        $gpononudata['voipaccountsid2'] = intval($gpononudata['voipaccountsid2']) ? $gpononudata['voipaccountsid2']: null;
        $gpononudata['host_id1'] = intval($gpononudata['host_id1']) ? $gpononudata['host_id1']: null;
        $gpononudata['host_id2'] = intval($gpononudata['host_id2']) ? $gpononudata['host_id2']: null;
        if ($this->DB->Execute(
            'INSERT INTO ' . self::SQL_TABLE_GPONONU . ' (name, gpononumodelsid, password, autoprovisioning, onudescription, gponoltprofilesid, serviceprofile,
			voipaccountsid1, voipaccountsid2, host_id1, host_id2, creatorid, creationdate, netdevid, xmlprovisioning, properties)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?)',
            array(
                    $gpononudata['name'],
                    $gpononudata['gpononumodelsid'],
                    $gpononudata['password'],
                    isset($gpononudata['autoprovisioning']) ? $gpononudata['autoprovisioning'] : 0,
                    $gpononudata['onu_description'],
                    $gpononudata['gponoltprofilesid'],
                    $gpononudata['serviceprofile'],
                    $gpononudata['voipaccountsid1'],
                    $gpononudata['voipaccountsid2'],
                    $gpononudata['host_id1'],
                    $gpononudata['host_id2'],
                    Auth::GetCurrentUser(),
                    empty($gpononudata['netdevid']) ? null : $gpononudata['netdevid'],
                    isset($gpononudata['xmlprovisioning']) ? $gpononudata['xmlprovisioning'] : 0,
                    $gpononudata['properties'],
            )
        )) {
            $id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONU);
            $dump = var_export($gpononudata, true);
            $this->Log(4, self::SQL_TABLE_GPONONU, $id, 'added '.$gpononudata['name'], $dump);
            return $id;
        } else {
            return false;
        }
    }

    public function GponOnuDescriptionUpdate($id, $onudescription) 
    {
        $onudescription = iconv('UTF-8', 'ASCII//TRANSLIT', $onudescription);
        if (intval($id)) {
            $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET onudescription = ?
				WHERE id = ?', array($onudescription, $id));
            $this->Log(4, self::SQL_TABLE_GPONONU, $id, 'description set: ' . $onudescription);
        }
    }

    public function GponOnuVoipUpdate($id, $port, $voipid)
    {
        if (intval($port)) {
            $colname = 'voipaccountsid' . $port;
            $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET '. $colname .' = ?
				WHERE id = ?', array($voipid, $id));
            $this->Log(4, self::SQL_TABLE_GPONONU, $id, 'voip '.$port.' set: '.$voipid);
        }
    }

    public function GponOnuProfileUpdateByName($id, $profile)
    {
        if (intval($id) && ($pid = $this->DB->GetOne('SELECT id FROM ' . self::SQL_TABLE_GPONOLTPROFILES
            . ' WHERE name = ?', array($profile)))) {
            $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET gponoltprofilesid = ?
				WHERE id=?', array($pid, $id));
            $this->Log(4, self::SQL_TABLE_GPONONU, $id, 'profile set: '.$profile);
        }
    }

    public function GponOnuUpdate($gpononudata)
    {
        $gpononudata['onudescription'] = iconv('UTF-8', 'ASCII//TRANSLIT', $gpononudata['onudescription']);
        $gpononudata['gponoltprofilesid'] = intval($gpononudata['gponoltprofilesid']) ? $gpononudata['gponoltprofilesid']: null;
        $gpononudata['voipaccountsid1'] = intval($gpononudata['voipaccountsid1']) ? $gpononudata['voipaccountsid1']: null;
        $gpononudata['voipaccountsid2'] = intval($gpononudata['voipaccountsid2']) ? $gpononudata['voipaccountsid2']: null;
        $gpononudata['host_id1'] = intval($gpononudata['host_id1']) ? $gpononudata['host_id1']: null;
        $gpononudata['host_id2'] = intval($gpononudata['host_id2']) ? $gpononudata['host_id2']: null;
        $this->DB->Execute(
            'UPDATE ' . self::SQL_TABLE_GPONONU . ' SET gpononumodelsid=?, password=?, autoprovisioning=?,
				onudescription=?, gponoltprofilesid=?, serviceprofile=?, voipaccountsid1=?, voipaccountsid2=?,
				host_id1=?, host_id2=?, netdevid=?, xmlprovisioning=?, properties=?, modid=?, moddate=?NOW?
				WHERE id=?',
            array(
                    intval($gpononudata['gpononumodelsid']),
                    $gpononudata['password'],
                    $gpononudata['autoprovisioning'],
                    $gpononudata['onudescription'],
                    $gpononudata['gponoltprofilesid'],
                    $gpononudata['serviceprofile'],
                    $gpononudata['voipaccountsid1'],
                    $gpononudata['voipaccountsid2'],
                    $gpononudata['host_id1'],
                    $gpononudata['host_id2'],
                    empty($gpononudata['netdevid']) ? null : $gpononudata['netdevid'],
                    $gpononudata['xmlprovisioning'],
                    $gpononudata['properties'],
                    Auth::GetCurrentUser(),
                    $gpononudata['id']
                )
        );
        $dump = var_export($gpononudata, true);
        $this->Log(4, self::SQL_TABLE_GPONONU, $gpononudata['id'], 'updated '.$gpononudata['name'], $dump);
    }

    public function GetGponOnuCheckList($devid = 0, $detect_configured_onus = true)
    {
       // echo $detect_configured_onus;
        //die;
        $olts = $this->GetGponAllOlt($devid);
        if (empty($olts)) {
            return array();
        }

        $onu_list = array();

        foreach ($olts as $olt) {
            $this->snmp->clear_options();
            $gponoltid = null;
            if (!empty($olt) && is_array($olt)) {
                $this->snmp->set_options($olt);
                $olt_name = $olt['name'];
                $olt_netdevicesid = $olt['netdevicesid'];
                $gponoltid = $olt['id'];
            }

            $error_snmp = $this->snmp->get_correct_connect_snmp();
            if (strlen($error_snmp)) {
                $error_snmp .= ' - <b>(' . $olt_name . ')</b><br>';
            }

            if (strlen($error_snmp)) {
                $onu_list[] = array(
                    'olt_name' => $error_snmp,
                );
                continue;
            }
            //.1.3.6.1.4.1.637.61.1.3.7.1.2
            $i = 1;
            $walk = $this->snmp->walk('.1.3.6.1.4.1.637.61.1.3.7.1.2', 'x');
            foreach($walk as $key => $var)
            {
                $var = $this->snmp->clean_snmp_value($var);
                if ($var == "45")
                {
                    $test =  $this->snmp->get('.1.3.6.1.4.1.637.61.1.3.7.1.3.'.$i, 'x');
                    if ($test == "128")
                    {
                        $alarmindex1 = $this->snmp->get('.1.3.6.1.4.1.637.61.1.3.7.1.4.'.$i , 'x');
                        //echo "Alarm Index 1: ".$alarmindex1."\n";
                        $alarmindex2 = $this->snmp->get('.1.3.6.1.4.1.637.61.1.3.7.1.5.'.$i , 'x');
                        //echo "Alarm Index 2: ".$alarmindex2."\n";
                        $ont_sn = $this->snmp->get('.1.3.6.1.4.1.637.61.1.35.11.3.1.3.'.$alarmindex1.'.'.$alarmindex2, 'x');

                        $onu_item = array(
                            'olt_name' => $olt_name,
                            'olt_netdevicesid' => $olt_netdevicesid,
                            'gponoltid' => $gponoltid,
                            'olt_port' => $alarmindex1,
                            'olt_port_formatted' => self::decode_ont_index($alarmindex1),
                            'onu_id' => 0, // zero bo nieskonfigurowane 
                            'onu_serial' => $ont_sn,
                            'onu_exists' => 0);

                            $onu_list[] = $onu_item;
                    }

                }
                $i++;
            }

            if($detect_configured_onus == true)
            {

                $olt_ports = $this->snmp->walk('1.3.6.1.4.1.637.61.1.35.10.4.1.2', 'x'); // ale to pobiera wszystkie onty na olt i teraz trzeba sobie port policzyć
                if (empty($olt_ports) || !is_array($olt_ports)) {
                    continue;
                }

                try {
                    $DB = LMSDB::getInstance(true);
                } catch (Exception $ex) {
                    trigger_error($ex->getMessage(), E_USER_WARNING);
                    // can't work without database
                    die("Fatal error: cannot connect to database!" . PHP_EOL);
                }

                $this->UpdateGponOltProfiles($gponoltid);
                
                foreach ($olt_ports as $port => $olt_port) {
                    $olt_port = $this->snmp->clean_snmp_value($olt_port);
                    $port = end(explode('.', $port));
                        
                    $onus = $this->snmp->walk('1.3.6.1.4.1.637.61.1.35.10.1.1.5.' . $port, 'x');

                    if (empty($onus) || !is_array($onus)) {
                        continue;
                    }

                    foreach ($onus as $onu_idx => $onu) {
                        $onu_id = self::decode_ont_index($port, 'onu_id');
                        $onu_serial = self::decodeOntSerialNumber($this->snmp->clean_snmp_value($onu));
                        $port_decocded = self::decode_ont_index($port, 'port');

                        if (!$this->IsGponOnuSerialConected($olt['id'], self::decode_ont_index($port, 'port'), $onu_id, $onu_serial)) {
                            $onu_item = array(
                                'olt_name' => $olt_name,
                                'olt_netdevicesid' => $olt_netdevicesid,
                                'gponoltid' => $gponoltid,
                                'olt_port' => $port,
                                'olt_port_formatted' => $port_decocded,
                                'onu_id' => $onu_id,
                                'onu_serial' => $onu_serial,
                                'onu_description' => $this->snmp->get('1.3.6.1.4.1.637.61.1.35.10.1.1.24.' . $port, 'x' ),
                                'onu_model' => str_replace('_', '', $this->snmp->get('1.3.6.1.4.1.637.61.1.35.10.1.1.26.'.$port, 'x')),
                                'onu_exists' => 0,
                            );

                            if ($this->GponOnuNameExists($onu_serial)) {
                                $onu_item['onu_exists'] = 1;
                                if ($this->IsGponOnuSerialConectedOtherOlt($olt['id'], $onu_serial)) {
                                    $onu_item['onu_error'] = 1;
                                    $onu_item['onu_error_text'] = 'The ONU is assigned to another OLT in the LMS. You must remove the assignment manually.';
                                } else {
                                    $gpon_onu_in_db = $this->GetGponOnuFromName($onu_serial);
                                    if (!empty($gpon_onu_in_db) && is_array($gpon_onu_in_db)) {
                                        if ($this->snmp->ONU_is_online($port_decocded, $onu_id)) {
                                            $onu_item['onu_exists'] = 2;
                                        } else {
                                            $this->GponOnuUnLinkAll($gpon_onu_in_db['id']);
                                            $this->GponOnuUpdateOnuId($gpon_onu_in_db['id'], $onu_id);
                                            $this->GponOnuLink($olt_netdevicesid, $port_decocded, $gpon_onu_in_db['id']);
                                            $this->snmp->ONU_set_description(
                                                $olt_port,
                                                $onu_id,
                                                $gpon_onu_in_db['onudescription']
                                            );
                                                                                
                                        }
                                    }
                                }
                            }

                            $onu_list[] = $onu_item;
                        }
                    }
                }
            }
        }
     
        return $onu_list;
    }

    public function GetGponAutoScript($debug)
    {
        $output = 'START';
        $onu_list = array();
        $olts = $this->GetGponAllOlt();
        
        $podlaczam = 0;
        if ($debug == 1) {
            $output .= '<br />'.trans('OLT ## Load all OLTs from the database');
        }
    
        if (is_array($olts) && !empty($olts)) {
            $i = 0;
            foreach ($olts as $k => $v) {
                $this->snmp->clear_options();
                $gponoltid = null;
                if (is_array($v) && !empty($v)) {
                    $this->snmp->set_options($v);
                    $olt_name = $v['name'];
                    $olt_netdevicesid = $v['netdevicesid'];
                    $gponoltid = $v['id'];
                }
                $error_snmp=$this->snmp->get_correct_connect_snmp();
                if (strlen($error_snmp)>0) {
                    $error_snmp.=' - <b>('.$olt_name.')</b><br />';
                }
                $output.=$error_snmp;
                if ($debug==1) {
                    $output.='<br />'.trans('OLT-name##').' <b>'.$olt_name.'</b>';
                }

                $sleep = 0;
                while($this->get_bussy($v['id']) == 1) {
                    echo "OLT ".$v['id']." is bussy, waiting..." . PHP_EOL;
                    sleep(1);
                    $sleep++;
                    if ($sleep > 60) {
                        echo "OLT ".$v['id']." is bussy for 60 seconds, exiting..." . PHP_EOL;
                        break;
                    }
                }
                // -------------------- szukanie ONU na OLT --------------------
                $olts_walk = $this->snmp->walk('.1.3.6.1.4.1.637.61.1.3.7.1.2', 'x');
               
                if (is_array($olts_walk) && count($olts_walk)>0) {

                    $i = 1;
                    foreach($olts_walk as $key => $var)
                    {
                        $var = $this->snmp->clean_snmp_value($var);
                      
                        if ($var == "45")
                        {
                            $test =  $this->snmp->get('.1.3.6.1.4.1.637.61.1.3.7.1.3.'.$i, 'x');
        
                            if ($test == "128")
                            {
                                $alarmindex1 = $this->snmp->get('.1.3.6.1.4.1.637.61.1.3.7.1.4.'.$i , 'x');
                                //echo "Alarm Index 1: ".$alarmindex1."\n";
                                $alarmindex2 = $this->snmp->get('.1.3.6.1.4.1.637.61.1.3.7.1.5.'.$i , 'x');
                                //echo "Alarm Index 2: ".$alarmindex2."\n";
                                $onu_serial = $this->snmp->get('.1.3.6.1.4.1.637.61.1.35.11.3.1.3.'.$alarmindex1.'.'.$alarmindex2, 'x');

                                $olt_port=self::decode_ont_index($alarmindex1);

                                if ($debug==1) {
                                    $output.='<br /><b>OLT-snmp-port## '.$olt_port.'</b>';
                                }
                                $onus_walk=$this->snmp->walk('sleGponOnuSerial.'.$olt_port);
                                if ($debug==1) {
                                    $output.='<br />'.trans('ONU-snmp ## Load all ONUs from the OLT port');
                                }
                                
                        $onu_to_olt_db=0;
                        $onu_exists_db=0;
                        $onu_autoprovisioning=0;
                        $onu_autoscript=0;
                        $onu_data=array();
                        
                        $error_onu=0;
                    
                        if ($debug==1) {
                            $output.='<br />ONU-snmp-ONU-serial## <b>'.$onu_serial.'</b>';
                        }
                        if ($this->IsGponOnuSerialConectedOtherOlt($v['id'], $onu_serial)==true) {
                            $error_onu=1;
                            $output.='<br /><font color="red"><b>'.trans('ERROR! - An error occured! ONU').$onu_serial.trans('Is connected to another OLT').'</b></font>';
                        }
                
                        $onu_data=$this->GetGponOnuFromName($onu_serial);
                        if (is_array($onu_data) && count($onu_data)>0) {
                            $onu_exists_db=1;
                            if ($debug==1) {
                                $output.='<br /><font color="blue">'.trans('ONU # There is a database').'</font>';
                            }
                            if (intval($onu_data['autoprovisioning'])==1) {
                                $onu_autoprovisioning=1;
                                if ($debug==1) {
                                    $output.='<br /><font color="blue">'.trans('ONU # issued to the customer').'</font>';
                                }
                                if (intval($onu_data['autoscript'])==1) {
                                    $onu_autoscript=1;
                                    if ($debug==1) {
                                        $output.='<br /><font color="red">'.trans('ONU # Configuration already sent').'</font>';
                                    }
                                }
                            }
                        }
                        if ($error_onu==0 && $onu_to_olt_db==0 && $onu_exists_db==1 && $onu_autoprovisioning==1 && $onu_autoscript==0 && is_array($onu_data) && count($onu_data)>0 && $onu_data['name']==$onu_serial) {
                            if ($debug==1) {
                                $output.='<br />---------------<b>'.$onu_serial.'</b>-------------------------';
                            }
                            if ($debug==1) {
                                $output.='<br /><font color="blue"><b>'.trans('ONU # SNMP - START configuration').'</b></font>';
                            }
                            //var_dump($onu_data);
                            //print_r($onu_data['xgspon']);
                           // die;
                            $xgspon = $this->GetOnuXgsponStatusByName($onu_serial);
                            $swverpland = $this->GetOnuSwverplandByName($onu_serial);
                            // dodajemy onu do olt
                            $onu_id = $this->snmp->ONU_add($olt_port, $onu_serial, '', $onu_data['onudescription'], $onu_data['serviceprofile'], $onu_data['profil_olt'], $xgspon, $onu_data['portdetails'], $swverpland);
              
                            $description=$this->snmp->ONU_set_description($olt_port, $onu_id, $onu_data['onudescription']);
                            if ($debug==1) {
                                $output.='<br />Description:'.$this->GetSNMPresultMsg($description);
                            }

                            /*
                            $phone_data=$this->GetPhoneVoip($onu_data['voipaccountsid1']);
                            $VoIP1=$this->snmp->ONU_SetPhoneVoip($olt_port, $onu_id, 2, 1, $phone_data);
                            if ($debug==1) {
                                $output.='<br />VoIP1: '.$this->GetSNMPresultMsg($VoIP1);
                            }

                            $phone_data=$this->GetPhoneVoip($onu_data['voipaccountsid2']);
                            $VoIP2=$this->snmp->ONU_SetPhoneVoip($olt_port, $onu_id, 2, 2, $phone_data);
                            if ($debug==1) {
                                $output.='<br />VoIP2: '.$this->GetSNMPresultMsg($VoIP2);
                            }

                            $reset=$this->snmp->ONU_Reset($olt_port, $onu_id);
                            if ($debug==1) {
                                $output.='<br />RESET: '.$this->GetSNMPresultMsg($reset);
                            }
                            */
                            $this->GponOnuUnLinkAll($onu_data['id']);
                            $onu_to_olt_db_set=$this->GponOnuLink($olt_netdevicesid, $olt_port, $onu_data['id']);
                            $this->GponOnuUpdateOnuId($onu_data['id'], $onu_id['ONU_id']);
                            $onu_to_olt_db_set=$onu_to_olt_db_set==1?'<b>OK</b>':'<font color="red"><b>ERROR</b></font>';
                            if ($debug==1) {
                                $output.='<br />SET ONU TO OLT:'.$onu_to_olt_db_set;
                            }
                            $this->GponOnuSetAutoScript($onu_data['id']);

                            if ($debug==1) {
                                $output.='<br /><font color="blue"><b>'.trans('ONU # SNMP - CONFIGURE END').'</b></font>';
                                $output.='<br />-------------------------------------------------------';
                            }
                            $podlaczam=1;
                            $output.='<br /><b>'.trans('ONU connected').$onu_serial.trans('On the port OLT').$olt_port.'/'.$onu_id['ONU_id'].'</b>';
                        } else {
                            if ($debug==1) {
                                $output.='<br /><font color="red">'.trans('ONU # condition failed - configuration not sent').'</font>';
                            }
                            if ($onu_to_olt_db==1) {
                                if ($debug==1) {
                                    $output.='<br /><font color="red">'.trans('ONU-TO-OLT # There is an OLT connection to the database').'</font>';
                                }
                            }
                            if ($onu_exists_db==0) {
                                if ($debug==1) {
                                    $output.='<br /><font color="red">'.trans('ONU # There is no database').'</font>';
                                }
                            } else {
                                if (!is_array($onu_data) || count($onu_data)==0) {
                                    if ($debug==1) {
                                        $output.='<br /><font color="red">'.trans('ONU # Data not retrieved from database').'</font>';
                                    }
                                }
                            }
                            if ($onu_autoprovisioning==0) {
                                if ($debug==1) {
                                    $output.='<br /><font color="red">'.trans('ONU # Not delivered to customer').'</font>';
                                }
                            }
                            if ($onu_autoscript==1) {
                                if ($debug==1) {
                                    $output.='<br /><font color="red">'.trans('ONU # Configuration already sent').'</font>';
                                }
                            }



                            if ($debug==1) {
                                $output.='<br />-------------------------------------------------------';
                            }
                                }
                            }
                        } else {
                            if ($debug==1) {
                                $output.='<br /><font color="blue">'.trans('ONU-snmp # NO ONU for OLT').'</font>';
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        if ($podlaczam==0) {
            $output.='<br /><b>'.trans('Nothing was connected').'</b>';
        }
        return array('onu_autoscript_debug' => $output);
                
    }

    public function GetSNMPresultMsg($result_array = array())
    {
        $result = '<b>OK</b>';
        if (is_array($result_array) && count($result_array)) {
            foreach ($result_array as $k => $v) {
                if ($v == false) {
                    $result = '<font color="red"><b>ERROR</b></font>';
                }
            }
        }
        return $result;
    }

    public function GponOnuSetAutoScript($gpononuid, $autoscript = 1)
    {
        if (intval($gpononuid)) {
            $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET autoscript = ?
				WHERE id = ?', array($autoscript, $gpononuid));
            $this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'autoscript set to '.$autoscript);
        }
    }

    public function GetGponAllOlt($olt = 0)
    {
        $where = ' WHERE 1=1';
        if (intval($olt)) {
            $where .= ' AND d.id = ' . $olt;
        }

        $result = $this->DB->GetAll('SELECT g.*, d.name, d.id AS netdevicesid, d.model AS olt_model
			FROM ' . self::SQL_TABLE_GPONOLT . ' g
			JOIN netdevices d ON d.id = g.netdeviceid' . $where);
        return $result;
    }

    public function IsGponOnuSerialConected($gponoltid, $olt_port, $onu_id, $onu_serial)
    {
        return ($this->DB->GetOne(
            "SELECT g2o.* FROM " . self::SQL_TABLE_GPONONU2OLT . " g2o
			JOIN netdevices n ON n.id = g2o.netdevicesid
			JOIN " . self::SQL_TABLE_GPONOLT . " g ON g.netdeviceid = n.id
			JOIN " . self::SQL_TABLE_GPONONU . " go ON go.id = g2o.gpononuid
			WHERE g.id = ? AND g2o.numport = ? AND go.onuid = ? AND go.name = ?",
            array($gponoltid, $olt_port, $onu_id, $onu_serial)
        ) ? true : false);
    }

    public function IsGponOnuSerialConectedOtherOlt($gponoltid, $onu_serial)
    {
        return ($this->DB->GetOne(
            "SELECT g2o.* FROM " . self::SQL_TABLE_GPONONU2OLT . " g2o
			JOIN netdevices n ON n.id=g2o.netdevicesid
			JOIN " . self::SQL_TABLE_GPONOLT . " g ON g.netdeviceid = n.id
			JOIN " . self::SQL_TABLE_GPONONU . " go ON go.id=g2o.gpononuid
			WHERE g.id <> ? AND go.name = ?",
            array($gponoltid, $onu_serial)
        ) ? true : false);
    }

    public function GponGetOnuNameFromOltOnuId($gponoltid, $olt_port, $onu_id)
    {
        $result = $this->DB->GetRow(
            "SELECT go.name FROM " . self::SQL_TABLE_GPONONU2OLT . " g2o
			JOIN netdevices n ON n.id = g2o.netdevicesid
			JOIN " . self::SQL_TABLE_GPONOLT . " g ON g.netdeviceid = n.id
			JOIN " . self::SQL_TABLE_GPONONU . " go ON go.id = g2o.gpononuid
			WHERE g.id = ? AND g2o.numport = ? AND go.onuid = ?",
            array($gponoltid, $olt_port, $onu_id)
        );
        return $result;
    }

    public function GetNotGponOnuDevices($gpononuid = null)
    {
        return $this->DB->GetAll('SELECT d.id, d.name FROM netdevices d
			LEFT JOIN ' . self::SQL_TABLE_GPONONU . ' o ON o.netdevid = d.id
			LEFT JOIN ' . self::SQL_TABLE_GPONOLT . ' oo ON oo.netdeviceid = d.id
			WHERE (o.netdevid IS NULL AND oo.netdeviceid IS NULL) OR o.id = ?
			ORDER BY name', array($gpononuid));
    }

    //--------------ONU_MODELS----------------
    public function GetGponOnuModelsList($order = 'name,asc')
    {
        list ($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY id';
                break;
            case 'producer':
                $sqlord = ' ORDER BY producer';
                break;
            default:
                $sqlord = ' ORDER BY name';
                break;
        }
        $where = ' WHERE 1=1 ';
        $netdevlist = $this->DB->GetAllByKey('SELECT *
			FROM ' . self::SQL_TABLE_GPONONUMODELS . ' g ' . $where
            . ($sqlord != '' ? $sqlord . ' ' . $direction : ''), 'id');

        $netdevlist['total'] = empty($netdevlist) ? 0 : count($netdevlist);
        $netdevlist['order'] = $order;
        $netdevlist['direction'] = $direction;

        return $netdevlist;
    }

    public function GponOnuModelsExists($id)
    {
        return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUMODELS
            . ' WHERE id = ?', array($id)) ? true : false);
    }

    public function CountGponOnuModelsLinks($id)
    {
        return $this->DB->GetOne('SELECT COUNT(*) FROM ' . self::SQL_TABLE_GPONONU
            . ' WHERE gpononumodelsid = ?', array($id));
    }

    public function GetGponOnuModels($id)
    {
        return $this->DB->GetRow('SELECT g.* FROM ' . self::SQL_TABLE_GPONONUMODELS . ' g
			WHERE g.id = ?', array($id));
    }

    public function GponOnuModelsUpdate($gpononumodelsdata)
    {
        $this->DB->Execute(
            'UPDATE ' . self::SQL_TABLE_GPONONUMODELS . ' SET name=?, description=?, producer=?, urltemplate = ?, xmlfilename = ?, xmltemplate=?, xgspon=?, swverpland=?
			WHERE id=?',
            array(
                $gpononumodelsdata['name'],
                $gpononumodelsdata['description'],
                $gpononumodelsdata['producer'],
                empty($gpononumodelsdata['urltemplate']) ? null : $gpononumodelsdata['urltemplate'],
                empty($gpononumodelsdata['xmlfilename']) ? null : $gpononumodelsdata['xmlfilename'],
                $gpononumodelsdata['xmltemplate'],
                isset($gpononumodelsdata['xgspon']) ? $gpononumodelsdata['xgspon'] : 0,
                isset($gpononumodelsdata['swverpland']) ? $gpononumodelsdata['swverpland'] : '',
                $gpononumodelsdata['id']
            )
        );
        $dump = var_export($gpononumodelsdata, true);
        $this->Log(4, self::SQL_TABLE_GPONONUMODELS, $gpononumodelsdata['id'], 'updated ' . $gpononumodelsdata['name'], $dump);
    }

    public function GponOnuModelsAdd($gpononumodelsdata)
    {
        if ($this->DB->Execute(
            'INSERT INTO ' . self::SQL_TABLE_GPONONUMODELS
            . ' (name, description, producer, urltemplate, xmlfilename, xmltemplate, xgspon, swverpland) VALUES (?, ?, ?, ?, ?, ?, ?)',
            array(
                $gpononumodelsdata['name'],
                $gpononumodelsdata['description'],
                $gpononumodelsdata['producer'],
                empty($gpononumodelsdata['urltemplate']) ? null : $gpononumodelsdata['urltemplate'],
                empty($gpononumodelsdata['xnlfilename']) ? null : $gpononumodelsdata['xmlfilename'],
                $gpononumodelsdata['xmltemplate'],
                isset($gpononumodelsdata['xgspon']) ? $gpononumodelsdata['xgspon'] : 0,
                isset($gpononumodelsdata['swverpland']) ? $gpononumodelsdata['swverpland'] : ''
            )
        )) {
            $id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONUMODELS);
            $dump = var_export($gpononumodelsdata, true);
            $this->Log(4, self::SQL_TABLE_GPONONUMODELS, $id, 'added ' . $gpononumodelsdata['name'], $dump);
            return $id;
        } else {
            return false;
        }
    }

    public function GetOnuXgsponStatus($numport, $onuid)
    {
        return $this->DB->GetOne('SELECT m.xgspon
            FROM ' . self::SQL_TABLE_GPONONU2OLT . ' o2o
            JOIN ' . self::SQL_TABLE_GPONONU . ' o ON o2o.gpononuid = o.id
            JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' m ON o.gpononumodelsid = m.id
            WHERE o2o.numport = ? AND o.onuid = ?',
            array($numport, $onuid)
        );
    }

    public function GetOnuXgsponStatusByName($name)
    {
        return $this->DB->GetOne('SELECT m.xgspon
            FROM ' . self::SQL_TABLE_GPONONUMODELS . ' m
            JOIN ' . self::SQL_TABLE_GPONONU . ' o ON o.gpononumodelsid = m.id
            WHERE o.name = ?',
            array($name)
        );
    }    
    
    public function GetOnuSwverplandByName($name)
    {
        return $this->DB->GetOne('SELECT m.swverpland
            FROM ' . self::SQL_TABLE_GPONONUMODELS . ' m
            JOIN ' . self::SQL_TABLE_GPONONU . ' o ON o.gpononumodelsid = m.id
            WHERE o.name = ?',
            array($name)
        );
    }

    public function DeleteGponOnuModels($id)
    {
        $this->DB->BeginTrans();
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUMODELS . ' WHERE id=?', array($id));
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' WHERE gpononumodelsid=?', array($id));
        $this->Log(4, self::SQL_TABLE_GPONONUMODELS, $id, 'model removed');
        $this->DB->CommitTrans();
    }

    public function GetGponOnuModelPorts($model)
    {
        return $this->DB->GetAllByKey("SELECT p.id, p.name, portscount
			FROM " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . " p2m
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . " p ON p.id = p2m.gpononuportstypeid
			JOIN " . self::SQL_TABLE_GPONONUMODELS . " m ON m.id = p2m.gpononumodelsid
			WHERE m.id = ? ORDER BY name", 'name', array($model));
    }

    public function GetGponOnuPorts($id)
    {
        return $this->DB->GetAllByKey("SELECT p.*, t.name, " . $this->DB->Concat('t.name', "'.'", 'p.portid') . " AS portname
			FROM " . self::SQL_TABLE_GPONONUPORTS . " p
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . " t ON t.id = p.typeid
			WHERE p.onuid = ?
			ORDER BY p.typeid, p.portid", 'portname', array($id));
    }

    public function GetGponOnuAllPorts($modelports, $onuports)
    {
        $portsettings = array();
        if (!empty($modelports)) {
            foreach ($modelports as $porttype => $portdetails) {
                for ($i = 1; $i <= $portdetails['portscount']; $i++) {
                    $portname = $porttype . '.' . $i;
                    if (isset($onuports[$portname])) {
                        $portsettings[$portname] = $onuports[$portname];
                    } else {
                        $portsettings[$portname] = array(
                            'onuid' => isset($_GET['id']) ? $_GET['id'] : null,
                            'typeid' => $portdetails['id'],
                            'portid' => $i,
                            'portdisable' => 0,
                            'name' => $porttype,
                            'portname' => $portname,
                        );
                    }
                }
            }
        }
        return $portsettings;
    }

    public function UpdateGponOnuPorts($onu, $portsettings)
    {
        $this->DB->Execute("DELETE FROM " . self::SQL_TABLE_GPONONUPORTS . " WHERE onuid = ?", array($onu));

        if (empty($portsettings)) {
            return;
        }

        $porttypes = $this->DB->GetAllByKey("SELECT * FROM " . self::SQL_TABLE_GPONONUPORTTYPES, 'name');
        foreach ($portsettings as $portname => $port) {
            list ($porttype, $portid) = explode('.', $portname);
            $porttypeid = $porttypes[$porttype]['id'];
            $dbfields = array();
            foreach ($port as $property => $value) {
                switch ($property) {
                    case 'portdisable':
                        if ($value == 1) {
                            $dbfields['portdisable'] = 1;
                        }
                        break;
                }
            }
            if (!empty($dbfields)) {
                $args = array($onu, $porttypeid, $portid);
                $args = array_merge($args, array_values($dbfields));
                $this->DB->Execute("INSERT INTO " . self::SQL_TABLE_GPONONUPORTS
                    . " (onuid, typeid, portid, " . implode(', ', array_keys($dbfields)) . ")
					VALUES (?, ?, ?, " . implode(', ', array_fill(0, count($dbfields), '?')) . ")", $args);
            }
        }
    }

    public function EnableGponOnuPortDB($onu, $porttype, $port)
    {
        if (!($rows = $this->DB->Execute(
            "UPDATE " . self::SQL_TABLE_GPONONUPORTS . " SET portdisable = 0
			WHERE onuid = ? AND typeid = ? AND portid = ?",
            array($onu, $porttype, $port)
        ))) {
            $rows = $this->DB->Execute("INSERT INTO " . self::SQL_TABLE_GPONONUPORTS . " (onuid, typeid, portid, portdisable)
				VALUES(?, ?, ?, 0)", array($onu, $porttype, $port));
        }
        if ($rows) {
            $this->Log(4, self::SQL_TABLE_GPONONU, $onu, 'port enabled: '.$port.', typ: '.$porttype);
        }
    }

    public function DisableGponOnuPortDB($onu, $porttype, $port)
    {
        if (!($rows = $this->DB->Execute(
            "UPDATE " . self::SQL_TABLE_GPONONUPORTS . " SET portdisable = 1
			WHERE onuid = ? AND typeid = ? AND portid = ?",
            array($onu, $porttype, $port)
        ))) {
            $rows = $this->DB->Execute("INSERT INTO " . self::SQL_TABLE_GPONONUPORTS . " (onuid, typeid, portid, portdisable)
				VALUES(?, ?, ?, 1)", array($onu, $porttype, $port));
        }
        if ($rows) {
            $this->Log(4, self::SQL_TABLE_GPONONU, $onu, 'port disabled: '.$port.', typ: '.$porttype);
        }
    }

    public function GetGponOnuPortsType()
    {
        return $this->DB->GetAll('SELECT gpt.* FROM ' . self::SQL_TABLE_GPONONUPORTTYPES . ' gpt
			ORDER BY gpt.id ASC');
    }

    public function GetGponOnuPortsType2Models($gpononumodelid)
    {
        return $this->DB->GetAllByKey(
            'SELECT gpt2m.* FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' gpt2m
			WHERE gpt2m.gpononumodelsid = ?
			ORDER BY gpt2m.gpononuportstypeid ASC',
            'gpononuportstypeid',
            array($gpononumodelid)
        );
    }

    public function SetGponOnuPortsType2Models($gpononumodelid, $porttypes)
    {
        if (intval($gpononumodelid)) {
            $this->DB->BeginTrans();
            $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                . ' WHERE gpononumodelsid=?', array($gpononumodelid));
            if (is_array($porttypes) && count($porttypes)) {
                foreach ($porttypes as $k => $v) {
                    if (intval($v)) {
                        $this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
                        . ' (gpononuportstypeid, gpononumodelsid, portscount)
							VALUES (?, ?, ?)', array(intval($k), $gpononumodelid, $v));
                    }
                }
            }
            $dump = var_export($porttypes, true);
            $this->Log(4, self::SQL_TABLE_GPONONUMODELS, $gpononumodelid, 'ports type updated', $dump);
            $this->DB->CommitTrans();
        }
    }

    //--------------GPON_TV----------------
    public function GetGponOnuTvList($order = 'name,asc')
    {
        list ($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY id';
                break;
            case 'producer':
                $sqlord = ' ORDER BY ipaddr';
                break;
            default:
                $sqlord = ' ORDER BY channel';
                break;
        }
        $where = ' WHERE 1=1 ';
        $netdevlist = $this->DB->GetAll('SELECT g.id,inet_ntoa(g.ipaddr) AS ipaddr, g.channel
			FROM ' . self::SQL_TABLE_GPONONUTV . ' g ' . $where
            .($sqlord != '' ? $sqlord.' '.$direction : ''));

        $netdevlist['total'] = empty($netdevlist) ? 0 : count($netdevlist);
        $netdevlist['order'] = $order;
        $netdevlist['direction'] = $direction;

        return $netdevlist;
    }

    public function GetGponOnuTv($id)
    {
        $result = $this->DB->GetRow('SELECT g.id, INET_NTOA(g.ipaddr) AS ipaddr, g.channel
			FROM ' . self::SQL_TABLE_GPONONUTV . ' g
			WHERE g.id = ?', array($id));
        return $result;
    }

    public function GponOnuTvUpdate($gpononutvdata)
    {
        $this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONUTV . ' SET ipaddr = INET_ATON(?), channel = ?
				WHERE id = ?', array($gpononutvdata['ipaddr'], $gpononutvdata['channel'], $gpononutvdata['id']));
        $this->Log(4, self::SQL_TABLE_GPONONUTV, $gpononutvdata['id'], 'updated: '.$gpononutvdata['channel'].' - '.$gpononutvdata['ipaddr']);
    }

    public function GponOnuTvAdd($gpononutvdata)
    {
        if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUTV . ' (ipaddr,channel)
				VALUES (INET_ATON(?), ?)', array($gpononutvdata['ipaddr'], $gpononutvdata['channel']))) {
            $id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONUTV);
            $this->Log(4, self::SQL_TABLE_GPONONUTV, $id, 'added: '.$gpononutvdata['channel'].' - '.$gpononutvdata['ipaddr']);
            return $id;
        } else {
            return false;
        }
    }

    public function DeleteGponOnuTv($id)
    {
        $this->DB->BeginTrans();
        $this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUTV . ' WHERE id = ?', array($id));
        $this->Log(4, self::SQL_TABLE_GPONONUTV, $id, 'deleted');
        $this->DB->CommitTrans();
    }

    public function GponOnuTvIpExists($ip, $id = 0)
    {
        if (!intval($id)) {
            return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUTV
                . ' WHERE ipaddr = INET_ATON(?)', array($ip)) ? true : false);
        } else {
            return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUTV
                . ' WHERE ipaddr = INET_ATON(?) AND id <> ?', array($ip, $id)) ? true : false);
        }
    }

    public function GponOnuTvExists($id)
    {
        return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUTV
            . ' WHERE id=?', array($id)) ? true : false);
    }

    public function GetGponOnuTvChannel($ipaddr)
    {
        $ipaddr = trim($ipaddr);
        $result = $this->DB->GetRow("SELECT g.channel
			FROM " . self::SQL_TABLE_GPONONUTV . " g
			WHERE g.ipaddr = INET_ATON(?)", array($ipaddr));
        return $result;
    }

    public function IsGponOnuTvMulticast($ipaddr)
    {
        $address = explode('.', $ipaddr);
        return is_array($address) && count($address) && intval($address[0]);
    }

  
    public function GetGponOnuLastAuth($onuid)
    {
        //return $this->DB->GetAll("SELECT * FROM " . self::SQL_TABLE_GPONAUTHLOG
        //    . " WHERE onuid = ? ORDER BY time DESC", array($onuid));
    }

    public function ListServiceProfiles($oltid = null)
    {
        if (($dh = @opendir(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME
            . DIRECTORY_SEPARATOR . 'gponserviceprofiles')) === false) {
            return null;
        }
        $fnames = array();
        while (($fname = readdir($dh)) !== false) {
            if (!preg_match('/^\./', $fname)) {
                if ($oltid) {
                    if (preg_match('/^OLT(?<oltid>[0-9]+)_.+$/', $fname, $m)) {
                        if (intval($m['oltid']) == $oltid) {
                            $fnames[] = $fname;
                        }
                    } else {
                        $fnames[] = $fname;
                    }
                } else {
                    $fnames[] = $fname;
                }
            }
        }
        closedir($dh);
        sort($fnames, SORT_LOCALE_STRING);
        return $fnames;
    }

    public function LoadServiceProfile($name, $model)
    {
        $fname = PLUGINS_DIR . '/' . LMSGponNokiaPlugin::PLUGIN_DIRECTORY_NAME . '/gponserviceprofiles/' . $name;
        if (!is_file($fname)) {
            return 1;
        }
        if (($ini = @parse_ini_file($fname, true)) === false) {
            return 2;
        }
        $config['vlans'] = array();
        $config['ports']['tagged'] = array();
        $config['ports']['untagged'] = array();
        //$config['ports']['locked'] = array();
        //$config['ports']['dhcp_external'] = array();
        //$config['ports']['router'] = array();
        //$config['ports']['hybrid'] = array();
        $config['ports']['access'] = array();

        foreach ($ini as $section => $values) {

            if (isset($values['vlanid'])) {
                $vlanRange = strpos($values['vlanid'], '-') !== false;
                if (!$vlanRange) {
                    $vlanid = intval(isset($values['vlanid']) ? $values['vlanid'] : $values['cvlanid']);
                } else {
                    $vlanid = $values['vlanid'];
                }
            }

            $config['vlans'][$vlanid] = array(
                'priority' => (isset($values['priority']) ? intval($values['priority']) : 0),
                'type' => ($router ? $values['type'] : 'bridge'),
            );

            if ($vlanRange) {
                list ($startVlan, $endVlan) = explode('-', $vlanid);
                $config['vlans'][$vlanid]['start_vlan'] = $startVlan;
                $config['vlans'][$vlanid]['end_vlan'] = $endVlan;
            }

            $multicast = isset($values['multicast']) && ConfigHelper::checkValue($values['multicast']);
            $iphost = isset($values['iphost']) && ConfigHelper::checkValue($values['iphost']);
            $config['vlans'][$vlanid] = array(
                'multicast' => $multicast,  'iphost' => $iphost);           
            
            if($iphost == 1)
            {
                $config['iphost_vlan'] = $vlanid;
            }
      

            foreach (array('tagged', 'untagged') as $porttype) {
                if (isset($values[$porttype]) && preg_match('/^([0-9]+,)*[0-9]+$/', $values[$porttype])) {
                    $ports = explode(',', $values[$porttype]);
                    foreach ($ports as $idx => $val) {
                        $port = intval($val);
                        if (!isset($config['ports'][$porttype][$port])) {
                            $config['ports'][$porttype][$port] = array();
                        }
                        $config['ports'][$porttype][$port][] = $vlanid;
                        
                        /*
                        // dla nokii niepotrzebne
                        if ($porttype == 'untagged') {
                            if (isset($config['ports']['tagged'][$port])) {
                                $config['ports']['hybrid'][$port] = intval($vlanid);
                            } else {
                                $config['ports']['access'][$port] = intval($vlanid);
                            }
                        } else {
                            if (isset($config['ports']['access'][$port])) {
                                $config['ports']['hybrid'][$port] = $config['ports']['access'][$port];
                                unset($config['ports']['access'][$port]);
                            }
                        }*/
                    }
                }
            }
        }
        return $config;
    }
}
