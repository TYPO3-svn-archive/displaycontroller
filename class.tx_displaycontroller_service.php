<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('tesseract', 'services/class.tx_tesseract_controllerbase.php'));

/**
 * Service for the 'displaycontroller' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller_service extends tx_tesseract_controllerbase {
	/**
     * This method is expected to return the primary provider related to the given display controller instance
     * 
     * @return	dataprovider	Reference to an object implementing the DataProvider interface
     */
	public function getRelatedProvider() {
		t3lib_div::loadTCA('tt_content');
			// Get table where the relation to the provider is stored
		$mmTable = $GLOBALS['TCA']['tt_content']['columns']['tx_displaycontroller_provider']['config']['MM'];
			// Get the provider-relation record
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $mmTable, "uid_local = '" . $this->uid . "' AND component = 'provider' AND rank = '1'");
		$numRows = count($rows);
		if ($numRows == 0) {
			throw new Exception('No provider found');
        } else {
				// Create an instance of the appropriate service
			$provider = t3lib_div::makeInstanceService('dataprovider', $rows[0]['tablenames']);
				// NOTE: loadData() may throw an exception, but we just let it pass at this point
			$provider->loadData(array('table' => $rows[0]['tablenames'], 'uid' => $rows[0]['uid_foreign']));
			return $provider;
        }
    }
}


   
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller_service.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller_service.php']);
}

?>