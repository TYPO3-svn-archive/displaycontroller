<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// Register plug-ins with standard template

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_displaycontroller_pi1.php', '_pi1', 'CType', 1);
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi2/class.tx_displaycontroller_pi2.php', '_pi2', 'CType', 0);

// Initialise known list of consumer and providers (if not yet done (might be if extensions were not loaded in proper order))

if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['providers'])) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['providers'] = array();
if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['consumers'])) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['consumers'] = array();

// Register Controller services for both plug-ins
// NOTE 1: the type of service is "datacontroller" and not "controller" to avoid conflict with a possible, future, core "controller" service
// NOTE 2: the subtype corresponds to the CType
// NOTE 3: the actual class used is the same for both plug-ins (since both plug-ins are the same, except for the cache)

t3lib_extMgm::addService($_EXTKEY,  'datacontroller' /* sv type */,  'tx_displaycontroller_pi1' /* sv key */,
		array(

			'title' => 'Display Controller (cached)',
			'description' => 'Controller service for the (cached) display controller',

			'subtype' => 'displaycontroller_pi1',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY, 'class.tx_displaycontroller_service.php'),
			'className' => 'tx_displaycontroller_service',
		)
	);
t3lib_extMgm::addService($_EXTKEY,  'datacontroller' /* sv type */,  'tx_displaycontroller_pi2' /* sv key */,
		array(

			'title' => 'Display Controller (not cached)',
			'description' => 'Controller service for the (not cached) display controller',

			'subtype' => 'displaycontroller_pi2',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY, 'class.tx_displaycontroller_service.php'),
			'className' => 'tx_displaycontroller_service',
		)
	);
?>