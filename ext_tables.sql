#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_displaycontroller_model int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_view int(11) DEFAULT '0' NOT NULL,
	tx_displaycontroller_dataquery text,
	tx_displaycontroller_focus text
);