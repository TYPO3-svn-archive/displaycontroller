<?php
// $Id$
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');

	// Add new columns to tt_content
	//
	// A note about MM_match_fields:
	// This structure makes use of a lot of additional fields in the MM table
	// "component" defines whether the related component is a consumer, a provider and a filter
	// "rank" defines the position of the component in the relation chain (1, 2, 3, ...)
	// "local_table" and "local_field" are set so that the relation can be reversed-engineered
	// when looking from the other side of the relation (i.e. the component). They help
	// the component know to which record from which table it is related and in which
	// field to find the type of controller (which is matched to a specific datacontroller service)
$tempColumns = array(
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
			'MM' => 'tx_displaycontroller_components_mm',
			'MM_match_fields' => array(
				'component' => 'consumer',
				'rank' => 1,
				'local_table' => 'tt_content',
				'local_field' => 'CType'
			),
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_dataconsumer',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=500,width=800,status=0,menubar=0,scrollbars=1,resizable=yes'
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
			'size' => 1,
			'minitems' => 1,
			'maxitems' => 1,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_components_mm',
			'MM_match_fields' => array(
				'component' => 'provider',
				'rank' => 1,
				'local_table' => 'tt_content',
				'local_field' => 'CType'
			),
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_dataprovider',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=500,width=800,status=0,menubar=0,scrollbars=1,resizable=yes'
				),
			)
		)
	),
	'tx_displaycontroller_filtertype' => array (		
		'exclude' => 0,
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
			'MM' => 'tx_displaycontroller_components_mm',
			'MM_match_fields' => array(
				'component' => 'filter',
				'rank' => 1,
				'local_table' => 'tt_content',
				'local_field' => 'CType'
			),
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_datafilter',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=500,width=800,status=0,menubar=0,scrollbars=1,resizable=yes'
				),
			)
		)
	),
	'tx_displaycontroller_emptyfilter' => array (		
		'exclude' => 0,
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter',		
		'config' => array (
			'type' => 'radio',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter.I.0', ''),
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter.I.1', 'all'),
			),
		)
	),
	'tx_displaycontroller_provider2' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_provider2',
		'config' => array (
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => '',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_components_mm',
			'MM_match_fields' => array(
				'component' => 'provider',
				'rank' => 2,
				'local_table' => 'tt_content',
				'local_field' => 'CType'
			),
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_dataprovider',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=500,width=800,status=0,menubar=0,scrollbars=1,resizable=yes'
				),
			)
		)
	),
	'tx_displaycontroller_emptyprovider2' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyprovider2',
		'config' => array (
			'type' => 'radio',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter.I.0', ''),
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter.I.1', 'all'),
			),
		)
	),
	'tx_displaycontroller_datafilter2' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_datafilter2',
		'config' => array (
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => '',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_components_mm',
			'MM_match_fields' => array(
				'component' => 'filter',
				'rank' => 2,
				'local_table' => 'tt_content',
				'local_field' => 'CType'
			),
			'wizards' => array(
				'edit' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:displaycontroller/locallang_db.xml:wizards.edit_datafilter',
					'script' => 'wizard_edit.php',
					'icon' => 'edit2.gif',
					'popup_onlyOpenIfSelected' => 1,
					'notNewRecords' => 1,
					'JSopenParams' => 'height=500,width=800,status=0,menubar=0,scrollbars=1,resizable=yes'
				),
			)
		)
	),
	'tx_displaycontroller_emptyfilter2' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter',
		'config' => array (
			'type' => 'radio',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter.I.0', ''),
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_emptyfilter.I.1', 'all'),
			),
		)
	),
);
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);

	// Define showitem property for both plug-ins
$showItem = 'CType;;4;button,hidden,1-1-1, header;;3;;2-2-2,linkToTop;;;;3-3-3';
$showItem .= ', --div--;LLL:EXT:displaycontroller/locallang_db.xml:tabs.dataobjects, tx_displaycontroller_consumer;;;;1-1-1, tx_displaycontroller_provider;;' . $_EXTKEY . '_1;;2-2-2,  tx_displaycontroller_provider2;;' . $_EXTKEY . '_2;;2-2-2, tx_displaycontroller_emptyprovider2';
$showItem .= ', --div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime';

$TCA['tt_content']['types'][$_EXTKEY . '_pi1']['showitem'] = $showItem;
$TCA['tt_content']['types'][$_EXTKEY . '_pi2']['showitem'] = $showItem;
$TCA['tt_content']['ctrl']['typeicons'][$_EXTKEY . '_pi1'] = t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_typeicon.gif';
$TCA['tt_content']['ctrl']['typeicons'][$_EXTKEY . '_pi2'] = t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_typeicon.gif';
$TCA['tt_content']['palettes'][$_EXTKEY . '_1'] = array('showitem' => 'tx_displaycontroller_filtertype, tx_displaycontroller_datafilter, tx_displaycontroller_emptyfilter');
$TCA['tt_content']['palettes'][$_EXTKEY . '_2'] = array('showitem' => 'tx_displaycontroller_datafilter2, tx_displaycontroller_emptyfilter2');

	// Add context sensitive help (csh) for the new fields
t3lib_extMgm::addLLrefForTCAdescr('tt_content', 'EXT:' . $_EXTKEY . '/locallang_csh_ttcontent.xml');

	// Register plug-ins (pi1 is cached, pi2 is not cached)
t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY . '_pi1', t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_typeicon.gif'), 'CType');
t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi2', $_EXTKEY . '_pi2', t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_typeicon.gif'), 'CType');
	// Register wizards for plug-ins
if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_displaycontroller_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY) . 'pi1/class.tx_displaycontroller_pi1_wizicon.php';
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_displaycontroller_pi2_wizicon'] = t3lib_extMgm::extPath($_EXTKEY) . 'pi2/class.tx_displaycontroller_pi2_wizicon.php';
}

	// Declare static TypoScript
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/', 'Generic display controller');

	// Register the name of the table linking the controller and its components
$T3_VAR['EXT']['tesseract']['controller_mm_tables'][] = 'tx_displaycontroller_components_mm';
?>