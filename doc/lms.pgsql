DO $$
BEGIN
	IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'auth_protocol') THEN
		CREATE TYPE auth_protocol AS ENUM ('MD5','SHA','');
	END IF;
	IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'sec_level') THEN
		CREATE TYPE sec_level AS ENUM ('noAuthNoPriv','authNoPriv','authPriv','');
	END IF;
	IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'privacy_protocol') THEN
		CREATE TYPE privacy_protocol AS ENUM ('DES','AES','');
	END IF;
END
$$;

/* gponnokiaolts */
CREATE SEQUENCE gponnokiaolts_id_seq;
CREATE TABLE gponnokiaolts (
	id integer DEFAULT nextval('gponnokiaolts_id_seq'::text) NOT NULL,
	snmp_version smallint NOT NULL,
	snmp_description varchar(255) NOT NULL,
	snmp_host varchar(100) NOT NULL,
	snmp_community varchar(100) NOT NULL,
	snmp_auth_protocol auth_protocol NOT NULL,
	snmp_username varchar(255) NOT NULL,
	snmp_password varchar(255) NOT NULL,
	snmp_sec_level sec_level NOT NULL,
	snmp_privacy_passphrase varchar(255) NOT NULL,
	snmp_privacy_protocol privacy_protocol NOT NULL,
	snmp_is_bussy smallint NOT NULL DEFAULT 0,
	netdeviceid integer NOT NULL
		REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

/* gponnokiaoltports */
CREATE SEQUENCE gponnokiaoltports_id_seq;
CREATE TABLE gponnokiaoltports (
	id integer DEFAULT nextval('gponnokiaoltports_id_seq'::text) NOT NULL,
	gponoltid integer NOT NULL,
	numport varchar(16) NOT NULL,
	maxonu integer NOT NULL,
	description text NOT NULL DEFAULT '',
	PRIMARY KEY (id)
);
CREATE INDEX gponnokiaoltports_gponoltid_idx ON gponnokiaoltports (gponoltid);

/* gponnokiaoltprofiles */
CREATE SEQUENCE gponnokiaoltprofiles_id_seq;
CREATE TABLE gponnokiaoltprofiles (
	id integer DEFAULT nextval('gponnokiaoltprofiles_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	gponoltid integer DEFAULT NULL
		REFERENCES gponnokiaolts (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

/* gponnokiaonus */
CREATE SEQUENCE gponnokiaonus_id_seq;
CREATE TABLE gponnokiaonus (
	id integer DEFAULT nextval('gponnokiaonus_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	gpononumodelsid integer NOT NULL,
	password varchar(100) NOT NULL,
	onuid smallint NOT NULL DEFAULT 0,
	autoprovisioning smallint DEFAULT NULL,
	onudescription varchar(32) DEFAULT NULL,
	gponoltprofilesid integer DEFAULT NULL,
	serviceprofile varchar(64) DEFAULT NULL,
	voipaccountsid1 integer DEFAULT NULL,
	voipaccountsid2 integer DEFAULT NULL,
	autoscript smallint NOT NULL DEFAULT 0,
	host_id1 integer DEFAULT NULL
		REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	host_id2 integer DEFAULT NULL
		REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	creationdate integer NOT NULL DEFAULT 0,
	moddate integer NOT NULL DEFAULT 0,
	creatorid integer NOT NULL DEFAULT 0,
	modid integer NOT NULL DEFAULT 0,
	netdevid integer DEFAULT NULL
		REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE,
	xmlprovisioning smallint DEFAULT 0,
	properties text DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);
CREATE INDEX gponnokiaonus_gpononumodelsid_idx ON gponnokiaonus (gpononumodelsid);
 
/* gponnokiaonu2customers */
CREATE SEQUENCE gponnokiaonu2customers_id_seq;
CREATE TABLE gponnokiaonu2customers (
	id integer DEFAULT nextval('gponnokiaonu2customers_id_seq'::text) NOT NULL,
	gpononuid integer NOT NULL
		REFERENCES gponnokiaonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
	customersid integer NOT NULL
		REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);
CREATE INDEX gponnokiaonu2customers_gpononuid_idx ON gponnokiaonu2customers (gpononuid);
CREATE INDEX gponnokiaonu2customers_customersid_idx ON gponnokiaonu2customers (customersid);

/* gponnokiaonu2olts */
CREATE SEQUENCE gponnokiaonu2olts_id_seq;
CREATE TABLE gponnokiaonu2olts (
	id integer DEFAULT nextval('gponnokiaonu2olts_id_seq'::text) NOT NULL,
	netdevicesid integer NOT NULL,
	gpononuid integer NOT NULL,
	numport varchar(16) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (gpononuid)
);
CREATE INDEX gponnokiaonu2olts_netdevicesid_idx ON gponnokiaonu2olts (netdevicesid);

/* gponnokiaonusyslog */
CREATE SEQUENCE gponnokiaonusyslog_id_seq;
CREATE TABLE gponnokiaonusyslog (
	id integer DEFAULT nextval('gponnokiaonusyslog_id_seq'::text) NOT NULL,
	time timestamp with time zone,
	onuid integer NOT NULL
		CONSTRAINT gponnokiaonusyslog_onuid_fkey REFERENCES gponnokiaonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
	oltid integer DEFAULT NULL
		CONSTRAINT gponnokiaonusyslog_oltid_fkey REFERENCES gponnokiaolts (id) ON DELETE SET NULL ON UPDATE CASCADE,
	oltname varchar(100) DEFAULT NULL,
	oltip varchar(15) NOT NULL DEFAULT '',
	gponport varchar(20),
	gpononuid integer,
	message text NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX gponnokiaonusyslog_onuid_time_idx ON gponnokiaonusyslog (onuid, time DESC);

/* gponnokiaonumodels */
CREATE SEQUENCE gponnokiaonumodels_id_seq;
CREATE TABLE gponnokiaonumodels (
	id integer DEFAULT nextval('gponnokiaonumodels_id_seq'::text) NOT NULL,
	name varchar(32) NOT NULL,
	description text,
	producer varchar(64) DEFAULT NULL,
	xmltemplate text DEFAULT '',
	xmlfilename text DEFAULT NULL,
	urltemplate varchar(120) DEFAULT NULL,
	xgspon smallint NOT NULL DEFAULT 0,
	swverpland varchar(120) DEFAULT NULL,
	defqosprofile varchar(120) DEFAULT NULL,
	PRIMARY KEY (id)
);

/* gponnokiaonuporttypes */
CREATE SEQUENCE gponnokiaonuporttypes_id_seq;
CREATE TABLE gponnokiaonuporttypes (
	id integer DEFAULT nextval('gponnokiaonuporttypes_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	PRIMARY KEY (id)
);

/* gponnokiaonuports */
CREATE SEQUENCE gponnokiaonuports_id_seq;
CREATE TABLE gponnokiaonuports (
	id integer DEFAULT nextval('gponnokiaonuports_id_seq'::text) NOT NULL,
	onuid integer NOT NULL
		REFERENCES gponnokiaonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
	typeid integer DEFAULT NULL
		REFERENCES gponnokiaonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	portid integer DEFAULT NULL,
	portdisable smallint,
	PRIMARY KEY (id),
	UNIQUE (onuid, typeid, portid)
);

/* gponnokiaonuporttype2models */
CREATE TABLE gponnokiaonuporttype2models (
	gpononuportstypeid integer DEFAULT NULL
		REFERENCES gponnokiaonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	gpononumodelsid integer NOT NULL
		REFERENCES gponnokiaonumodels (id) ON DELETE CASCADE ON UPDATE CASCADE,
	portscount integer NOT NULL,
	portslot integer DEFAULT NULL
);
CREATE INDEX gponnokiaonuporttype2models_gpononuportstypeid_idx ON gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid);

/* gponnokiaonutv */
CREATE SEQUENCE gponnokiaonutv_id_seq;
CREATE TABLE gponnokiaonutv (
	id integer DEFAULT nextval('gponnokiaonutv_id_seq'::text) NOT NULL,
	ipaddr bigint NOT NULL,
	channel varchar(100) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (ipaddr)
);


/* uiconfig */
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'max_onu_to_olt', '128', 'GPON - Domyślna maksymalna liczba ONU przypisanych do portu OLT', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'onumodels_pagelimit', '100', 'Limit wyświetlanych rekordów na jednej stronie listy modeli ONU.', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'onu_pagelimit', '100', 'Limit wyświetlanych rekordów na jednej stronie listy ONU.', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'olt_pagelimit', '100', 'Limit wyświetlanych rekordów na jednej stronie listy OLT.', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'onu_customerlimit', '2', 'Maksymalna liczba Klientów przypisanych do ONU', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'rx_power_weak', '-26', 'Niski poziom odbieranej mocy optycznej', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'rx_power_overload', '-4', 'Wysoki poziom odbieranej mocy optycznej', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'onu_autoscript_debug', '1', '', 1);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'detect_configured_onus', '1', 'Pozwala wylaczyć wyświetlanie skonfigurowanych ont podczas wykrywania', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'syslog', 0, 'Jeśli mamy tabele syslog to możemy logować zdarzenia (custom lms).  syslog(time integer, userid integer, level smallint, what character varying(128), xid integer, message text, detail text)', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-nokia', 'onu_description_template', '', 'obsługiwane symbole specjalne:
%cid% - id klienta,
%city% - miasto klienta,
%street% - ulica klienta,
%house% - number budynku klienta,
%flat% - numer mieszkania klienta,
%lastname% - nazwisko klienta,
%name% - imię klienta,
%fullname% - pełna nazwa klienta', 0);


INSERT INTO gponnokiaonuporttypes (name) VALUES ('eth'), ('10G-eth'), ('pots'), ('video'), ('virtual-eth'), ('wifi');

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSGponNokiaPlugin', '2025100600');

/* ONT LeoLabs */
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('LXT-010G-D', 'LeoLabs', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('LXT-010H-D', 'LeoLabs', 0);
INSERT INTO gponnokiaonumodels (name, description, producer, xgspon) VALUES ('LXT-010S-H', 'GSPON SFP', 'LeoLabs', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('LXT-240G-C1', 'LeoLabs', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('LXE-050X-C', 'LeoLabs', 1);
INSERT INTO gponnokiaonumodels (name, description, producer, xgspon) VALUES ('LXE-010S-H', 'XGSPON SFP', 'LeoLabs', 1);


/*  mapowanie portów LEOX */
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 1, NULL FROM gponnokiaonumodels WHERE name = 'LXT-010G-D';

INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 1, NULL FROM gponnokiaonumodels WHERE name = 'LXT-010H-D';

INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 14 FROM gponnokiaonumodels WHERE name = 'LXT-010S-H';

INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'LXT-240G-C1';

INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'LXE-050X-C';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 14 FROM gponnokiaonumodels WHERE name = 'LXE-050X-C';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 2, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'LXE-050X-C';

INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 2, id, 1, 1 FROM gponnokiaonumodels WHERE name = 'LXE-010S-H';


/*  ONT HALNE */
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-1GE', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-2GRV', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4BX3V-F', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4G', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4G2', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GMV', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GMV2', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GMV3', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GQV', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GQVS', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GXV', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HL-4GXV-F', 'Halny', 0);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HLX-1TLV', 'Halny', 1);
INSERT INTO gponnokiaonumodels (name, producer, xgspon) VALUES ('HLX-TGV', 'Halny', 1);

/* mapowanie portów Halny */

INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 1, 1 FROM gponnokiaonumodels WHERE name = 'HL-1GE';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 2, 1 FROM gponnokiaonumodels WHERE name = 'HL-2GRV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4BX3V-F';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4BX3V-F';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4G';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4G2';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GMV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GMV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GMV2';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GMV2';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GMV3';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GMV3';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GQV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GQV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GQVS';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GQVS';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GXV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GXV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 1, id, 4, 1 FROM gponnokiaonumodels WHERE name = 'HL-4GXV-F';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HL-4GXV-F';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 2, id, 1, 8 FROM gponnokiaonumodels WHERE name = 'HLX-1TLV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HLX-1TLV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 2, id, 1, 8 FROM gponnokiaonumodels WHERE name = 'HLX-TGV';
INSERT INTO gponnokiaonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount, portslot)
SELECT 5, id, 1, 10 FROM gponnokiaonumodels WHERE name = 'HLX-TGV';
