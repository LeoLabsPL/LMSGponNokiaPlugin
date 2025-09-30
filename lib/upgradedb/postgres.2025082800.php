<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponnokiaonuporttype2models ADD COLUMN portslot integer DEFAULT NULL");
$this->Execute("INSERT INTO gponnokiaonuporttypes (name) VALUES ('10G-eth')");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025082800', 'dbversion_LMSGponNokiaPlugin'));

$this->CommitTrans();
