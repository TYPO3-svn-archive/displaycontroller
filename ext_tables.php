<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Register plug-ins (pi1 is cached, pi2 is not cached

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem']='CType;;4;button;1-1-1, header;;3;;2-2-2';
t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY.'_pi1'),'CType');

$TCA['tt_content']['types'][$_EXTKEY.'_pi2']['showitem']='CType;;4;button;1-1-1, header;;3;;2-2-2';
t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi2', $_EXTKEY.'_pi2'),'CType');

// Add new columns to tt_content

$tempColumns = array (
	'tx_displaycontroller_model' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_model',		
		'config' => array (
			'type' => 'select',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_model.I.0', '0'),
			),
			'size' => 1,	
			'maxitems' => 1,
		)
	),
	'tx_displaycontroller_view' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_view',		
		'config' => array (
			'type' => 'select',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_view.I.0', '0'),
			),
			'size' => 1,	
			'maxitems' => 1,
		)
	),
	'tx_displaycontroller_dataquery' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_dataquery',		
		'config' => array (
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => 'tx_dataquery_queries',	
			'size' => 1,	
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_displaycontroller_focus' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_focus',		
		'config' => array (
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => 'toto',	
			'size' => 1,	
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
);

t3lib_extMgm::addTCAcolumns('tt_content',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_content','tx_displaycontroller_model;;;;1-1-1, tx_displaycontroller_view, tx_displaycontroller_dataquery, tx_displaycontroller_focus');
?>