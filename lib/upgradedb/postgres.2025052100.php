<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponnokiaonumodels ADD COLUMN swverpland varchar(120) DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025052100', 'dbversion_LMSGponNokiaPlugin'));

$this->CommitTrans();
