<?php

########################################################################
# Extension Manager/Repository config file for ext "displaycontroller".
#
# Auto generated 18-05-2011 10:19
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Standard Controller - Tesseract project',
	'description' => 'This FE plugin manages relations between Tesseract components and produces output in the FE. More info on http://www.typo3-tesseract.com/',
	'category' => 'plugin',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => 'cms,tesseract',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.2',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'typo3' => '4.3.0-0.0.0',
			'tesseract' => '1.0.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:24:{s:9:"ChangeLog";s:4:"8d12";s:10:"README.txt";s:4:"b948";s:30:"class.tx_displaycontroller.php";s:4:"8190";s:38:"class.tx_displaycontroller_realurl.php";s:4:"0d14";s:38:"class.tx_displaycontroller_service.php";s:4:"8f8e";s:16:"ext_autoload.php";s:4:"a0bf";s:21:"ext_conf_template.txt";s:4:"38b1";s:12:"ext_icon.gif";s:4:"f02f";s:17:"ext_localconf.php";s:4:"8cc2";s:14:"ext_tables.php";s:4:"2169";s:14:"ext_tables.sql";s:4:"62a7";s:16:"ext_typeicon.gif";s:4:"0c33";s:13:"locallang.xml";s:4:"d932";s:27:"locallang_csh_ttcontent.xml";s:4:"a95d";s:16:"locallang_db.xml";s:4:"bc5c";s:15:"wizard_icon.gif";s:4:"b025";s:14:"doc/manual.pdf";s:4:"e840";s:14:"doc/manual.sxw";s:4:"50b2";s:14:"doc/manual.txt";s:4:"c8d7";s:38:"pi1/class.tx_displaycontroller_pi1.php";s:4:"3994";s:46:"pi1/class.tx_displaycontroller_pi1_wizicon.php";s:4:"16b3";s:38:"pi2/class.tx_displaycontroller_pi2.php";s:4:"9ddb";s:46:"pi2/class.tx_displaycontroller_pi2_wizicon.php";s:4:"153e";s:16:"static/setup.txt";s:4:"b445";}',
);

?>