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
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('basecontroller', 'class.tx_basecontroller.php'));

/**
 * Plugin 'Display Controller (cached)' for the 'displaycontroller' extension.
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_displaycontroller
 */
class tx_displaycontroller extends tslib_pibase {
	public $prefixId	= 'tx_displaycontroller';		// Same as class name
	public $extKey		= 'displaycontroller';	// The extension key.
	protected $controller; // Contains a reference to a controller object

	/**
	 * This method performs various initialisations
	 *
	 * @return	void
	 */
	protected function init($conf) {
		$this->conf = $conf;
		$this->controller = t3lib_div::makeInstance('tx_basecontroller');
	}

	/**
	 * The main method of the plugin
	 *
	 * @param	string		$content: the plugin's content
	 * @param	array		$conf: the plugin's TS configuration
	 * @return	string		The content to display on the website
	 */
	public function main($content, $conf) {
//		$content = t3lib_div::view_array($conf);
//		$content .= t3lib_div::view_array($this->cObj->data);
		$this->init($conf);

			// Get the list of referenced data providers
		$availableProviders = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_providers_mm', "uid_local = '".$this->cObj->data['uid']."'", '', 'sorting ASC');
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$availableProviders[] = $row;
			}
		}
		else {
			// An error occurred querying the database
		}
			// Get the actual data provider
		$provider = $this->controller->getDataProvider($availableProviders);
//t3lib_div::debug($provider);
$content = t3lib_div::view_array($provider->getDataStructure());
		// Get the data consumer
		// Get the data structure from the provider
		// Load the data structure into the consumer
		// Get the rendered data
		return $content;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php']);
}

?>