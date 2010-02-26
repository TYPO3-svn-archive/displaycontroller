#
# Additional fields for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_displaycontroller_provider int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_consumer int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_filtertype varchar(6) DEFAULT '' NOT NULL,
	tx_displaycontroller_datafilter int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_emptyfilter varchar(3) DEFAULT '' NOT NULL,
	tx_displaycontroller_provider2 int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_emptyprovider2 varchar(3) DEFAULT '' NOT NULL,
	tx_displaycontroller_datafilter2 int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_emptyfilter2 varchar(3) DEFAULT '' NOT NULL,
);

#
# Relation table between controller and all components
#
CREATE TABLE tx_displaycontroller_components_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(100) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	component varchar(100) DEFAULT '' NOT NULL,
	rank tinyint(4) DEFAULT '1' NOT NULL,
	local_table varchar(255) DEFAULT '' NOT NULL,
	local_field varchar(255) DEFAULT '' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);
