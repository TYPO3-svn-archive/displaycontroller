<?php
/* 
 * Register necessary class names with autoloader
 *
 * $Id$
 */
$extensionPath = t3lib_extMgm::extPath('displaycontroller');
return array(
	'tx_displaycontroller_pi1'			=> $extensionPath . 'pi1/class.tx_displaycontroller_pi1.php',
	'tx_displaycontroller_pi2'			=> $extensionPath . 'pi2/class.tx_displaycontroller_pi2.php',
	'tx_displaycontroller'				=> $extensionPath . 'class.tx_displaycontroller.php',
	'tx_displaycontroller_realurl'		=> $extensionPath . 'class.tx_displaycontroller_realurl.php',
	'tx_displaycontroller_service'		=> $extensionPath . 'class.tx_displaycontroller_service.php',
);
?>
