<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// Register plug-ins with standard template

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_displaycontroller_pi1.php', '_pi1', 'CType', 1);
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi2/class.tx_displaycontroller_pi2.php', '_pi2', 'CType', 0);
?>