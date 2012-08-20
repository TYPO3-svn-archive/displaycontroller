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
 * TCE main hook for the 'displaycontroller' extension.
 *
 * Provides a way to call up a FE page with the correct parameters when hitting the save and view
 * button in the BE
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller_hooks_tcemain {
	/**
	 * @var array Extension configuration
	 */
	protected $extensionConfiguration = array();

	public function __construct() {
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['displaycontroller']);
	}

	/**
	 * Hooks into the TCEmain process to call up a page for preview when the "Save and view" button was clicked
	 *
	 * @param string $status Status of the record
	 * @param string $table Name of the table
	 * @param mixed $id Id of the record (may be a string if the record is new)
	 * @param array $fieldArray Fields of the record
	 * @param t3lib_TCEmain $parentObject Back-reference to the calling object
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, t3lib_TCEmain $parentObject) {
			// If the feature is activated, hook into the preview process to provide a valid preview link
			// (if the "save and view" button was clicked)
		if (isset($GLOBALS['_POST']['_savedokview_x']) && $this->extensionConfiguration['saveAndViewAction']) {
				// Get the actual id if the record is a new one
			if (!is_numeric($id)) {
				$id = $parentObject->substNEWwithIDs[$id];
			}
				// Get the Page TSconfig for the preview page
			$pageId = intval($GLOBALS['_POST']['popViewId']);
			$tsConfig = t3lib_BEfunc::getPagesTSconfig($pageId);
				// Act if some preview information is indeed defined
			if (isset($tsConfig['tx_displaycontroller.'][$table . '.'])) {
					// Change the preview page id to use the configured preview page, if defined
					// (otherwise it will stay on the current page)
				if (!empty($tsConfig['tx_displaycontroller.'][$table . '.']['previewPid'])) {
					$GLOBALS['_POST']['popViewId'] = intval($tsConfig['tx_displaycontroller.'][$table . '.']['previewPid']);
				}
					// Make sure the cache is not used
				$additionalParameters = '&no_cache=1';
					// If the parameters were not defined, use default
				if (empty($tsConfig['tx_displaycontroller.'][$table . '.']['parameters'])) {
					$moreAdditionalParameters = '&tx_displaycontroller[table]=###table###&tx_displaycontroller[showUid]=###id###&L=###lang###';
				} else {
					$moreAdditionalParameters = trim($tsConfig['tx_displaycontroller.'][$table . '.']['parameters']);
						// If the parameters don't start with "&", add it
					if (strpos($moreAdditionalParameters, '&') !== 0) {
						$moreAdditionalParameters = '&' . $moreAdditionalParameters;
					}
				}
					// Prepare replacements for the allowed markers
				$search = array('###id###', '###table###', '###lang###');
				$replacements = array($id, $table);
					// Add the language parameter, if needed
				if (isset($fieldArray['sys_language_uid'])) {
					$replacements[] = $fieldArray['sys_language_uid'];
				} else {
					$replacements[] = 0;
				}
					// Replace the markers and add the parameters
				$additionalParameters .= str_replace(
					$search,
					$replacements,
					$moreAdditionalParameters
				);
					// Assign the additional parameters to the pop-up data
				$GLOBALS['_POST']['popViewId_addParams'] = $additionalParameters . '&tx_displaycontroller_preview=1';
			}
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/hooks/class.tx_displaycontroller_hooks_tcemain.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/hooks/class.tx_displaycontroller_hooks_tcemain.php']);
}

?>