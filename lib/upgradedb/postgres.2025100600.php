<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponnokiaonumodels ADD COLUMN defqosprofile varchar(120) DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025100600', 'dbversion_LMSGponNokiaPlugin'));

$this->CommitTrans();
