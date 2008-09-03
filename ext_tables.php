<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');

// Add new columns to tt_content

$tempColumns = array(
	'tx_displaycontroller_filtertype' => array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_filtertype',		
		'config' => array (
			'type' => 'radio',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_filtertype.I.0', ''),
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_filtertype.I.1', 'single'),
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_filtertype.I.2', 'list'),
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_filtertype.I.3', 'filter'),
			),
		)
	),
	'tx_displaycontroller_datafilter' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_datafilter',		
		'config' => array (
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => '',	
			'size' => 1,	
			'minitems' => 0,
			'maxitems' => 1,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_filters_mm',
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_datafilter',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=400,width=600,status=0,menubar=0,scrollbars=1'
				),
			)
		)
	),
	'tx_displaycontroller_provider' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_provider',		
		'config' => array (
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => '',	
			'size' => 2,	
			'minitems' => 1,
			'maxitems' => 2,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_providers_mm',
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_dataprovider',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=400,width=600,status=0,menubar=0,scrollbars=1'
				),
			)
		)
	),
	'tx_displaycontroller_consumer' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_consumer',		
		'config' => array(
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => '',	
			'size' => 1,	
			'minitems' => 1,
			'maxitems' => 1,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_consumers_mm',
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_dataconsumer',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=400,width=600,status=0,menubar=0,scrollbars=1'
				),
			)
		)
	),
);
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);

// Define showitem property for both plug-ins

$showItem = 'CType;;4;button,hidden,1-1-1, header;;3;;2-2-2,linkToTop;;;;3-3-3';
$showItem .= ', --div--;LLL:EXT:displaycontroller/locallang_db.xml:tabs.dataobjects, tx_displaycontroller_filtertype, tx_displaycontroller_datafilter, tx_displaycontroller_provider, tx_displaycontroller_consumer';
$showItem .= ', --div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime';

$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem'] = $showItem;
$TCA['tt_content']['types'][$_EXTKEY.'_pi2']['showitem'] = $showItem;
$TCA['tt_content']['ctrl']['typeicons'][$_EXTKEY.'_pi1'] = t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif';
$TCA['tt_content']['ctrl']['typeicons'][$_EXTKEY.'_pi2'] = t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif';

// Register plug-ins (pi1 is cached, pi2 is not cached)

t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY.'_pi1', t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif'), 'CType');
t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi2', $_EXTKEY.'_pi2', t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif'), 'CType');
?>