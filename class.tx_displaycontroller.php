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
*
* $Id$
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('basecontroller', 'class.tx_basecontroller.php'));
require_once(t3lib_extMgm::extPath('basecontroller', 'lib/class.tx_basecontroller_utilities.php'));

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
	protected static $consumer; // Contains a reference to the Data Consumer object
	protected $passStructure = true; // Set to false if Data Consumer should not receive the structure
	protected $debug = false; // Debug flag

	/**
	 * This method performs various initialisations
	 *
	 * @return	void
	 */
	protected function init($conf) {
			// Activate debug mode if BE user is logged in
			// (other conditions may be added at a later point)
		if (!empty($GLOBALS['TSFE']->beUserLogin)) $this->debug = true;
			// Merge the configuration of the pi* plugin with the general configuration
			// defined with plugin.tx_displaycontroller (if defined)
		if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'])) {
			$this->conf = t3lib_div::array_merge_recursive_overrule($conf, $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.']);
		}
		else {
			$this->conf = $conf;
		}
			// Override standard piVars definition
		$this->piVars = t3lib_div::GParrayMerged($this->prefixId);
			// Get an instance of the base controller
		$this->controller = t3lib_div::makeInstance('tx_basecontroller');
			// Finally load some additional data into the basecontroller parser
		$this->loadParserData();
	}

	/**
	 * This method loads additional data into the basecontroller parser, so that it is available for Data Filters
	 * and other places where expressions are used
	 * 
	 * @return	void
	 */
	protected function loadParserData() {
			// Load plug-in's variables into the parser
		tx_expressions_parser::setVars($this->piVars);
			// Load specific configuration into the extra data
		$extraData = array();
		if (is_array($this->conf['context.'])) {
			$extraData = t3lib_div::removeDotsFromTS($this->conf['context.']);
		}
			// Allow loading of additional extra data from hooks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['setExtraDataForParser'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['setExtraDataForParser'] as $className) {
				$hookObject = &t3lib_div::getUserObj($className);
				$extraData = $hookObject->setExtraDataForParser($extraData, $this);
			}
		}
			// Add the extra data to the parser and to the TSFE
		if (count($extraData) > 0) {
			tx_expressions_parser::setExtraData($extraData);
			// TODO: this should not stay
			// This was added so that context can be available in the local TS of the templatedisplay
			// We must find another solution so that the templatedisplay's TS can use the tx_expressions_parser
			$GLOBALS['TSFE']->tesseract = $extraData;
		}
	}

	/**
	 * The main method of the plugin
	 * This method uses a controller object to find the appropriate Data Provider
	 * The data structure from the Data Provider is then passed to the appropriate Data Consumer for rendering
	 *
	 * @param	string		$content: the plugin's content
	 * @param	array		$conf: the plugin's TS configuration
	 * @return	string		The content to display on the website
	 */
	public function main($content, $conf) {
		$this->init($conf);
		$content = '';

			// Handle the secondary provider first
		if (!empty($this->cObj->data['tx_displaycontroller_provider2'])) {
				// Get the secondary data filter, if any
			$secondaryFilter = $this->getEmptyFilter();
			if (!empty($this->cObj->data['tx_displaycontroller_datafilter2'])) {
				$secondaryFilter = $this->defineAdvancedFilter('secondary');
			}
				// Get the secondary provider if necessary,
				// i.e. if the process was not blocked by the advanced filter (by setting the passStructure flag to false)
			if ($this->passStructure) {
				$res = $this->getLocalizedMM('tx_displaycontroller_providers2_mm');
				if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

					try {
						$secondaryProvider = $this->controller->getDataProvider($row);
						$secondaryProvider->setDataFilter($secondaryFilter);
					}
						// Something happened, skip passing the structure to the Data Consumer
					catch (Exception $e) {
						$this->passStructure = false;
						if ($this->debug) {
							echo 'Secondary provider set passStructure to false with the following exception: '.$e->getMessage();
						}
					}
				}
				
			}
		}

			// Handle the primary provider
			// Define the filter (if any)
		try {
			$filter = $this->definePrimaryFilter();
		}
		catch (Exception $e) {
				// Issue warning (error?) if a problem occurred with the filter
			if ($this->debug) {
				echo 'The primary filter threw the following exception: '.$e->getMessage();
			}
		}

			// Get the primary data provider
		$res = $this->getLocalizedMM('tx_displaycontroller_providers_mm');
		if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

				// Get the primary data provider, if necessary
			try {
				if ($this->passStructure) {
					try {
						$primaryProvider = $this->controller->getDataProvider($row, isset($secondaryProvider) ? $secondaryProvider : null);
						$primaryProvider->setDataFilter($filter);
							// If the secondary provider exists and the option was chosen
							// to display everything in the primary provider, no matter what
							// the result from the secondary provider, make sure to set
							// the empty data structure flag to false, otherwise nothing will display
						if (isset($secondaryProvider) && !empty($this->cObj->data['tx_displaycontroller_emptyprovider2'])) {
							$primaryProvider->setEmptyDataStructureFlag(FALSE);
						}
					}
						// Something happened, skip passing the structure to the Data Consumer
					catch (Exception $e) {
						$this->passStructure = false;
						if ($this->debug) {
							echo 'Primary provider set passStructure to false with the following exception: '.$e->getMessage();
						}
					}
				}
	
					// Get the data consumer
				$res = $this->getLocalizedMM('tx_displaycontroller_consumers_mm');
				if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
					$availableConsumer = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					try {
						self::$consumer = $this->controller->getDataConsumer($availableConsumer);
							// Pass reference to current object and appropriate TypoScript to consumer
						self::$consumer->setParentReference($this);
						$typoscriptConfiguration = isset($GLOBALS['TSFE']->tmpl->setup['plugin.'][self::$consumer->getTypoScriptKey()]) ? $GLOBALS['TSFE']->tmpl->setup['plugin.'][self::$consumer->getTypoScriptKey()] : array();
						self::$consumer->setTypoScript($typoscriptConfiguration);
						self::$consumer->setDataFilter($filter);
							// If the structure shoud be passed to the consumer, do it now and get the rendered content
						if ($this->passStructure) {
								// Check if provided data structure is compatible with Data Consumer
							if (self::$consumer->acceptsDataStructure($primaryProvider->getProvidedDataStructure())) {
									// Get the data structure and pass it to the consumer
								$structure = $primaryProvider->getDataStructure();
									// Check if there's a redirection configuration
								$this->handleRedirection($structure);
									// Pass the data structure to the consumer
								self::$consumer->setDataStructure($structure);
									// Start the processing and get the rendered data
								self::$consumer->startProcess();
								$content = self::$consumer->getResult();
							} else {
								// TODO: Issue error if data structures are not compatible between provider and consumer
							}
						}
							// If no structure should be passed (see defineFilter()),
							// don't pass structure :-), but still do the rendering
							// (this gives the opportunity to the consumer to render its own error content, for example)
							// This is achieved by not calling startProcess(), but just getResult()
						else {
							$content = self::$consumer->getResult();
						}
					}
					catch (Exception $e) {
						if ($this->debug) {
							echo 'Could not get the data consumer. The following exception was returned: '.$e->getMessage();
						}
					}
				} else {
					if ($this->debug) {
						echo 'An error occurred querying the database for the data consumer.';
					}
				}
			} // FIXME: is this try/catch block useless?
			catch (Exception $e) {
				if ($this->debug) {
					echo 'An error occurred with the following exception: '.$e->getMessage();
				}
			}
		} else {
			if ($this->debug) {
				echo 'An error occurred querying the database for the primary data provider.';
			}
		}
		return $content;
	}

	/**
	 * This method defines the Data Filter to use depending on the values stored in the database record
	 * It returns the Data Filter structure
	 *
	 * @return	array	Data Filter structure
	 */
	protected function definePrimaryFilter() {
		$filter = $this->getEmptyFilter();
		if (!empty($this->cObj->data['tx_displaycontroller_filtertype'])) {
			switch ($this->cObj->data['tx_displaycontroller_filtertype']) {

					// Simple filter for single view
					// We expect the "table" and "showUid" parameters and assemble a filter based on those values
				case 'single':
					$filter = array();
					$filter['filters'] = array(
											0 => array(
												'table' => $this->piVars['table'],
												'field' => 'uid',
												'conditions' => array(
													0 => array(
														'operator' => '=',
														'value' => $this->piVars['showUid'],
													)
												)
											)
										);
					break;

					// Simple filter for list view
				case 'list':
					$filter = $this->defineListFilter();
					break;

					// Handle advanced data filters
				case 'filter':
					$filter = $this->defineAdvancedFilter();
					break;
			}
		}
		return $filter;
	}

	/**
	 * This method is used to return a clean, empty filter
	 * 
	 * @return	array	Empty filter structure
	 */
	protected function getEmptyFilter() {
		return array('filters' => array());
	}

	/**
	 * This method is used to initialise the filter
	 * This can be either an empty array or some structure already stored in cache
	 *
	 * @param	mixed	$key: a string or a number that identifies a given filter (for example, the uid of a DataFilter record)
	 * @return	array	A filter structure or an empty array
	 */
	protected function initFilter($key = '') {
		$clearCache = isset($this->piVars['clear_cache']) ? $this->piVars['clear_cache'] : t3lib_div::_GP('clear_cache');
		if (!empty($clearCache)) {
			$filter = array();
		}
		else {
			if (empty($key)) $key = 'default';
			$cacheKey = $this->prefixId . '_filterCache_' . $key . '_' . $this->cObj->data['uid'] . '_' . $GLOBALS['TSFE']->id;
			$cache = $GLOBALS['TSFE']->fe_user->getKey('ses', $cacheKey);
			if (isset($cache)) {
				$filter = $cache;
			}
			else {
				$filter = array();
			}
		}
			// Declare hook for extending the initialisation of the filter
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['extendInitFilter'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['extendInitFilter'] as $className) {
				$hookObject = t3lib_div::getUserObj($className);
				$filter = $hookObject->extendInitFilter($filter, $this);
			}
		}
		return $filter;
	}

	/**
	 * This method defines the filter for the default, simple list view
	 * It expects two parameters, "limit" and "page" ,for browsing the list's pages
	 * It will also considere a default sorting scheme represented by the "sort" and "order" parameters
	 *
	 * @return	array	A filter structure
	 */
	protected function defineListFilter() {
			// Initialise the filter
		$filter = $this->initFilter();
		if (!isset($filter['limit'])) $filter['limit'] = array();

			// Handle the page browsing variables
		if (isset($this->piVars['max'])) {
			$filter['limit']['max'] = $this->piVars['max'];
		}
		$filter['limit']['offset'] = isset($this->piVars['page']) ? $this->piVars['page'] : 0;

			// If the limit is still empty after that, consider the default value from TypoScript
		if (empty($filter['limit']['max'])) {
			$filter['limit']['max'] = $this->conf['listView.']['limit'];
		}

			// Handle sorting variables
		if (isset($this->piVars['sort'])) {
			$sortParts = t3lib_div::trimExplode('.', $this->piVars['sort'], 1);
			if (count($sortParts) == 2) {
				$table = $sortParts[0];
				$field = $sortParts[1];
			}
			else {
				$table = '';
				$field = $sortParts[0];
			}
			$order = isset($this->piVars['order']) ? $this->piVars['order'] : 'asc';
			$orderby = array(0 => array('table' => $table, 'field' => $field, 'order' => $order));
			$filter['orderby'] = $orderby;
		}
			// If there were no variables, check a default sorting configuration
		elseif (!empty($this->conf['listView.']['sort'])) {
			$sortParts = t3lib_div::trimExplode('.', $this->conf['listView.']['sort'], 1);
			if (count($sortParts) == 2) {
				$table = $sortParts[0];
				$field = $sortParts[1];
			}
			else {
				$table = '';
				$field = $sortParts[0];
			}
			$order = isset($this->conf['listView.']['order']) ? $this->conf['listView.']['order'] : 'asc';
			$orderby = array(0 => array('table' => $table, 'field' => $field, 'order' => $order));
			$filter['orderby'] = $orderby;
		}

			// Save the filter's hash in session
		$cacheKey = $this->prefixId.'_filterCache_default_'.$this->cObj->data['uid'].'_'.$GLOBALS['TSFE']->id;
		$GLOBALS['TSFE']->fe_user->setKey('ses', $cacheKey, $filter);

		return $filter;
	}

	/**
	 * This method gets a filter structure from a referenced Data Filter
	 *
	 * @param	string	$type: type of filter, either primary (default) or secondary
	 * @return	array	A filter structure
	 */
	protected function defineAdvancedFilter($type = 'primary') {
			// Define variables depending on filter type
		if ($type == 'secondary') {
			$table = 'tx_displaycontroller_filters2_mm';
			$checkField = 'tx_displaycontroller_emptyfilter2';
		}
		else {
			$table = 'tx_displaycontroller_filters_mm';
			$checkField = 'tx_displaycontroller_emptyfilter';
		}
			// Get the data filter
		$res = $this->getLocalizedMM($table);
		if ($res && $availableFilter = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$datafilter = $this->controller->getDataFilter($availableFilter);
				// Initialise the filter
			$filter = $this->initFilter($availableFilter['uid_foreign']);
				// Pass the cached filter to the DataFilter
			$datafilter->setFilter($filter);
			try {
				$filter = $datafilter->getFilterStructure();
					// Store the filter in session
				$cacheKey = $this->prefixId . '_filterCache_' . $availableFilter['uid_foreign'] . '_' . $this->cObj->data['uid'] . '_' . $GLOBALS['TSFE']->id;
				$GLOBALS['TSFE']->fe_user->setKey('ses', $cacheKey, $filter);
					// Here handle case where the "filters" part of the filter is empty
					// If the display nothing flag has been set, we must somehow stop the process
					// The Data Provider should not even be called at all
					// and the Data Consumer should receive an empty (special?) structure
				if (count($filter['filters']) == 0 && empty($this->cObj->data[$checkField])) {
					$this->passStructure = false;
				}
			}
			catch (Exception $e) {
				echo 'Error getting filter: '.$e->getMessage();
			}
		}
		else {
			throw new Exception('No data filter found');
		}
		return $filter;
	}

	/**
	 * This method checks whether a redirection is defined
	 * If yes and if the conditions match, it performs the redirection
	 *
	 * @param	array	$structure: a SDS
	 * @return	void
	 */
	protected function handleRedirection($structure) {
		if (isset($this->conf['redirect.']) && !empty($this->conf['redirect.']['enable'])) {
				// Initialisations
			$redirectConfiguration = $this->conf['redirect.'];
				// Load general SDS information into registers
			$GLOBALS['TSFE']->register['sds.totalCount'] = $structure['totalCount'];
			$GLOBALS['TSFE']->register['sds.count'] = $structure['count'];
				// Create a local cObject for handling the redirect configuration
			$localCObj = t3lib_div::makeInstance('tslib_cObj');
				// If there's at least one record, load it into the cObject
			if ($structure['count'] > 0) {
				$localCObj->start($structure['records'][0]);
			}

				// First interpret the enable property
			if (empty($redirectConfiguration['enable'])) {
				$enable = false;
			}
			else {
				if (isset($this->conf['redirect.']['enable.'])) {
					$enable = $this->cObj->stdWrap($this->conf['redirect.']['enable'], $this->conf['redirect.']['enable.']);
				}
				else {
					$enable = $this->conf['redirect.']['enable'];
				}
			}

				// If the redirection is indeed enabled, continue
			if ($enable) {
					// Get the result of the condition
				if (isset($redirectConfiguration['condition.'])) {
					$condition = $localCObj->checkIf($redirectConfiguration['condition.']);
				}
				else {
					$condition = false;
				}
					// If the condition was true, calculate the URL
				if ($condition) {
					$url = '';
					if (isset($redirectConfiguration['url.'])) {
						$redirectConfiguration['url.']['returnLast'] = 'url';
						$url = $localCObj->typoLink('', $redirectConfiguration['url.']);
					}
					header('Location: '.t3lib_div::locationHeaderUrl($url));
				}
			}
		}
	}
	
	
	/**
	 * Returns localized data for filters, consumers and providers
	 *
	 * @param	string	Name of the mm table
	 * @return	object	Typo3_DB object
	 */
	
	protected function getLocalizedMM($table) {
		if(!empty($this->cObj->data['_LOCALIZED_UID'])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, "uid_local = '".$this->cObj->data['_LOCALIZED_UID']."'");
			if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
				return $res;
			}
		}
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, "uid_local = '".$this->cObj->data['uid']."'");
	}
	
	/**
	 * This method can be called instead of main() for rendering nested elements of a data structure
	 * It avoids the full initialisation by refering to the consumer stored in a static variable
	 *
	 * @param	string		$content: the plugin's content
	 * @param	array		$conf: limited TS configuration for the rendering of the nested element
	 * @return	string		The content to display on the website
	 */
	public function sub($content, $conf) {
		self::$consumer->setTypoScript($conf);
		$content = self::$consumer->getSubResult();
		return $content;
	}

// Getters and setters

	/**
	 * This method returns the plug-in's prefix id
	 *
	 * @return	string	The plug-in's prefix id
	 */
	public function getPrefixId() {
		return $this->prefixId;
	}

}


   
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php']);
}

?>