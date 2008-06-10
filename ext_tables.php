<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem']='CType;;4;button;1-1-1, header;;3;;2-2-2';


t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY.'_pi1'),'CType');


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types'][$_EXTKEY.'_pi2']['showitem']='CType;;4;button;1-1-1, header;;3;;2-2-2';


t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi2', $_EXTKEY.'_pi2'),'CType');


if (TYPO3_MODE == 'BE')	{
	include_once(t3lib_extMgm::extPath('displaycontroller').'class.tx_displaycontroller_tt_content_tx_displaycontroller_model.php');
}


if (TYPO3_MODE == 'BE')	{
	include_once(t3lib_extMgm::extPath('displaycontroller').'class.tx_displaycontroller_tt_content_tx_displaycontroller_view.php');
}

$tempColumns = array (
	'tx_displaycontroller_model' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_model',		
		'config' => array (
			'type' => 'select',
			'items' => array (
				array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_model.I.0', '0'),
			),
			'itemsProcFunc' => 'tx_displaycontroller_tt_content_tx_displaycontroller_model->main',	
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
			'itemsProcFunc' => 'tx_displaycontroller_tt_content_tx_displaycontroller_view->main',	
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


t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_content','tx_displaycontroller_model;;;;1-1-1, tx_displaycontroller_view, tx_displaycontroller_dataquery, tx_displaycontroller_focus');
?>