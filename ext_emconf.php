<?php

########################################################################
# Extension Manager/Repository config file for ext "displaycontroller".
#
# Auto generated 19-04-2012 15:11
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
	'version' => '1.3.0',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'typo3' => '4.3.0-4.6.99',
			'tesseract' => '1.4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"65cf";s:10:"README.txt";s:4:"b948";s:30:"class.tx_displaycontroller.php";s:4:"b9ad";s:39:"class.tx_displaycontroller_debugger.php";s:4:"38b7";s:38:"class.tx_displaycontroller_realurl.php";s:4:"0d14";s:38:"class.tx_displaycontroller_service.php";s:4:"8f8e";s:16:"ext_autoload.php";s:4:"58ed";s:21:"ext_conf_template.txt";s:4:"e283";s:12:"ext_icon.gif";s:4:"f02f";s:17:"ext_localconf.php";s:4:"d4f2";s:14:"ext_tables.php";s:4:"122e";s:14:"ext_tables.sql";s:4:"62a7";s:16:"ext_typeicon.gif";s:4:"0c33";s:13:"locallang.xml";s:4:"eef3";s:27:"locallang_csh_ttcontent.xml";s:4:"a95d";s:16:"locallang_db.xml";s:4:"bc5c";s:15:"wizard_icon.gif";s:4:"b025";s:14:"doc/manual.pdf";s:4:"c50b";s:14:"doc/manual.sxw";s:4:"272e";s:14:"doc/manual.txt";s:4:"cf4d";s:50:"hooks/class.tx_displaycontroller_hooks_tcemain.php";s:4:"eef8";s:23:"lib/kint/Kint.class.php";s:4:"dcea";s:27:"lib/kint/config.default.php";s:4:"ae73";s:22:"lib/kint/view/kint.css";s:4:"899b";s:21:"lib/kint/view/kint.js";s:4:"f06a";s:25:"lib/kint/view/trace.phtml";s:4:"facf";s:38:"pi1/class.tx_displaycontroller_pi1.php";s:4:"3994";s:46:"pi1/class.tx_displaycontroller_pi1_wizicon.php";s:4:"16b3";s:38:"pi2/class.tx_displaycontroller_pi2.php";s:4:"9ddb";s:46:"pi2/class.tx_displaycontroller_pi2_wizicon.php";s:4:"153e";s:16:"static/setup.txt";s:4:"b445";}',
);

?>