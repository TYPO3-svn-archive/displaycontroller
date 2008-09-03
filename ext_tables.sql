#
# Additional fields for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_displaycontroller_provider int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_consumer int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_filtertype varchar(6) DEFAULT '' NOT NULL,
	tx_displaycontroller_datafilter int(11) DEFAULT '0' NOT NULL
);

#
# MM table for Data Filters
#
CREATE TABLE tx_displaycontroller_filters_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(100) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# MM table for Data Providers
#
CREATE TABLE tx_displaycontroller_providers_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(100) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# MM table for Data Consumers
#
CREATE TABLE tx_displaycontroller_consumers_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(100) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);