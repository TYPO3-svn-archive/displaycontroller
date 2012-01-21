<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
 * Debugging output for the 'displaycontroller' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller_debugger implements t3lib_Singleton {
	/**
	 * @var t3lib_PageRenderer Reference to the current page renderer object
	 */
	protected $pageRenderer;

	public function __construct(t3lib_PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}

	/**
	 * Renders all messages and dumps their related data
	 *
	 * @param array $messageQueue List of messages to display
	 * @return string Debug output
	 */
	public function render(array $messageQueue) {
			// Add t3skin stylesheets for proper display, if t3skin is loaded
		if (t3lib_extMgm::isLoaded('t3skin')) {
			$this->pageRenderer->addCssFile(TYPO3_mainDir . t3lib_extMgm::extRelPath('t3skin') . 'stylesheets/structure/element_message.css');
			$this->pageRenderer->addCssFile(TYPO3_mainDir . t3lib_extMgm::extRelPath('t3skin') . 'stylesheets/visual/element_message.css');
		}
		require_once(t3lib_extMgm::extPath('displaycontroller', 'lib/kint/Kint.class.php'));
			// Prepare the output and return it
		$debugOutput = '';
		foreach ($messageQueue as $messageData) {
			t3lib_utility_Debug::debug($messageData, 'messages');
			$debugOutput .= $messageData['message']->render();
			if ($messageData['data'] !== NULL) {
				if (is_array($messageData['data'])) {
					$debugData = $messageData['data'];
				} else {
					$debugData = array($messageData['data']);
				}
				$debugOutput .= @Kint::dump($debugData);
			}
		}

		return $debugOutput;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller_debugger.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller_debugger.php']);
}

?>