<?php

use PHPUnit\Event\Runtime\PHP;

use function PHPUnit\Framework\isArray;

class GPON_NOKIA_SNMP
{
    private $GPON;
    private $options = array();
    private $error = array();
    private $path_OID='';

    public function __construct($options, &$GPON)
    {
        $this->GPON = &$GPON;
        $this->set_options($options);
        //@snmp_read_mib('...');
       
        if (defined('SNMP_OID_OUTPUT_MODULE')) {
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_MODULE);
        }
        
    }

    public function set_options($options)
    {
        if (is_array($options) && count($options)>0) {
            $this->options=$options;
            if (!isset($this->options['snmp_privacy_passphrase']) || strlen($this->options['snmp_privacy_passphrase'])==0) {
                if (isset($this->options['snmp_password'])) {
                    $this->options['snmp_privacy_passphrase']=$this->options['snmp_password'];
                }
            }
        }
        return $this->test_options();
    }
    public function get_options($key)
    {
        $key=trim($key);
        if (strlen($key)>0 && is_array($this->options) && count($this->options)>0) {
            if (isset($this->options[$key])) {
                return $this->options[$key];
            }
        }
    }
    public function clear_options()
    {
        if (is_array($this->options) && count($this->options)>0) {
            $this->options=array();
        }
    }
    public function test_options()
    {
        $result='';
        if (is_array($this->options) && count($this->options)>0) {
            if (isset($this->options['snmp_version'])) {
                switch ($this->options['snmp_version']) {
                    case 1:
                        if (!isset($this->options['snmp_community'])) {
                            $result=trans('Invalid parameter: community SNMP');
                        }
                        break;
                    case 2:
                        if (!isset($this->options['snmp_community'])) {
                            $result=trans('Invalid parameter: community SNMP');
                        }
                        break;
                    case 3:
                        if (!isset($this->options['snmp_username']) || strlen($this->options['snmp_username'])==0) {
                            $result=trans('Invalid parameter: security name (username) SNMP');
                        }
                        if (!isset($this->options['snmp_sec_level']) || strlen($this->options['snmp_sec_level'])==0) {
                            $result=trans('Invalid parameter: SNMP security level');
                        }
                        if (!isset($this->options['snmp_auth_protocol']) || strlen($this->options['snmp_auth_protocol'])==0) {
                            $result=trans('Invalid parameter: SNMP authentication protocol');
                        }
                        if (!isset($this->options['snmp_password']) || strlen($this->options['snmp_password'])==0) {
                            $result=trans('Invalid parameter: SNMP authentication pass phrase');
                        }
                        if (!isset($this->options['snmp_privacy_protocol']) || strlen($this->options['snmp_privacy_protocol'])==0) {
                            $result=trans('Invalid parameter: privacy protocol SNMP');
                        }
                        if (!isset($this->options['snmp_privacy_passphrase']) || strlen($this->options['snmp_privacy_passphrase'])==0) {
                            $result=trans('Invalid parameter: privacy pass phrase SNMP');
                        }
                        break;
                    default:
                        $result=trans('Invalid parameter: SNMP version');
                        break;
                }
                if (!isset($this->options['snmp_host']) || strlen($this->options['snmp_host'])==0) {
                    $result=trans('Invalid parameter: SNMP host');
                }
            }
        } else {
            $result=trans('No SNMP parameter');
        }

        return $result;
    }
    public function get_correct_connect_snmp()
    {
        $result='';
        $GponOltName=$this->get('sysName.0', 'SNMPv2-MIB::');
        $GponOltName=trim($GponOltName);
        if (strlen($GponOltName)==0) {
            $result='<br /><font color="red"><b>'.trans('Connection error via SNMP! Check SNMP configuration for OLT.').'</b></font><br />';
        }
        return $result;
    }
    public function parse_result_error($result = array())
    {
        $result=array_unique($result);
        $error=array();
        if (is_array($result) && count($result)>0) {
            foreach ($result as $k => $v) {
                if ($v===false) {
                    $last_error_snmp=error_get_last();
                    if (isset($last_error_snmp['message'])) {
                        $error[]=trans('Error').': '.$last_error_snmp['message'];
                    } else {
                        $error[]=trans('Error');
                    }
                }
            }
        }
        $error=array_unique($error);
        return implode('<br />', $error);
    }
    public function strToHex($string, $lenght = 20)
    {
        $hex='';
        for ($i=0; $i < strlen($string); $i++) {
            $hex .= dechex(ord($string[$i]));
        }
        $lenght=intval($lenght);
        if ($lenght>0) {
            if ($lenght>strlen($hex)) {
                $hex.=str_repeat('0', $lenght-strlen($hex));
            }
        }
        return $hex;
    }
    public function hexToStr($hex)
    {
        $hex = str_replace(' ', '', $hex);
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= ctype_xdigit($hex[$i]) && ctype_xdigit($hex[$i + 1]) ? chr(hexdec($hex[$i] . $hex[$i + 1])) : $hex[$i] . $hex[$i + 1];
        }
        return trim($string);
    }
    public function search_array_key($data_array, $key)
    {
        if (is_array($data_array) && count($data_array)>0) {
            foreach ($data_array as $k => $v) {
                if (preg_match('/'.$key.'/', $k)) {
                    return true;
                }
            }
        }
        return false;
    }
    public function search_array_key_value($data_array, $key)
    {
        if (is_array($data_array) && count($data_array)>0) {
            foreach ($data_array as $k => $v) {
                if (preg_match('/'.$key.'/', $k)) {
                    return $this->clean_snmp_value($v);
                }
            }
        }
    }
    public function clean_snmp_value($value)
    {
        if (!isset($value)) {
            return $value;
        }

        $value=str_replace('INTEGER: ', '', $value);
        $value=str_replace('STRING: ', '', $value);
        $value=str_replace('Counter64: ', '', $value);
        $value=str_replace('Gauge32: ', '', $value);
        $value=str_replace('BITS: ', '', $value);
        $value=str_replace('Hex-STRING: ', '', $value);
        $value=str_replace('Hex-', '', $value);
        $value=str_replace('"', '', $value);
        $value=str_replace('IpAddress: ', '', $value);
        $value=str_replace('IpAddress:', '', $value);
        $value=str_replace('(1)', '', $value);
        $value=str_replace('(2)', '', $value);
        $value=str_replace('Wrong Type (should be Gauge32 or Unsigned32): ', '', $value);
        $value=$this->convert_snmp_value($value);
        //$value=str_replace(' ', '&nbsp;', $value);
        return $value;
    }
    public function convert_snmp_value($value)
    {
        if (preg_match('/0.1dBm/', $value)) {
            $tmp=explode(' ', $value);
            if (is_array($tmp) && count($tmp)>0) {
                if (isset($tmp[0]) && isset($tmp[1])) {
                    $tmp[0]=$tmp[0]*0.1;
                    $value=$tmp[0].'dBm';
                }
            }
        }
        if (preg_match('/1m/', $value)) {
            $tmp=explode(' ', $value);
            if (is_array($tmp) && count($tmp)>0) {
                if (isset($tmp[0]) && isset($tmp[1])) {
                    $value=$tmp[0].'m';
                }
            }
        }
        if (preg_match('/1 sec/', $value)) {
            $tmp=explode(' ', $value);
            if (is_array($tmp) && count($tmp)>0) {
                if (isset($tmp[0]) && isset($tmp[1])) {
                    $dni = floor($tmp[0]/60/60/24);
                    $godzin = floor(($tmp[0]/60/60)-($dni*24));
                    $minut = floor(($tmp[0]/60)-($dni*24*60)-($godzin*60));
                    $sekund = floor($tmp[0]-($dni*24*60*60)-($godzin*60*60)-($minut*60));
                    $value = "$dni d, ".str_pad($godzin, 2, "0", STR_PAD_LEFT).":".str_pad($minut, 2, "0", STR_PAD_LEFT).":".str_pad($sekund, 2, "0", STR_PAD_LEFT);
                    //$value=$tmp[0].'sec';
                }
            }
        }
        if (preg_match('/1 hour/', $value)) {
            $tmp=explode(' ', $value);
            if (is_array($tmp) && count($tmp)>0) {
                if (isset($tmp[0]) && isset($tmp[1])) {
                    $value=$tmp[0].'h';
                }
            }
        }

        return $value;
    }
    public function color_snmp_value($value)
    {
        if ($value == 0) {
            $valuetxt = "Aktywny";
        } elseif ($value & (1 << 14)) {
            $valuetxt =  "Nieaktywny";
        } elseif ($value & (1 << 11)) {
            $valuetxt =  "Wyłączony";
        } else {
            $valuetxt =  "Aktywny z alarmami";
        }

        if ($value & (1 << 0)) {
            $valuetxt .=  " LOS";
        } 
        if ($value & (1 << 17)) {
            $valuetxt .=  " DG";
        } 
        switch ($valuetxt) {
            case 'DG':
                $color='#CC0000';
                break;
            case 'LOS':
                $color='#CC0000';
                break;
            case 'Aktywny':
                $color='#00CC00';
                break;
            case 'Aktywny z alarmami':
                $color='#00CC00';
                break;
            default:
                $color='#000000';
                break;
        }
        return $value='<font color="'.$color.'">'.$valuetxt.'</font> ';
        //return $value='<font color="'.$color.'">'.$valuetxt.'</font> '. $value;
    }
    public function get_path_OID($path_OID = '')
    {
        $path_OID=trim($path_OID);
        if (strlen($path_OID)>0) {
            return $path_OID;
        } else {
            return $this->path_OID;
        }
    }
    public function get_max_last($array_data, $text_cut)
    {
        if (is_array($array_data) && count($array_data)>0) {
            foreach ($array_data as $k => $v) {
                if (preg_match('/'.$text_cut.'/', $k)) {
                    $keys[]=str_replace($text_cut, '', $k);
                }
            }
            return max($keys);
        } else {
            return 0;
        }
    }

    public function get_min_free($OLT_numport)
    {
        for($i=1;$i<=128;$i++)
        {
            
            $ONU_index = $this->calc_ont_index($OLT_numport.'/'.$i);
     
                $result = $this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.5.'.$ONU_index);
                if($result == '')
                {
                   return $i;
                }
        }
        return 0;
    }

    public function walk($OID, $path_OID = '')
    {
        
        $result=false;
        if($path_OID!='x')
        {
            $path_OID=$this->get_path_OID($path_OID);
            $OID=$path_OID.$OID;
        }

        if (strlen($this->test_options())==0) {
            switch ($this->get_options('snmp_version')) {
                case 1:
                    $result=@snmpwalk($this->get_options('snmp_host'), $this->get_options('snmp_community'), $OID);
                    break;
                case 2:
                    $result=@snmp2_real_walk($this->get_options('snmp_host'), $this->get_options('snmp_community'), $OID);
                    break;
                case 3:
                    $result=@snmp3_real_walk($this->get_options('snmp_host'), $this->get_options('snmp_username'), $this->get_options('snmp_sec_level'), $this->get_options('snmp_auth_protocol'), $this->get_options('snmp_password'), $this->get_options('snmp_privacy_protocol'), $this->get_options('snmp_privacy_passphrase'), $OID);
                    break;
                default:
                    break;
            }
        }
        return $result;
    }

    public function set($OID, $type, $value, $path_OID = '')
    {
        $result=false;
        if($path_OID!='x')
        {
            $path_OID=$this->get_path_OID($path_OID);
            $OID=$path_OID.$OID;
        }
        if (strlen($this->test_options())==0) {
            switch ($this->get_options('snmp_version')) {
                case 1:
                    $result=@snmpset($this->get_options('snmp_host'), $this->get_options('snmp_community'), $OID, $type, $value);
                    break;
                case 2:
                    $result=@snmp2_set($this->get_options('snmp_host'), $this->get_options('snmp_community'), $OID, $type, $value);
                    break;
                case 3:
                    $result=@snmp3_set($this->get_options('snmp_host'), $this->get_options('snmp_username'), $this->get_options('snmp_sec_level'), $this->get_options('snmp_auth_protocol'), $this->get_options('snmp_password'), $this->get_options('snmp_privacy_protocol'), $this->get_options('snmp_privacy_passphrase'), $OID, $type, $value);
                    break;
                default:
                    break;
            }
        }
        return $result;
    }

    public function cli_result_to_snmp_result($cli_result)
    {
        switch ($cli_result) {
            case 0:
                $snmp_result = true;
                break;
            case 1:
                $snmp_result = trans('Unknown Object Identifier');
                break;
            case 2:
                $snmp_result = trans('Error in packet. Reason: undoFailed');
                break;
            default:
                $snmp_result = $cli_result;
        }
        return $snmp_result;
    }

    public function set_CLI($OID, $type, $value, $path_OID = '')
    {
        //print_r($OID);
        $result = false;
        if($path_OID!='x')
        {
            $path_OID=$this->get_path_OID($path_OID);
        }
        else
        {
            $path_OID = '';
        }

        $cmd = ConfigHelper::getConfig('gpon-nokia.snmpset_command', '/usr/bin/snmpset') . ' -v';
        switch ($this->get_options('snmp_version')) {
            case 1:
                $cmd .= '1 -c ' . $this->get_options('snmp_community') . ' ' . $this->get_options('snmp_host') . ' ';
                break;
            case 2:
                $cmd .= '2c -c ' . $this->get_options('snmp_community') . ' ' . $this->get_options('snmp_host') . ' ';
                break;
            case 3:
                $cmd .= '3 -l ' . $this->get_options('snmp_sec_level') . ' -a ' . $this->get_options('snmp_auth_protocol')
                    . ' -u ' . $this->get_options('snmp_username') . ' -A ' . $this->get_options('snmp_password')
                    . ' -x ' . $this->get_options('snmp_privacy_protocol') . ' -X ' . $this->get_options('snmp_privacy_passphrase')
                    . ' ' . $this->get_options('snmp_host') . ' ';
                break;
        }
        if (is_array($OID)) {
            foreach ($OID as $key => $id) {
                $cmd .= $path_OID . $id . ' ' . $type[$key] . ' \'' . $value[$key] . '\' ';
            }
        } else {
            $cmd .= $path_OID . $OID . ' ' . $type . ' \'' . $value . '\'';
        }
        exec($cmd, $output, $ret);
        $result = $this->cli_result_to_snmp_result($ret);
        return $result;
    }

    public function get($OID, $path_OID = '')
    {
        $result=false;
        if($path_OID!='x')
        {
            $path_OID=$this->get_path_OID($path_OID);
            $OID=$path_OID.$OID;
        }
        if (strlen($this->test_options())==0) {
            switch ($this->get_options('snmp_version')) {
                case 1:
                    $result=@snmpget($this->get_options('snmp_host'), $this->get_options('snmp_community'), $OID);
                    break;
                case 2:
                    $result=@snmp2_get($this->get_options('snmp_host'), $this->get_options('snmp_community'), $OID);
                    break;
                case 3:
                    $result=@snmp3_get($this->get_options('snmp_host'), $this->get_options('snmp_username'), $this->get_options('snmp_sec_level'), $this->get_options('snmp_auth_protocol'), $this->get_options('snmp_password'), $this->get_options('snmp_privacy_protocol'), $this->get_options('snmp_privacy_passphrase'), $OID);
                    break;
                default:
                    break;
            }
        }
        return $this->clean_snmp_value($result);
    }
    //--------------
    public function ONU_delete($OLT_id, $ONU_id) //ok
    {
        $result=array();
        $OLT_id=$OLT_id;
        $ONU_id=intval($ONU_id);

        echo 'ONU delete: '.$OLT_id.'/'.$ONU_id.'<br />';
       
        if ($OLT_id!='' && $ONU_id>0) {
            
 
            $ont_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
            
            $oid = array();
            $type = array();
            $value = array();
            // .1.3.6.1.2.1.2.2.1.7.${ONTID} i 1 # (1 - up, 2 - down)
            $oid[0] = ".1.3.6.1.2.1.2.2.1.7.".$ont_index;
            $type[0] = "i";
            $value[0] = 2;
            // .1.3.6.1.4.1.637.61.1.35.10.1.1.2.${ONTID} i 4 # Ont (4 - create, 6 - destroy)
            $oid[1] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.2.".$ont_index;
            $type[1] = "i";
            $value[1] = 6;

            $result[] = $this->set_CLI($oid, $type, $value, 'x');

            $this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Deleted Onu '.$ONU_id.', olt '.$OLT_id);
            
        }
        return array_unique($result);
    }

    public function ONU_add($OLT_numport, $ONU_name, $ONU_password = '', $ONU_description = '', $serviceprofile, $qosprofile = '', $xgspon, $portdetails, $swverpland = '') //ok
    {
        $result = array();
        $OLT_numport = $OLT_numport;
        $ONU_name = trim($ONU_name);
      
        if($xgspon == '' or $xgspon == '0' or $xgspon == 'false')
        {
            $xgspon = false;
        }
        if($xgspon == '1' or $xgspon == 'true')
        {
            $xgspon = true;
        }
        
       
        $ONU_description = trim($ONU_description);
        if ($OLT_numport && strlen($ONU_name) == 12) {
           
            $ONU_id = intval($this->get_min_free($OLT_numport));
            if (!$ONU_id) {
                $ONU_id = 1;
            }
            
            if ($ONU_id) {
                // to juz dodajemy onu !!!!!!!!!!!!!! ***************** !!!!!!!!!!!!!!!!!!!
                $config = $this->GPON->LoadServiceProfile($serviceprofile, '');

                $ont_index = self::calc_ont_index($OLT_numport.'/'.$ONU_id, $xgspon);
                $eth_slot = self::calc_eth_slot($OLT_numport.'/'.$ONU_id, $xgspon);
             
                $oid = array();
                $type = array();
                $value = array();
                $sn_hex = bin2hex(substr($ONU_name, 0,4)).substr($ONU_name, 4,8);

                # Dodanie ONT (jako jeden set)
                // .1.3.6.1.4.1.637.61.1.35.10.1.1.2.${ONTID} i 4 #  (4 - create, 6 - destroy)
                $oid[0] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.2.".$ont_index;
                $type[0] = "i";
                $value[0] = 4;

                // .1.3.6.1.4.1.637.61.1.35.10.1.1.5.${ONTID} x 4343434300000003 # Ont lNumber (hex)
                $oid[1] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.5.".$ont_index;
                $type[1] = "x";
                $value[1] = $sn_hex;

                    // .1.3.6.1.4.1.637.61.1.35.10.1.1.11.${ONTID} s "DISABLED" # (wersja softu jako string)
                $oid[2] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.11.".$ont_index;
                $type[2] = "s";
                if(trim($swverpland) == '' or $swverpland == null)
                {
                    $value[2] = "DISABLED";
                }
                else
                {
                    $walksw = $this->walk('1.3.6.1.4.1.637.61.1.35.26.2.1.2', 'x');
                    foreach($walksw as $key => $var)
                    {
                        $var = $this->clean_snmp_value($var);
                        if ($var == $swverpland)
                        {
                            $value[2] = $swverpland;
                            break;
                        }       
                    }           
                }

                // .1.3.6.1.4.1.637.61.1.35.10.1.1.39.${ONTID} i 1 # FEC Upstream (1 - enabled, 2 - disabled)
                $oid[3] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.39.".$ont_index;
                $type[3] = "i";
                $value[3] = 1;

                // .1.3.6.1.4.1.637.61.1.35.10.1.1.75.${ONTID} i 1 #  (0 - disabled, 1 - enabled)
                $oid[4] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.75.".$ont_index;
                $type[4] = "i";
                $value[4] = 1;

                if ($xgspon == true)
                {
                    // .1.3.6.1.4.1.637.61.1.35.10.1.1.122.${ONTID} i 1 #  (0 - disabled, 1 - enabled)
                    $oid[5] = ".1.3.6.1.4.1.637.61.1.35.10.1.1.122.".$ont_index;
                    $type[5] = "i";
                    $value[5] = 2;
                }

                $result[] = $this->set_CLI($oid, $type, $value, 'x');  // ***********************


                
                $oid = array();
                $type = array();
                $value = array();
                # Dodawanie slotu (jako jeden set)
                // .1.3.6.1.4.1.637.61.1.35.10.2.1.3.${ONTID}.${ETHSLOT} i 4 # Card Status (4 - create, 6 - destroy)
                $oid[0] = ".1.3.6.1.4.1.637.61.1.35.10.2.1.3.".$ont_index.".".$eth_slot;
                $type[0] = "i";
                $value[0] = 4;

                // .1.3.6.1.4.1.637.61.1.35.10.2.1.4.${ONTID}.${ETHSLOT} i 24 # Card Type (24 - Ethernet)
                $oid[1] = ".1.3.6.1.4.1.637.61.1.35.10.2.1.4.".$ont_index.".".$eth_slot;
                $type[1] = "i";
                $value[1] = 24;

                if(!isset($portdetails['eth']['portscount'])) // ilość portwów ETH z konfiguracji modelu ont
                {
                    $eth_port_count = 1;
                }
                else
                {
                    $eth_port_count = $portdetails['eth']['portscount'];
                }

                // .1.3.6.1.4.1.637.61.1.35.10.2.1.10.${ONTID}.${ETHSLOT} i 1 #  (ile portów typu data)
                $oid[2] = ".1.3.6.1.4.1.637.61.1.35.10.2.1.10.".$ont_index.".".$eth_slot;
                $type[2] = "i";
                $value[2] = $eth_port_count;

                // .1.3.6.1.4.1.637.61.1.35.10.2.1.11.${ONTID}.${ETHSLOT} i 0 #  (ile portów typu pots)
                $oid[3] = ".1.3.6.1.4.1.637.61.1.35.10.2.1.11.".$ont_index.".".$eth_slot;
                $type[3] = "i";
                $value[3] = 0;

                // .1.3.6.1.4.1.637.61.1.35.10.2.1.6.${ONTID}.${ETHSLOT} i 0 # Admin State (0 - unlock, 1 - lock)
                $oid[4] = ".1.3.6.1.4.1.637.61.1.35.10.2.1.6.".$ont_index.".".$eth_slot;
                $type[4] = "i";
                $value[4] = 0;

                $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************


                # QoS
                $walkqos = $this->walk('.1.3.6.1.4.1.637.61.1.47.3.24.1.2', 'x');
                $qosid = 1; // default qos
                
                foreach($walkqos as $key => $var)
                {
                    $var = $this->clean_snmp_value($var);
                    if ($var == $qosprofile)
                    {
                        $key = explode('47.3.24.1.2.', $key);
                        $key = $key[1];
                        $qosid = $key;
                        break;
                    }
                }

                foreach($config['ports']['untagged'] as $port => $vlanindex)
                {
                    $eth_index = self::calc_eth_index($OLT_numport.'/'.$ONU_id, $xgspon, $port);

                    echo 'port: '.$port.' '.$eth_index.'<br />';

                    $oid = array();
                    $type = array();
                    $value = array();
                    # Status portu ETH
                    // .1.3.6.1.2.1.2.2.1.7.${ETHINDEX} i 1 #  (1 - up, 2 - down)
                    $oid[0] = ".1.3.6.1.2.1.2.2.1.7.".$eth_index;
                    $type[0] = "i";
                    $value[0] = 1;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************

                    $oid = array();
                    $type = array();
                    $value = array();
                    // .1.3.6.1.4.1.637.61.1.47.6.1.1.28.${ETHINDEX} i 5 #  (BandwidthProfile id dla całego interfejsu)
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.47.6.1.1.28.".$eth_index;
                    $type[0] = "i";
                    $value[0] = $qosid;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************

                    $oid = array();
                    $type = array();
                    $value = array();
                    # Bridge Port
                    // .1.3.6.1.4.1.637.61.1.31.2.25.1.2.${ETHINDEX} i 4 # bridge portu (4 - create, 6 - destroy)
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.31.2.25.1.2.".$eth_index;
                    $type[0] = "i";
                    $value[0] = 4; 

                    $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************
                }

                foreach($config['ports']['tagged'] as $port => $vlanindex)
                {
                    $eth_index = self::calc_eth_index($OLT_numport.'/'.$ONU_id, $xgspon, $port);

                    echo 'port: '.$port.' '.$eth_index.'<br />';

                    $oid = array();
                    $type = array();
                    $value = array();
                    # Status portu ETH
                    // .1.3.6.1.2.1.2.2.1.7.${ETHINDEX} i 1 #  (1 - up, 2 - down)
                    $oid[0] = ".1.3.6.1.2.1.2.2.1.7.".$eth_index;
                    $type[0] = "i";
                    $value[0] = 1;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************

                    $oid = array();
                    $type = array();
                    $value = array();
                    // .1.3.6.1.4.1.637.61.1.47.6.1.1.28.${ETHINDEX} i 5 #  (BandwidthProfile id dla całego interfejsu)
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.47.6.1.1.28.".$eth_index;
                    $type[0] = "i";
                    $value[0] = $qosid;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************

                    $oid = array();
                    $type = array();
                    $value = array();
                    # Bridge Port
                    // .1.3.6.1.4.1.637.61.1.31.2.25.1.2.${ETHINDEX} i 4 # bridge port (4 - create, 6 - destroy)
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.31.2.25.1.2.".$eth_index;
                    $type[0] = "i";
                    $value[0] = 4; 

                    $result[] = $this->set_CLI($oid, $type, $value, 'x'); // ***********************
                }

                # VLANy (można jako jeden set albo później osobnym setem zmienić typ)
                foreach($config['ports']['untagged'] as $port => $vlanindex)
                {      
                    $bridgeport = self::calc_bridgeport($OLT_numport.'/'.$ONU_id, $xgspon, $port);
                    $oid = array();
                    $type = array();
                    $value = array();
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.31.2.12.1.2.".$bridgeport.".".$vlanindex[0]; //tworzenie vlana (vlan portu) (4 - create, 6 - destroy)
                    $type[0] = "i";
                    $value[0] = 4;
                    $oid[1] = ".1.3.6.1.4.1.637.61.1.31.2.12.1.4.".$bridgeport.".".$vlanindex[0]; // rodzaj tagowania vlana (1 - untag, 2 - single tag, 3 - priority tag)
                    $type[1] = "i";
                    $value[1] = 1;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x');

                    $oid = array();
                    $type = array();
                    $value = array();
                    # PVID
                    // .1.3.6.1.2.1.17.7.1.4.5.1.1.${BRIDGEPORTID} u vlan # pvid VLANINDEX
                    $oid[0] = ".1.3.6.1.2.1.17.7.1.4.5.1.1.".$bridgeport;
                    $type[0] = "u";
                    $value[0] = $vlanindex[0];

                    $result[] = $this->set_CLI($oid, $type, $value, 'x');

                    $oid = array();
                    $type = array();
                    $value = array();
                    // .1.3.6.1.4.1.637.61.1.31.2.5.1.3.${BRIDGEINDEX} // lmit mac
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.31.2.5.1.3.".$bridgeport;
                    $type[0] = "u";
                    $value[0] = 10;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x');

                    if($config['vlans'][$vlanindex[0]]['multicast'] == 1)
                    {
                        $vporteth = $this->get('.1.3.6.1.4.1.637.61.1.31.2.12.1.9.'.$bridgeport.'.'.$vlanindex[0] , 'x');

                        $oid = array();
                        $type = array();
                        $value = array();

                        // pbit dla MC
                        $oid[0] = '.1.3.6.1.4.1.637.61.1.29.59.1.31.'.$vporteth;
                        $type[0] = 'i';
                        $value[0] = 4;

                        // IGMP max num group
                        $oid[1] = '.1.3.6.1.4.1.637.61.1.29.59.1.9.'.$vporteth;
                        $type[1] = 'i';
                        $value[1] = 16;

                        // IGMP version
                        $oid[2] = '.1.3.6.1.4.1.637.61.1.29.59.1.18.'.$vporteth;
                        $type[2] = 'i';
                        $value[2] = 2;

                        // IGMP max msg rate
                        $oid[3] = '.1.3.6.1.4.1.637.61.1.29.59.1.3.'.$vporteth;
                        $type[3] = 'i';
                        $value[3] = 16;

                        $result[] = $this->set_CLI($oid, $type, $value, 'x');
                    }
                }
                // tagowane vlany
                foreach($config['ports']['tagged'] as $port => $vlanindexs)
                {      
                    foreach($vlanindexs as $vlanindex)
                    {                 
                        $bridgeport = self::calc_bridgeport($OLT_numport.'/'.$ONU_id, $xgspon, $port);
                        $oid = array();
                        $type = array();
                        $value = array();
                        $oid[0] = ".1.3.6.1.4.1.637.61.1.31.2.12.1.2.".$bridgeport.".".$vlanindex; //tworzenie vlana (vlan portu) (4 - create, 6 - destroy)
                        $type[0] = "i";
                        $value[0] = 4;
                        $oid[1] = ".1.3.6.1.4.1.637.61.1.31.2.12.1.4.".$bridgeport.".".$vlanindex; //rodzaj tagowania vlana (1 - untag, 2 - single tag, 3 - priority tag)
                        $type[1] = "i";
                        $value[1] = 2;

                        $result[] = $this->set_CLI($oid, $type, $value, 'x');

                        if($config['vlans'][$vlanindex]['multicast'] == 1)
                        {
                            // .1.3.6.1.4.1.637.61.1.31.2.12.1.9.${BRIDGEINDEX}.vlan
                            $vporteth = $this->get('.1.3.6.1.4.1.637.61.1.31.2.12.1.9.'.$bridgeport.'.'.$vlanindex , 'x');

                            $oid = array();
                            $type = array();
                            $value = array();

                            // pbit dla MC
                            $oid[0] = '.1.3.6.1.4.1.637.61.1.29.59.1.31.'.$vporteth;
                            $type[0] = 'i';
                            $value[0] = 4;

                            // IGMP max num group
                            $oid[1] = '.1.3.6.1.4.1.637.61.1.29.59.1.9.'.$vporteth;
                            $type[1] = 'i';
                            $value[1] = 16;

                            // IGMP version
                            $oid[2] = '.1.3.6.1.4.1.637.61.1.29.59.1.18.'.$vporteth;
                            $type[2] = 'i';
                            $value[2] = 2;

                            // IGMP max msg rate
                            $oid[3] = '.1.3.6.1.4.1.637.61.1.29.59.1.3.'.$vporteth;
                            $type[3] = 'i';
                            $value[3] = 16;

                            $result[] = $this->set_CLI($oid, $type, $value, 'x');
                        }
                    }

                    $oid = array();
                    $type = array();
                    $value = array();
                    // .1.3.6.1.4.1.637.61.1.31.2.5.1.3.${BRIDGEINDEX} // lmit mac
                    $oid[0] = ".1.3.6.1.4.1.637.61.1.31.2.5.1.3.".$bridgeport;
                    $type[0] = "u";
                    $value[0] = 10;

                    $result[] = $this->set_CLI($oid, $type, $value, 'x');    
                    
                    
                }

                $oid = array();
                $type = array();
                $value = array();
                # Podniesienie ONTa
                // .1.3.6.1.2.1.2.2.1.7.${ONTID} i 1 # (1 - up, 2 - down)
                $oid[0] = ".1.3.6.1.2.1.2.2.1.7.".$ont_index;
                $type[0] = "i";
                $value[0] = 1;

                $result[] = $this->set_CLI($oid, $type, $value, 'x');

                if (strlen($ONU_description)) {
                    $this->ONU_set_description($OLT_numport, $ONU_id, $ONU_description);
                }
                print_r($result);
                //die;  
                $result = array_unique($result);
                if (!strlen($this->parse_result_error($result))) {
                    $result['ONU_id']=$ONU_id;
                }
            }
            
            $this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Added Onu '.$ONU_id.', serial '.$ONU_name.', olt ' . $OLT_numport);
        }
       
        return $result;
    }

    public function ONU_set_description($OLT_id, $ONU_id, $ONU_description = '') //ok
    {
		$ogonek     = array(chr(177),chr(182),chr(191),chr(188),chr(230),chr(241),chr(179),chr(243),chr(234),chr(161),chr(166),chr(175),chr(172),chr(198),chr(209),chr(163),chr(211),chr(202));
        $bez_ogonek = array("a", "s", "z", "z", "c", "n", "l", "o", "e", "A", "S", "Z", "Z", "C", "N", "L", "O", "E");
        $result=array();
        $OLT_id=$OLT_id;
        $ONU_id=intval($ONU_id);
        $ont_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
        $ONU_description=trim($ONU_description);
        $ONU_description=str_replace($ogonek, $bez_ogonek, $ONU_description);
        if ($OLT_id && $ONU_id) {
            $result[] = $this->set_CLI(
                array('1.3.6.1.4.1.637.61.1.35.10.1.1.24.'.$ont_index),
                array('s'),
                array($ONU_description),
                'x'
            );
        }
        return array_unique($result);
    }

    public function ONU_get_IgmpGroup_table($OLT_id, $ONU_id)
    {
        return '';
        $result='';
        $OLT_id=intval($OLT_id);
        $ONU_id=intval($ONU_id);
        if ($OLT_id && $ONU_id) {
            $this->set_CLI(
                array('sleGponIgmpGroupControlRequest', 'sleGponIgmpGroupControlOltId', 'sleGponIgmpGroupControlOnuId', 'sleGponIgmpGroupControlTimer'),
                array('i', 'i', 'i', 'u'),
                array(1, $OLT_id, $ONU_id, 0)
            );
            $index=$this->walk('sleGponIgmpGroupIndex.'.$OLT_id.'.'.$ONU_id);
            $result='<h1 class="align-center">'.trans('Registered Multicast Groups').'</h1><table class="lmsbox lms-ui-background-cycle">';
            $result.='<thead><tr><th>Index:</th><th>Id:</th><th>'.trans('Type:').'</th><th>SrcIPAddr:</th><th>DstIPAddr:</th><th>RptIPAddr:</th><th>'.trans('TV channel:').'</th><th>JoinTime:</th><th>VlanId:</th></tr></thead>';
            if (is_array($index) && count($index)>0) {
                $num=0;
                foreach ($index as $k => $v) {
                    $num=intval($this->clean_snmp_value($v));
                    $DstIPAddr=$this->get('sleGponIgmpGroupDstIPAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num);
                    $RptIPAddr=$this->get('sleGponIgmpGroupRptIPAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num);
                    $tv = $this->GPON->GetGponOnuTvChannel($DstIPAddr);
                    $channel = $tv['channel'];
                    $result.='<tr>
					<td>'.$num.'</td>
					<td>'.$this->get('sleGponIgmpGroupUniId.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponIgmpGroupUniType.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponIgmpGroupSrcIPAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$DstIPAddr.'</td>
					<td>'.$RptIPAddr.'</td>
					<td>' . $channel . '</td>
					<td>'.$this->get('sleGponIgmpGroupJoinTime.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponIgmpGroupVlanId.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					</tr>';
                }
            }
            $result.='</table>';
        }
        return $result;
    }

    public function ONU_get_VoipLine_table($OLT_id, $ONU_id)
    {
        return '';
        $result='';
        $OLT_id=intval($OLT_id);
        $ONU_id=intval($ONU_id);
        if ($OLT_id>0 && $ONU_id>0) {
            $this->set_CLI(
                array('sleGponOnuVoipLineControlRequest', 'sleGponOnuVoipLineControlOltId', 'sleGponOnuVoipLineControlOnuId', 'sleGponOnuVoipLineControlTimer'),
                array('i', 'i', 'i', 'u'),
                array(1, $OLT_id, $ONU_id, 0)
            );
            $index=$this->walk('sleGponOnuVoipLineId.'.$OLT_id.'.'.$ONU_id);
            $result='<h1>' . trans('VoIP Lines') . '</h1><table class="lmsbox lms-ui-background-cycle">';
            $result.='<thead><tr class="text-center"><th>Pots:</th><th>Line Status:</th><th>Used Codec:</th><th>Session Type:</th><th>1st Protocol Period:</th><th>1st Dest Addr:</th><th>2nd Protocol Period:</th><th>2nd Dest Addr:</th></tr></thead>';
            if (is_array($index) && count($index)>0) {
                $num=0;
                foreach ($index as $k => $v) {
                    $num=intval($this->clean_snmp_value($v));
                    $result.='<tr>
					<td>'.$num.'</td>
					<td>'.$this->get('sleGponOnuVoipLineStatus.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLineUsedCodec.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLineSessionType.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine1stProtocolPeriod.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine1stDestAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine2ndProtocolPeriod.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine2ndDestAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					</tr>';
                }
            }
            $result.='</table>';
        }
        return $result;
    }

    public function ONU_get_UserMacOlt_table($OLT_id, $ONU_id, $snmp_ports, $xgspon = false)
    {
        $resultm='';
        $ONU_id=intval($ONU_id);

        $resultm.='<h1>'.trans('Detected MAC addresses on ONU').'</h1><table class="lmsbox lms-ui-background-cycle">';
        $resultm.='<thead><tr class="text-center"><th>no:</th><th>Port:</th><th colspan="2">MAC Address:</th><th>'.trans('Manufacturer:').'</th><th>VID:</th></tr></thead>';
        $num=1;
        for ($k = 0; $k < count($snmp_ports); $k++) {

            $bridgeport = self::calc_bridgeport($OLT_id.'/'.$ONU_id, $xgspon, $k+1);
          
            $vlanport =$this->walk('.1.3.6.1.4.1.637.61.1.31.2.12.1.9.'.$bridgeport , 'x');
            if (is_array($vlanport) && count($vlanport)>0) {
                foreach ($vlanport as $d => $v) {
                    $v = $this->clean_snmp_value($v);
                    $macwalk = $this->walk('.1.3.6.1.4.1.637.61.1.31.5.10.1.1.'.$v , 'x');
                    $vlanid = explode($bridgeport.'.', $d);
                    $vlanid = $vlanid[1];       

                    foreach ($macwalk as $vm) {
                        $mac = $this->clean_snmp_value($vm);
                        $mac_replace = str_replace('"', '', $mac);
                        $mac_replace = trim(str_replace('&nbsp;', ' ', $mac));
                        $mac_replace = str_replace(' ', ':', $mac_replace);
                         $resultm.='<tr>
                        <td>'.$num.'</td>
                        <td>eth '.($k+1).'</td>
                        <td class="text-right"><i class="lms-ui-icon-configuration" style="cursor: pointer;" onclick="javascript:changeMacFormat(\'mac-list-' . $num . '\')"
                            title="' . trans('Change the format of presentation of the MAC address.') . '"></i></td>
                        <td width="150px"><span id="mac-list-' . $num . '">' . $mac_replace . '</span></td>
                        <td>'.get_producer($mac_replace).'</td>
                        <td>'.$vlanid.'</td>
                        </tr>';
                        $num++;
                    }
                }
            }
            
           
        }
        if($num == 1)
        {
            $resultm.='<tr>
                    <td colspan="6">No MAC Detected</td>
                    </tr>';
        }
        $resultm.='</table>';
        return $resultm;
    }
    public function clean_arrays_strange_key($key)
    {
        $table1=array();
        $table=$this->walk($key);
        if (is_array($table) && count($table)>0) {
            foreach ($table as $k => $v) {
                $k1=str_replace('""', '', str_replace($this->path_OID.$key.'.', '', $k));
                $k1_explode=explode('.', $k1);
                $k=$k1_explode[0].'_'.$k1_explode[count($k1_explode)-1];
                $table1[$k]=$v;
            }
        }
        return $table1;
    }
    public function secondsToTime($s)
    {
        $h = floor($s / 3600);
        $s -= $h * 3600;
        $m = floor($s / 60);
        $s -= $m * 60;
        $d = floor($h / 24);
        $h -= $d * 24;
        return $d.' d, '.$h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
    }

    public function ONU_is_online($OLT_id, $ONU_id)
    {
        $ONU_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
        return $this->clean_snmp_value($this->get('1.3.6.1.4.1.637.61.1.35.10.4.1.2.'. $ONU_index)) == '0';
    }

    public function ONU_get_param($OLT_id, $ONU_id)  // pobieranie parametrow ONU
    {
        $result=array();
        $ONU_id=intval($ONU_id);
        $ONU_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
        $ONU_index = intval($ONU_index);
        if ($OLT_id>0 && $ONU_id>0) {
            $result['Status']=$this->color_snmp_value($this->get('1.3.6.1.4.1.637.61.1.35.10.4.1.2.'.$ONU_index, 'x'));
            $result['Status2']=$this->get('1.3.6.1.2.1.2.2.1.7.'.$ONU_index, 'x');
            $result['Serial']=$this->GPON->decodeOntSerialNumber($this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.5.'.$ONU_index, 'x') );
            $result['Description']=$this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.24.'.$ONU_index, 'x');
            if (preg_match('/^[A-F0-9]{2}&nbsp\;[A-F0-9]{2}&nbsp\;/', $result['Description'])) {
                //jakies \n sa w tym description !?
                $result['Description'] = trim(preg_replace('/\s*/', '', $result['Description']));
                $tmp = preg_replace('/&nbsp;/', '', $result['Description']);
                $newtmp = pack('H*', $tmp);
                $result['Description'] = $newtmp;
            }
            
            
            $result['Model Name']=$this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.26.'.$ONU_index, 'x');
            $result['Model Name'] = str_replace('"', '', str_replace('_', '', $result['Model Name']));
                
            //$result['Profile']=$this->get('...'.$ONU_index);
            //$result['Deactive Reason']=$this->get('...'.$ONU_index);
            $result['Rx Power']=($this->get('1.3.6.1.4.1.637.61.1.35.10.14.1.2.'.$ONU_index, 'x'))*0.002;
            if($result['Rx Power']== 65.536)
            {
                $result['Rx Power'] = '';
            }
            else
            {
                $result['Rx Power'] = round($result['Rx Power'],1)." dBm";
            }
            $result['Tx Power']=($this->get('1.3.6.1.4.1.637.61.1.35.10.14.1.4.'.$ONU_index, 'x'))*0.002;
            if($result['Tx Power']== 65.536)
            {
                $result['Tx Power'] = '';
            }
            else
            {
                $result['Tx Power'] = round($result['Tx Power'],1)." dBm";
            }
            $result['Rx Power OLT']=($this->get('1.3.6.1.4.1.637.61.1.35.10.18.1.2.'.$ONU_index, 'x'));
            if($result['Rx Power OLT']== 65534)
            {
                $result['Rx Power OLT'] = '';
            }
            else
            {
                $result['Rx Power OLT'] = $result['Rx Power OLT']*0.1." dBm";
            }
            $result['Distance']=$this->get('1.3.6.1.4.1.637.61.1.35.10.4.1.3.'.$ONU_index, 'x'); 
            if($result['Distance']== 65534)
            {
                $result['Distance'] = '';
            }
            else
            {
                $result['Distance'] = $result['Distance']*0.1." km";
            }
            //$result['Link Up Time']=$this->get('...'.$ONU_index);
            //$result['Inactive Time']=$this->get('...'.$ONU_index);
            //add human format
            /*if ($result['Inactive Time'] > 86400) {
                $result['Inactive Time'] .= $m=sprintf(" (%dd %02dh)", floor($result['Inactive Time']/86400), ($result['Inactive Time']%86400)/3600);
            } elseif ($result['Inactive Time'] > 3600) {
                $result['Inactive Time'] .= $m=sprintf(" (%dh %02dm)", floor($result['Inactive Time']/3600), ($result['Inactive Time']%3600)/60);
            } else {
                //$result['Inactive Time'] .= $m=sprintf(" (%dm %02ds)", floor($result['Inactive Time']/60), $result['Inactive Time']%60);
            }*/

            //$result['Mac']=$this->get('...');
            $result['OS1 Standby Version']=$this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.10.'.$ONU_index, 'x');
            $result['OS2 Active Version']=$this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.9.'.$ONU_index, 'x');
            //$result['Upgrade Status']=$this->get('...'.$OLT_id.'.'.$ONU_id);
            //$result['OLTMac']=$this->walk('...'.$OLT_id.'.'.$ONU_id);

            $result['Sys Up Time']=$this->clean_snmp_value($this->get('1.3.6.1.4.1.637.61.1.35.10.4.1.11.'.$ONU_index, 'x'));

            if( $result['Sys Up Time'] > 0)
            {
                $result['Sys Up Time'] = round($result['Sys Up Time']/100, 0);
            }
        }
        return $result;
    }

  

    public function style_gpon_tx_power($rxpower, $style = 1)
    {
        if ($rxpower == '') {
            return '';
        }
        $result = '';
        $gpon_rx_power_weak = ConfigHelper::getConfig('gpon-nokia.tx_power_weak');
        if ($gpon_rx_power_weak) {
            $rxpower = (float) str_replace(',', '.', str_replace('dBm', '', $rxpower));
            $gpon_rx_power_weak = (float) str_replace(',', '.', str_replace('dBm', '', $gpon_rx_power_weak));
            if ($rxpower <= $gpon_rx_power_weak) {
                if ($style == 1) {
                    $result=' style="background-color:#FF0000;color:#FFFFFF;" ';
                } else {
                    $result='#FF0000';
                }
            } else {
                $gpon_rx_power_overload = ConfigHelper::getConfig('gpon-nokia.tx_power_overload');
                if ($gpon_rx_power_overload) {
                    $gpon_rx_power_overload = (float) str_replace(',', '.', str_replace('dBm', '', $gpon_rx_power_overload));
                    if ($rxpower >= $gpon_rx_power_overload) {
                        if ($style == 1) {
                            $result=' style="background-color:#FFFF00;color:#000000;" ';
                        } else {
                            $result='#FFFF00';
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function style_gpon_rx_power($rxpower, $style = 1)
    {
        if ($rxpower == '') {
            return '';
        }
        $result = '';
        $gpon_rx_power_weak = ConfigHelper::getConfig('gpon-nokia.rx_power_weak');
        if ($gpon_rx_power_weak) {
            $rxpower = (float) str_replace(',', '.', str_replace('dBm', '', $rxpower));
            $gpon_rx_power_weak = (float) str_replace(',', '.', str_replace('dBm', '', $gpon_rx_power_weak));
            if ($rxpower <= $gpon_rx_power_weak) {
                if ($style == 1) {
                    $result=' style="background-color:#FF0000;color:#FFFFFF;" ';
                } else {
                    $result='#FF0000';
                }
            } else {
                $gpon_rx_power_overload = ConfigHelper::getConfig('gpon-nokia.rx_power_overload');
                if ($gpon_rx_power_overload) {
                    $gpon_rx_power_overload = (float) str_replace(',', '.', str_replace('dBm', '', $gpon_rx_power_overload));
                    if ($rxpower >= $gpon_rx_power_overload) {
                        if ($style == 1) {
                            $result=' style="background-color:#FFFF00;color:#000000;" ';
                        } else {
                            $result='#FFFF00';
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function ONU_get_param_table($OLT_id, $ONU_id, $ONU_name = '')
    {
        static $onumodels = array();

        $result='';
        if ($this->ONU_is_real($OLT_id, $ONU_id, $ONU_name)) {
            $snmp_result=$this->ONU_get_param($OLT_id, $ONU_id);
            $xgspon = $this->GPON->GetOnuXgsponStatus($OLT_id, $ONU_id);

            $result=trans('Data of:').' <b>'.date('Y-m-d H:i:s').'</b><br /><br />';
            if (is_array($snmp_result) && count($snmp_result)>0) {
                if (empty($onumodels)) {
                    $onumodels = $this->GPON->getGponOnuModelManagementUrls();
                }
                $PON_index = $this->calc_pon_index($OLT_id);
                $PON = $this->get('1.3.6.1.4.1.637.61.1.35.11.9.1.2.'.$PON_index, 'x')/10;
                if($snmp_result['Rx Power'] != '')
                {
                    // do poprawy bo xgspon trzeba liczyć z sygnału innego ale to w następnej wersji
                    $tlumienie=(float)str_replace(',', '.', $PON)-(float)str_replace(',', '.', str_replace('dBm', '', $snmp_result['Rx Power'])).' dB';
                }
                else
                {
                    $tłumienie = '';
                }

                $result.='
				<table width="100%">
                <tr>
				<td class="valign-top">
				<h1 class="text-center">'.trans('About ONU').'</h1>
					<table class="lmsbox lms-ui-background-cycle">
					<tr><td class="text-right bold">'.trans('ONU ID:').'</td><td>'.$ONU_id.'</td></tr>
					<tr><td class="text-right bold">'.trans('Name S/N:').'</td><td>'.$snmp_result['Serial'].'</td></tr>
					<tr><td class="text-right bold">Description:</td><td>' . mb_convert_encoding($snmp_result['Description'], 'UTF-8', 'UTF-8') . '</td></tr>
					<tr><td class="text-right bold">Model:</td><td>'.$snmp_result['Model Name'].'</td></tr>

					<tr><td class="text-right bold">Status:</td><td>'.$snmp_result['Status'].' <IMG SRC="img/';
                    if ($snmp_result['Status2']=='down(2)' || $snmp_result['Status2']=='2' || $snmp_result['Status2']=='down') {
                        $result.='no';
                    }
                    $result.='access.gif" ALT="">';
                    if ($snmp_result['Status2']=='down(2)' || $snmp_result['Status2']=='2' || $snmp_result['Status2']=='down') {
                        $result.='Odłączony';
                    }
                    $result .= '</td></tr>
                    <tr><td class="text-right bold">'.trans('TX ONU:').'</td><td'.$this->style_gpon_tx_power($snmp_result['Tx Power']).'>'.$snmp_result['Tx Power'].'</td></tr>
					<tr><td class="text-right bold">'.trans('Signal Level 1490nm').'<br />'.trans('Received on ONU:').'</td><td'.$this->style_gpon_rx_power($snmp_result['Rx Power']).'>'.$snmp_result['Rx Power'].'</td></tr>
                    <tr><td class="text-right bold">'.trans('Attenuation of the route to the subscriber:').'</td><td>'.$tlumienie.'</td></tr>
                    <tr><td class="text-right bold">'.trans('TX OLT:').'</td><td'.$this->style_gpon_tx_power($PON).'>'.$PON.' dBm</td></tr>
					<tr><td class="text-right bold">'.trans('Signal Level 1310nm').'<br />'.trans('Received on OLT:').'</td><td'.$this->style_gpon_rx_power($snmp_result['Rx Power OLT']).'>'.$snmp_result['Rx Power OLT'].'</td></tr>    
					<tr><td class="text-right bold">'.trans('Distance:').'</td><td>'.$snmp_result['Distance'].'</td></tr>
					<tr><td class="text-right bold">'.trans('Device Uptime:').'</td><td>'.$this->secondsToTime($snmp_result['Sys Up Time']).'</td></tr>';
                    //<tr><td class="text-right bold">'.trans('Link working time:').'</td><td>'.$snmp_result['Link Up Time'].'</td></tr>'
					//<tr><td class="text-right bold">'.trans('Time of inactivity:').'</td><td>'.$snmp_result['Inactive Time'].'</td></tr>
					//<tr><td class="text-right bold">'.trans('ONU MAC address:').'</td><td>'.$snmp_result['Mac'].'</td></tr>
					$result .= '<tr><td class="text-right bold">OS1 Standby Version:</td><td>'.$snmp_result['OS1 Standby Version'].'</td></tr>
					<tr><td class="text-right bold">OS2 Active Version:</td><td>'.$snmp_result['OS2 Active Version'].'</td></tr>';
					//<tr><td class="text-right bold">Upgrade Status:</td><td>'.$snmp_result['Upgrade Status'].'</td></tr>';
					//<tr><td class="text-right bold">OLT RX Power:</td><td>'.$snmp_result['OltRxPower'].'</td></tr>';


                $result.='</table>';
                $ETH_index = $this->calc_eth_index($OLT_id.'/'.$ONU_id, $xgspon);
                
                $OLT_index = $this->calc_ont_index($OLT_id.'/'.$ONU_id);

                $result.='</td><td class="valign-top">';
                $result.='<h1>'.trans('Port status on ONT').'</h1>
					<table class="lmsbox lms-ui-background-cycle">
					<thead><tr class="text-center"><th>Port:</th><th>'.trans('Oper Status:').'</th><th>Admin Status:</th><th>AutoNego:</th><th>Speed:</th></tr></thead>';
                    
                $snmp_ports=$this->walk('1.3.6.1.4.1.637.61.1.35.13.6.1.3.'.$OLT_index, 'x'); // sprawdzamy ile portów ethernet
                 
                if (is_array($snmp_ports) && count($snmp_ports)>0) {

                    for ($k = 0; $k < count($snmp_ports); $k++) {

                        $ETH_slot = $this->calc_eth_slot($OLT_id.'/'.$ONU_id, $xgspon, ($k+1));

                        $snmp_ports_id=$this->get('ifOperStatus.'.($ETH_index + $k) ,'x');
                        $snmp_ports_admin_status=$this->get('ifAdminStatus.'.($ETH_index+$k) , 'x');
                        $snmp_ports_autonego=$this->get('1.3.6.1.4.1.637.61.1.35.13.2.1.5.'.$OLT_index.'.'.$ETH_slot, 'x');
                        $snmp_ports_speed=$this->get('1.3.6.1.4.1.637.61.1.35.13.6.1.3.'.$OLT_index.'.'.$ETH_slot, 'x');

                    
                        $portoperstatus=$this->clean_snmp_value($snmp_ports_id);
                        $portadminstatus=$this->clean_snmp_value($snmp_ports_admin_status);
                        //print_r($snmp_ports_admin_status);
                        //die;
                        $autonego='';
                        $speed='';
                        $macs ='';
                        //if (preg_match('/^ethernet/', $portid)) {
                            $autonego=$this->match_autonego($this->clean_snmp_value($snmp_ports_autonego));
                            $speed=$this->match_speed($this->clean_snmp_value($snmp_ports_speed));
                            //$duplex=$this->clean_snmp_value($snmp_ports_duplex[$this->path_OID.'.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
                            //$mediummode=$this->clean_snmp_value($snmp_ports_mediummode[$this->path_OID.'.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
                            //mac table on port
                            $macs ='';
                            
                       // }
                        
                        //$result.='<tr class="text-center"><td> eth '.($k+1).' '.$OLT_index.'.'.($ETH_slot+$k) .'</td><td>'
                        $result.='<tr class="text-center"><td> eth '.($k+1).'</td><td>'
                            . (empty($portoperstatus) || $portoperstatus == 'up' ? 'up' : 'down').'</td><td>'
                            . (empty($portadminstatus) || $portadminstatus == 'up' ? 'up' : 'down')
                            //. '</td><td>' . $autonego . '</td><td>'
                            . '</td><td>' . $autonego . '</td><td>'
                            . $speed . '</td></tr>';
                            //. $speed . '</td><td>' . $duplex . '</td><td>'
                            //. $macs . '</td></tr>';
                    }
                }
                $result.='	</table>';
                

                /*//stara wersja - zła - ale zostawiam jakby to też było potrzebne
                $result.='<table border="1">
                    <tr><td><b>Lp.</b></td><td><b>MAC:</b></td></tr>';
                    if(is_array($snmp_result['OLTMac']) && count($snmp_result['OLTMac'])>0)
                    {
                        $i=1;
                        foreach($snmp_result['OLTMac'] as $k=>$v)
                        {
                            $result.='<tr><td align="right">'.$i.'.</td><td>'.$this->clean_snmp_value($v).'</td></tr>';
                            $i++;
                        }
                    }

                    $result.='</table>';
                    */
                    //$result.='<td>';

                    
                
                if (is_array($snmp_ports) && count($snmp_ports)>0) {
                    $result.=$this->ONU_get_UserMacOlt_table($OLT_id, $ONU_id, $snmp_ports, $xgspon);

                }
                //$result.='</tr>';
                $IgmpGroup_table=$this->ONU_get_IgmpGroup_table($OLT_id, $ONU_id);
                $VoipLine_table=$this->ONU_get_VoipLine_table($OLT_id, $ONU_id);
                
                $result.='</td></tr><tr>
                <td class="valign-top" colspan="2">';
                $result.='<br /><table class="text-left"><tr><td>'.$IgmpGroup_table.'</td></tr></table>';
                $result.='<br /><table class="text-left"><tr><td>'.$VoipLine_table.'</td></tr></table>';
                $result.='</td></tr>';
                $result.='</table>';
               
            }
        } else {
            $result='<font color="red"><b>'.trans('An error occured! Other ONUs are connected under this OLT').'<br />(OLT-port: '.$OLT_id.', ONU-ID: '.$ONU_id.', ONU-Serial: '.$this->ONU_GetSerial($OLT_id, $ONU_id).')</b></font>';
        }
        return $result;
    }
    public function ONU_get_param_table_edit($OLT_id, $ONU_id, $id, $phonesvoip = array(), $ONU_name = '')
    {
        //print_r($OLT_id);
        //$OLT_id=1;
        //$ONU_id=7;
        //echo $ONU_name;
        $result='';
        if ($this->ONU_is_real($OLT_id, $ONU_id, $ONU_name)) {
            $onchange=' onchange="this.style.borderColor=\'red\';"';
            $snmp_result=$this->ONU_get_param($OLT_id, $ONU_id);
            $xgspon = $this->GPON->GetOnuXgsponStatus($OLT_id, $ONU_id);

            $result='<p class="text-center">
        <button type="submit" class="lms-ui-button" id="save_changes" OnClick="document.getElementById(\'save\').value=1;document.getElementById(\'snmpsend\').value=1;this.form.submit();"><i class="lms-ui-icon-save"></i><span class="lms-ui-label"> ' . trans("Save changes via SNMP") . '</button><br>
			'.trans('Data of:').' <b>'.date('Y-m-d H:i:s').'</b></p>';
            if (is_array($snmp_result) && count($snmp_result)>0) {
                $result.='
				<FORM ID="myform" name="myform" METHOD="POST" ACTION="?m=gpondasanonuedit&id='.$id.'">
				<input type="hidden" name="snmpsend" id="snmpsend" value="0" />
				<input type="hidden" name="onureset" id="onureset" value="0" />
				<input type="hidden" name="clear_mac" id="clear_mac" value="0" />
				<input type="hidden" name="save" id="save" value="1" />
				<table cellspacing="3" border="0" width="99%">
				<tr>
				<td rowspan="2" valign="top" width="40%">
					<table class="lmsbox lms-ui-background-cycle">
					';
                $snmp_result['Status']=trim($snmp_result['Status']);
                if (preg_match('/active\(2\)/', $snmp_result['Status']) || preg_match('/running/', $snmp_result['Status'])) {
                    $result.='
					<tr><td class="text-right bold">ONU Reset:</td><td><input type="button" value="Reset" id="onu_reset" OnClick="document.getElementById(\'onureset\').value=1;this.form.submit();" /></td></tr>';
                }
                $result.='
					<tr><td class="text-right bold">'.trans('ONU ID:').'</td><td>'.$ONU_id.'</td></tr>
					<tr><td class="text-right bold">'.trans('Name S/N:').'</td><td>'.$snmp_result['Serial'].'</td></tr>
					<tr><td class="text-right bold">Description:</td><td><INPUT TYPE="TEXT" NAME="onu_description" id="onu_description" VALUE="'.$snmp_result['Description'].'" MAXLENGTH="32" '.$onchange.'/></td></tr>
					<tr><td class="text-right bold">Model:</td><td>'.$snmp_result['Model Name'].'</td></tr>';


                   /* $result.='<tr><td class="text-right bold">'.trans('ONU Profile:').'</td><td>';
                    $profiles=$this->GPON_get_profiles();
                    $result.='<SELECT NAME="onu_profile"'.$onchange.'>';
                if (is_array($profiles) && count($profiles)>0) {
                    foreach ($profiles as $k => $v) {
                        $result.='<OPTION VALUE="'.$v.'" ';
                        if ($snmp_result['Profile']==$v) {
                            $result.='selected="selected"';
                        }
                        $result.=' >'.$v.'</OPTION>';
                    }
                }
                    $result.='</SELECT>';
                    $result.='</td></tr>*/
					$result.='<tr><td class="text-right bold">'.trans('Clear MAC:').'</td><td><input type="button" value="'.trans('Clear').'" id="clear_mac_button" OnClick="document.getElementById(\'clear_mac\').value=1;this.form.submit();" /></td></tr>
					<tr><td class="text-right bold">Status:</b></td><td>'.$snmp_result['Status'].'</td></tr>
					<tr><td class="text-right bold"><IMG SRC="img/';
                    
                if ($snmp_result['Status2']=='down(2)' || $snmp_result['Status2']=='2' || $snmp_result['Status2']=='down') {
                    $result.='no';
                }
                    $result.='access.gif" ALT=""></b></td><td>';
                    $result.='<SELECT SIZE="1" NAME="onu_status"'.$onchange.'>';
                    $result.='<OPTION VALUE="1"';
                if ($snmp_result['Status2']=='up(1)' || $snmp_result['Status2']=='1' || $snmp_result['Status2']=='up') {
                    $result.=' selected="selected"';
                }
                    $result.='>'.trans('Connected').'</OPTION>';
                    $result.='<OPTION VALUE="2"';
                if ($snmp_result['Status2']=='down(2)' || $snmp_result['Status2']=='2' || $snmp_result['Status2']=='down') {
                    $result.=' selected="selected"';
                }
                    $result.='>'.trans('Disconnected').'</OPTION></SELECT>';

                    $result.='</td></tr>
					<tr><td class="text-right bold">'.trans('TX ONU:').'</td><td'.$this->style_gpon_tx_power($snmp_result['Tx Power']).'>'.$snmp_result['Tx Power'].'</td></tr>
					<tr><td class="text-right bold">'.trans('Signal Level 1490nm').'<br />'.trans('Received on ONU:').'</td><td'.$this->style_gpon_rx_power($snmp_result['Rx Power']).'>'.$snmp_result['Rx Power'].'</td></tr>
					<tr><td class="text-right bold">'.trans('Signal Level 1310nm').'<br />'.trans('Received on OLT:').'</td><td'.$this->style_gpon_rx_power($snmp_result['Rx Power OLT']).'>'.$snmp_result['Rx Power OLT'].'</td></tr>
					<tr><td class="text-right bold">'.trans('Distance:').'</td><td>'.$snmp_result['Distance'].'</td></tr>';
					//<tr><td class="text-right bold">'.trans('Link working time:').'</td><td>'.$snmp_result['Link Up Time'].'</td></tr>
					//<tr><td class="text-right bold">'.trans('Device Uptime:').'</td><td>'.$this->secondsToTime($snmp_result['Sys Up Time']).'</td></tr>
					//<tr><td class="text-right bold">'.trans('ONU MAC address:').'</td><td>'.$snmp_result['Mac'].'</td></tr>
					 $result.='<tr><td class="text-right bold">OS1 Standby Version:</td><td>'.$snmp_result['OS1 Standby Version'].'</td></tr>
					<tr><td class="text-right bold">OS2 Active Version:</td><td>'.$snmp_result['OS2 Active Version'].'</td></tr>';

                    $ETH_index = $this->calc_eth_index($OLT_id.'/'.$ONU_id, $xgspon);
                    //$ETH_slot = $this->calc_eth_slot($OLT_id.'/'.$ONU_id);
                    $OLT_index = $this->calc_ont_index($OLT_id.'/'.$ONU_id);
                    //echo '<br>'.$OLT_index.' '.$ETH_slot;
                    $result.='</tr></table><br />

				</td>
				<td valign="top" style="vertical-align:top">
					<table class="lmsbox lms-ui-background-cycle">
					<thead><tr class="text-center"><th>Port Id:</th><th>Oper Status:</th><th>Admin Status:</th><th>AutoNego:</th><th>Speed:</th></tr></thead>';

                    $snmp_ports=$this->walk('1.3.6.1.4.1.637.61.1.35.13.6.1.3.'.$OLT_index, 'x'); // sprawdzamy ile portów ethernet

                    if (is_array($snmp_ports) && count($snmp_ports)>0) {
    
                        for ($k = 0; $k < count($snmp_ports); $k++) {
    
                            $ETH_slot = $this->calc_eth_slot($OLT_id.'/'.$ONU_id, $xgspon, ($k+1));
    
                            $snmp_ports_id=$this->get('ifOperStatus.'.($ETH_index + $k) ,'x');
                            $portstatus=$this->get('ifAdminStatus.'.($ETH_index+$k) , 'x');
                            $snmp_ports_autonego=$this->get('1.3.6.1.4.1.637.61.1.35.13.2.1.5.'.$OLT_index.'.'.$ETH_slot, 'x');
                            $snmp_ports_speed=$this->get('1.3.6.1.4.1.637.61.1.35.13.6.1.3.'.$OLT_index.'.'.$ETH_slot, 'x');
    
                        
                            $portoperstatus=$this->clean_snmp_value($snmp_ports_id);
                            $portstatus=$this->clean_snmp_value($portstatus);
                            //print_r($snmp_ports_admin_status);
                            //die;
                            $autonego='';
                            $speed='';
                            $macs ='';
                            //if (preg_match('/^ethernet/', $portid)) {
                                $autonego=$this->clean_snmp_value($snmp_ports_autonego);
                                $speed=$this->match_speed($this->clean_snmp_value($snmp_ports_speed));

                                $portid = $k+1;
                        $result.='<tr class="text-center"><td>eth '.$portid.'</td>';
                        //echo $portstatus;
                        //die;
                        $result.='<td>'. (empty($portoperstatus) || $portoperstatus == 'up' ? 'up' : 'down').'</td>';
                        $result.='<td>
						<select name="onuport_'.$portid.'"'.$onchange.'>
						<option';
                        if ($portstatus == '1' || $portstatus == 'up' || $portstatus == 'up(1)') {
                            $result .= ' selected';
                        }
                        $result.=' value="1">up</option>
						<option';
                        if ($portstatus == '2' || $portstatus == 'down' || empty($portstatus) || !strlen(trim($portstatus))) {
                            $result .= ' selected';
                        }
                        $result.=' value="2">down</option>
						</select>
						</td>';
                        $result.='<td>';
                     
                        $result.='
                        <select name="onuportautonego_'.$portid.'"'.$onchange.'>
                        <option ';
                        if ($autonego=='0') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="0">10/100Auto</option>
                        <option ';
                        if ($autonego=='1') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="1">10FD</option>
                        <option ';
                        if ($autonego=='2') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="2">100FD</option>
                        <option ';
                        if ($autonego=='3') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="3">1000FD</option>
                        <option ';
                        if ($autonego=='4') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="4">AutoFD</option>
                        <option ';
                        if ($autonego=='5') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="5">10GigFD</option>
                        <option ';
                        if ($autonego=='16') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="16">10Auto</option>
                        <option ';
                        if ($autonego=='17') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="17">10HD</option>
                        <option ';
                        if ($autonego=='18') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="18">100HD</option>
                        <option ';
                        if ($autonego=='19') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="19">1000HD</option>
                        <option ';
                        if ($autonego=='20') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="20">AutoHD</option>
                        <option ';
                        if ($autonego=='32') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="32">10/100/1000Auto</option>
                        <option ';
                        if ($autonego=='48') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="48">100Auto</option>
                        <option ';
                        if ($autonego=='96') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="96">AutoAuto</option>
                        <option ';
                        if ($autonego=='97') {
                            $result.='selected="selected"';
                        }
                        $result.=' value="97">1000Auto</option>
                        </select>
                        ';
                        
                        $result.='</td><td>';
                        //if (preg_match('/ethernet/', $portid) && !preg_match('/virtual/', $portid)) {
                            $result.= $speed;
                        //}
                        $result.='</td></tr>';
                    }
                }
                $result.='</table>
				</td></tr>
				
				</table>
				</form>
				';
            }
        } else {
            $result='<font color="red"><b>'.trans('An error occured! Other ONUs are connected under this OLT').'<br />(OLT-port: '.$OLT_id.', ONU-ID: '.$ONU_id.', ONU-Serial: '.$this->ONU_GetSerial($OLT_id, $ONU_id).')</b></font>';
        }
        return $result;
    }

    public function OLT_write_config()
    {
       return false; // do sprawdzenia czy się da 
    }

    private function match_autonego($var)
    {
        $result = '';
        switch ($var) {
            case 0:
                $result = '10/100Auto';
                break;
            case 1:
                $result = '10FD';
                break;
            case 2:
                $result = '100FD';
                break;
            case 3:
                $result = '1000FD';
                break;
            case 4:
                $result = 'AutoFD';
                break;
            case 5:
                $result = '10GigFD';
                break;
            case 16:
                $result = '10Auto';
                break;
            case 17:
                $result = '10HD';
                break;
            case 18:
                $result = '100HD';
                break;
            case 19:
                $result = '1000HD';
                break;
            case 20:
                $result = 'AutoHD';
                break;
            case 32:
                $result = '10/100/1000Auto';
                break;
            case 48:
                $result = '100Auto';
                break;
            case 96:
                $result = 'AutoAuto';
                break;
            case 97:
                $result = '1000Auto';
                break;
            default:
                $result = 'unknown';
        }
        
        return $result;
    }
    private function match_speed($var)
    {
        $result = '';
        switch ($var) {
            case 0:
                $result = 'Status not detected';
                break;
            case 1:
                $result = '10BaseT (full duplex)';
                break;
            case 2:
                $result = '100BaseT (full duplex)';
                break;
            case 3:
                $result = 'GigE (full duplex)';
                break;
            case 4:
                $result = '10GigE (full duplex)';
                break;
            case 17:
                $result = '10BaseT (half duplex)';
                break;
            case 18:
                $result = '100BaseT (half duplex)';
                break;
            case 19:
                $result = 'GigE (half duplex)';
                break;
            default:
                $result = 'unknown';
        }

        return $result;
    }

    private static function calc_bridgeport($ont, $xgspon = false, $port = 1)
    {
        $var = explode("/", $ont);
        
        $index = "00";
        $index .= sprintf( "%05d", decbin($var[2]+1)); //SLOT
        $index .= "0110"; // level
        $index .= sprintf( "%05d", decbin($var[3]-1)); //PON
        $index .= sprintf( "%07d", decbin($var[4]-1)); //ONT
        if( $xgspon == true)
        {
            $index .= sprintf( "%04d", decbin($var[1]+9));   //CARD
        }
        else
        {
            $index .= sprintf( "%04d", decbin($var[1]));   //CARD
        }
        $index .= sprintf( "%05d", decbin($port-1));   //PORT
    
        return bindec($index);
    }

    public function calc_pon_index($ont)
    {
        $var = explode("/", $ont);
        $pon_index = (($var[2] + 1 & 0x1F) << 25) + ((13 & 0xF) << 21) + (($var[3] - 1 & 0x1F) << 16);

        return $pon_index;
    }

    private function calc_eth_slot($ont, $xgspon = false, $port = 1)
    {
        //index = ((2 & 0x7) << 28) + ((card & 0x3f) << 22) + ((port & 0x3f) << 16)

        $var = explode("/", $ont);
        
        $index = "10";
        if( $xgspon == true)
        {
            $index .= sprintf( "%06d", decbin($var[1]+9)); //CARD
        }
        else
        {
            $index .= sprintf( "%06d", decbin($var[1])); //CARD
        }
       
        $index .= sprintf( "%06d", decbin($port)); //PORT
        $index .= "0000000000000000";
    
        
        return bindec($index);
    }

    private function calc_eth_index($ont, $xgspon = false, $port = 1)
    {
        $var = explode("/", $ont);
               
        $index = "00";
        $index .= sprintf( "%05d", decbin($var[2]+1)); //SLOT
        $index .= "0000"; // level
        $index .= sprintf( "%05d", decbin($var[3]-1)); //PON
        $index .= sprintf( "%07d", decbin($var[4]-1)); //ONT
        if( $xgspon == true)
        {
            $index .= sprintf( "%04d", decbin($var[1]+9));   //CARD
        }
        else
        {
            $index .= sprintf( "%04d", decbin($var[1]));   //CARD
        }
        $index .= sprintf( "%05d", decbin($port-1));   //PORT

        return bindec($index);
    } 
    private static function calc_ont_index($ont)
    {
        $var = explode("/", $ont);

        $index = "00";
        $index .= sprintf( "%05d", decbin($var[2]+1)); //SLOT
        $index .= "1110";
        $index .= sprintf( "%05d", decbin($var[3]-1)); //PON
        $index .= sprintf( "%07d", decbin($var[4]-1)); //ONT
        $index .= "000000000";
        
        return bindec($index);
    }

    private function OLT_ONU_walk($port = null, $oltid = null)
    {
           
            $result = array();


            if (!empty($port)) {
               
                $result['Status'] = array();
                $result['RxPower'] = array();
                $result['Distance'] = array();

                for($i = 1; $i <= 128; $i++) 
                {
                    
                    $test = $this->GPON->GponGetOnuNameFromOltOnuId($oltid, $port, $i);
                    if($test['name'] != '')
                    {
                        $port_snmp = $this->calc_ont_index($port.'/'.$i);
                        $tmp = $this->get('1.3.6.1.4.1.637.61.1.35.10.4.1.2.'.$port_snmp, 'x');
                       
                        if($tmp != '')
                        {
                            $result['Status'][$port_snmp] = $tmp;
                            // Pobierz dane bezpośrednio, bez dodatkowej zagnieżdżonej tablicy
                            $rxPower = $this->get('1.3.6.1.4.1.637.61.1.35.10.14.1.2.' . $port_snmp, 'x'); // ONT RX Power
                            $OLTrxPower = $this->get('1.3.6.1.4.1.637.61.1.35.10.18.1.2.' . $port_snmp, 'x'); // OLT RX Power
                            $distance = $this->get('1.3.6.1.4.1.637.61.1.35.10.4.1.3.' . $port_snmp, 'x');
                            $activeos = $this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.9.' . $port_snmp, 'x');
                            
                            $result['RxPower'][$port_snmp] = $rxPower;
                            $result['OLTrxPower'][$port_snmp] = $OLTrxPower;
                            $result['Distance'][$port_snmp] = $distance*0.1;
                            $result['ActiveOS'][$port_snmp] = $activeos;
                        }
                    }
                }
            }
        return $result;
    }

    public function OLT_ONU_walk_get_param($port = null, $oltid)
    {
        $result1 = array();


       
        $result = $this->OLT_ONU_walk($port, $oltid);
        if (is_array($result) && !empty($result)) {
            foreach ($result as $k => $v) {
                if (is_array($v) && !empty($v)) {
                    foreach ($v as $k1 => $v1) {
                        $v1 = $this->clean_snmp_value($v1);
                        switch ($k) {
                            case 'Distance':
                                $k1 = end(explode('.', $k1));
                                if($v1 == 6553.4)
                                {
                                    $v1 = null;
                                }
                                break;
                            case 'OLTrxPower':
                               
                                if($v1 == 65534)
                                {
                                
                                    $v1 = null;
                                }
                                else
                                {
                                    $v1 = $v1*0.1.' dBm';
                                }
                                break;
                            case 'RxPower':
                                if ($v1 == 32768)
                                {
                                    $v1 = null;
                                }
                                else
                                {
                                    $v1 = round($v1*0.002, 1).' dBm';
                                }
                                break;
                            case 'Status':
                                $k1 = end(explode('.', $k1));
                                $v1 = $this->color_snmp_value($v1);
                                break;
                            case 'ActiveOS':
                                $k1 = end(explode('.', $k1));
                                break;
                        }
                        $result1[$k][$k1] = $v1;
                    }
                }
            }
        }
        return $result1;
    }

    public function OLT_get_param($OLT_id = 0)
    {
        $result = array();
        $result['Version'] = $this->get('sysDescr.0', 'SNMPv2-MIB::');
        $result['Up time'] = $this->get('sysUpTimeInstance', 'DISMAN-EVENT-MIB::');
        $result['Contact'] = $this->get('sysContact.0', 'SNMPv2-MIB::');
        $result['Name'] = $this->get('sysName.0', 'SNMPv2-MIB::');
        $result['Location'] = $this->get('sysLocation.0', 'SNMPv2-MIB::');
        $result['OLT ID'] = $OLT_id;

        return $result;
    }

    public function OLT_get_param_array()
    {
        $result=array();
        $result['temp']=$this->walk('1.3.6.1.4.1.637.61.1.35.11.2.1.57', 'x');

        foreach ($result['temp'] as $k => $v) {
            $pid = explode('.', $k);
            $pid = end($pid);
            $port = $this->GPON->decode_ont_index($pid);
            $result['test'][] = $port;
            $result['Status'][$port][] = $this->get('1.3.6.1.2.1.2.2.1.8.'.$pid, 'x');
            $tx_tmp=$this->get('1.3.6.1.4.1.637.61.1.35.11.9.1.2.'.$pid, 'x');
            if ($tx_tmp == 65534) {
                $result['Moc Tx'][$port][] = '';
            } else {
                $result['Moc Tx'][$port][] = $tx_tmp / 10 .' dbm';
            }

            $result['Vendor'][$port]=$this->get('.1.3.6.1.4.1.637.61.1.35.11.9.1.6.'.$this->calc_pon_index($port), 'x');
        }
        return $result;
    }

    public function OLT_get_param_table($OLT_id = 0)
    {
        $result = '';

        $snmp_result = $this->OLT_get_param($OLT_id);
      

        $result = trans('Data of:').' <b>' . date('Y-m-d H:i:s') . '</b><br><br>';
        if (is_array($snmp_result) && count($snmp_result)) {
            $result .= '<p class="text-center bold">OLT system information</p><table class="lmsbox lms-ui-background-cycle">';
            foreach ($snmp_result as $k => $v) {
                $result .= '<tr><td class="text-right bold">' . $k . ':</td><td>' . $v . '</td></tr>';
            }
            $result .= '</table>';
        }

        $param_array = $this->OLT_get_param_array();
        if (is_array($param_array) && count($param_array)) {
            $result .= '<br><p class="text-center bold">OLT Ports</p><table class="lmsbox lms-ui-background-cycle">';
            $result .= '<thead><tr class="text-center bold"><td>Port</td><td>Status GPON</td>'
                . '<td>Moc Tx</td><td>Status XSGPON</td><td>Moc Tx</td><td>Vendor</td></thead><tbody>';
            foreach ($param_array as $k => $v) {
                if ($k=='Status') {
                    if (is_array($v) && count($v)>0) {
                        foreach ($v as $k1 => $v1) {
                            $numport=$k1;
                            $result.='<tr class="text-center">
							<td>'.$numport.'</td>
							<td>'.$this->clean_snmp_value($v1[0]).'</td>
                            <td>'.$this->clean_snmp_value($param_array['Moc Tx'][$numport][0]).'</td>
							<td>'.$this->clean_snmp_value($v1[1]).'</td>
							<td>'.$this->clean_snmp_value($param_array['Moc Tx'][$numport][1]).'</td>
							<td>'.$this->clean_snmp_value($param_array['Vendor'][$numport]).'</td>
							</tr>';
                        }
                    }
                }
            }
            $result.='</tbody></table>';
        }

        return $result;
    }

    public function OLT_get_ports_maxonu() //ok
    {
        $portlist = $this->walk('1.3.6.1.4.1.637.61.1.35.11.2.1.57', 'x');
        foreach ($portlist as $k => $v) {
            $max = intval($this->clean_snmp_value($v));
            $pid = explode('.', $k);
            $pid = end($pid);
            $ports[$pid] = $max;
        }
        return $ports;
    }

    public function OLT_get_ports_description() //ok
    {
        $portlist = $this->walk('1.3.6.1.4.1.637.61.1.35.11.2.1.19', 'x');
        foreach ($portlist as $k => $v) {
            $desc = $this->clean_snmp_value($v);
            $pid = explode('.', $k);
            $pid = end($pid);
            $ports[$pid] = $desc;
        }
        return $ports;
    }

    public function ONU_Reset($OLT_id, $ONU_id) //ok
    {
        $result=array();
        $OLT_id=$OLT_id;
        $ONU_id=intval($ONU_id);
        $ONU_index=self::calc_ont_index($OLT_id.'/'.$ONU_id);
        if ($ONU_index>0 && $ONU_id>0) {
            $result=$this->set('1.3.6.1.4.1.637.61.1.35.10.1.1.17.'.$ONU_index, 'i', '1', 'x');
    
            $this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Onu Reset '.$ONU_id.', olt '.$OLT_id);
            
        }

        return array_unique($result);
    }

    public function ONU_FactorySettings($OLT_id, $ONU_id)
    {
        $result=array();
        $OLT_id=$OLT_id;
        $ONU_id=intval($ONU_id);
        $ONU_index=self::calc_ont_index($OLT_id.'/'.$ONU_id);
        if ($ONU_index>0 && $ONU_id>0) {
            $result=$this->set('1.3.6.1.4.1.637.61.1.35.10.1.1.17.'.$ONU_index, 'i', '1', 'x'); // do podmiany jak będzie wiadomo jaki
    
            $this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Onu Factory Settings '.$ONU_id.', olt '.$OLT_id);
            
        }

        return array_unique($result);

    }

    public function ONU_ClearMac($OLT_id, $ONU_id)
    {
        $result=array();
        $ONU_id=intval($ONU_id);

        return array_unique($result);
    }

    public function ONU_Status($OLT_id, $ONU_id, $status) //ok
    {
        $result=array();
        $OLT_id=$OLT_id;
        $ONU_id=intval($ONU_id);
        $status=intval($status);
        $ont_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
        if ($OLT_id && $ONU_id) {
            $result[] = $this->set('.1.3.6.1.2.1.2.2.1.7.'.$ont_index, 'i', $status, 'x');
        }

        return array_unique($result);
    }

    public function ONU_SetPortStatus($OLT_id, $ONU_id, $typ, $port, $status) //ok
    {
        $result=array();
        $ONU_id=intval($ONU_id);
        $xgspon = $this->GPON->GetOnuXgsponStatus($OLT_id, $ONU_id);
        $port=intval($port);
        $status=intval($status);
        $eth_index = $this->calc_eth_index($OLT_id.'/'.$ONU_id , $xgspon, $port);
        
        if ($OLT_id && $ONU_id && $port) {
            $result[] = $this->set('.1.3.6.1.2.1.2.2.1.7.'.$eth_index, 'i', $status, 'x');
        }
        return array_unique($result);
    }

    public function ONU_SetAutoNego($OLT_id, $ONU_id, $typ, $port, $autonego) //ok
    {
        $result=array();
        $ONU_id=intval($ONU_id);
        $xgspon = $this->GPON->GetOnuXgsponStatus($OLT_id, $ONU_id);
        $typ=intval($typ);
        $port=intval($port);
        $autonego=intval($autonego);
        $ETH_slot = $this->calc_eth_slot($OLT_id.'/'.$ONU_id,  $xgspon, $port);
        $OLT_index = $this->calc_ont_index($OLT_id.'/'.$ONU_id);
        if ($OLT_id && $ONU_id && $port) {
            
            $result[] = $this->set_CLI(
                array('1.3.6.1.4.1.637.61.1.35.13.2.1.5.'.$OLT_index.'.'.$ETH_slot ),
                array('i',),
                array($autonego),
                'x'
            );            
        }
        return array_unique($result);
    }

    public function ONU_SetPhoneVoip($OLT_id, $ONU_id, $typ, $port, $phone_data = array())
    {
        $result=array();
        $OLT_id=intval($OLT_id);
        $ONU_id=intval($ONU_id);
        $typ=intval($typ);
        $port=intval($port);
        if ($OLT_id && $ONU_id && $port && is_array($phone_data)) {
            if (!count($phone_data)) {
                $phone_data['login']='';
                $phone_data['passwd']='';
                $phone_data['phone']='';
            }
            $onu=$this->walk('sleGponOnuSerial');
            if ($this->search_array_key($onu, 'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id)) {
                $result[] = $this->set_CLI(
                    array('sleGponProfileVoIPOmciControlRequest', 'sleGponProfileVoIPOmciControlOltIndex', 'sleGponProfileVoIPOmciControlOnuIndex',
                        'sleGponProfileVoIPOmciControlUniId', 'sleGponProfileVoIPOmciControlAuthName', 'sleGponProfileVoIPOmciControlAuthPasswd',
                        'sleGponProfileVoIPOmciControlTimer'),
                    array('i', 'i', 'i', 'i', 's', 's', 'u'),
                    array(1, $OLT_id, $ONU_id, $port, $phone_data['login'], $phone_data['passwd'], 0)
                );

                $result[] = $this->set_CLI(
                    array('sleGponProfileVoIPOmciControlRequest', 'sleGponProfileVoIPOmciControlOltIndex', 'sleGponProfileVoIPOmciControlOnuIndex',
                        'sleGponProfileVoIPOmciControlUniId', 'sleGponProfileVoIPOmciControlPhoneNumber', 'sleGponProfileVoIPOmciControlTimer'),
                    array('i', 'i', 'i', 'i', 's', 'u'),
                    array(2, $OLT_id, $ONU_id, $port, $phone_data['phone'], 0)
                );
            }
        }
        return array_unique($result);
    }

    public function GPON_get_profiles()
    {
        $result=array();
        $profiles=$this->walk('sleGponProfileName');
        if (is_array($profiles) && count($profiles)>0) {
            foreach ($profiles as $k => $v) {
                $v=$this->clean_snmp_value($v);
                $result[$v]=$v;
            }
        }
        return $result;
    }

    public function ONU_is_real($OLT_id, $ONU_id, $ONU_name) //ok
    {
        $result=false;
        $ONU_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
        $ONU_index=intval($ONU_index);
        $ONU_name=trim($ONU_name);
        if ($ONU_index>0 && $ONU_id>0 && strlen($ONU_name)>0) {
            $onu=$this->clean_snmp_value($this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.5.'.$ONU_index ,'x'));
            $onu = $this->GPON->decodeOntSerialNumber($onu);
            if ($onu==$ONU_name) {
                $result=true;
            }
        }
        return $result;
    }
    public function ONU_GetSerial($OLT_id, $ONU_id)
    {
        $result='';
        $ONU_id=intval($ONU_id);
        $ONU_index = self::calc_ont_index($OLT_id.'/'.$ONU_id);
        if ($OLT_id>0 && $ONU_id>0) {
            $onu=$this->clean_snmp_value($this->get('1.3.6.1.4.1.637.61.1.35.10.1.1.5.'.$ONU_index, 'x'));
            $result = $this->GPON->decodeOntSerialNumber($onu);
        }
        return $result;
    }
     
  
}
