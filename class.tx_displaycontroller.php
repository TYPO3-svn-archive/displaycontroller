<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Francois Suter (Cobweb) <typo3@cobweb.ch>
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

require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'Display Controller (cached)' for the 'displaycontroller' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller extends tslib_pibase implements tx_tesseract_datacontroller_output {
	public $prefixId	= 'tx_displaycontroller';		// Same as class name
	public $extKey		= 'displaycontroller';	// The extension key.
	/**
	 * Contains a reference to the frontend Data Consumer object
	 * @var tx_tesseract_feconsumerbase
	 */
	protected $consumer;
	protected $passStructure = TRUE; // Set to FALSE if Data Consumer should not receive the structure
	/**
	 * @var array General extension configuration
	 */
	protected $extensionConfiguration = array();
	/**
	 * @var bool General debugging flag
	 */
	protected $debug = FALSE;
	/**
	 * @var bool Debug to output or not
	 */
	protected $debugToOutput = FALSE;
	/**
	 * @var bool Debug to devlog or not
	 */
	protected $debugToDevLog = FALSE;
	/**
	 * @var array List of debug messages
	 */
	protected $messageQueue = array();

	public function __construct() {
			// Read the general configuration and initialize the debug flags
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		if (!empty($this->extensionConfiguration['debug'])) {
			$this->debug = TRUE;
			switch ($this->extensionConfiguration['debug']) {
				case 'output':
					$this->debugToOutput = TRUE;
					break;
				case 'devlog':
					$this->debugToDevLog = TRUE;
					break;
				case 'both':
					$this->debugToOutput = TRUE;
					$this->debugToDevLog = TRUE;
					break;

					// Turn off all debugging if no valid value was entered
				default:
					$this->debug = FALSE;
					$this->debugToOutput = FALSE;
					$this->debugToDevLog = FALSE;
			}
		}
	}

	/**
	 * This method performs various initialisations
	 *
	 * @param array $conf TypoScript configuration array
	 * @return	void
	 */
	protected function init($conf) {
			// Merge the configuration of the pi* plugin with the general configuration
			// defined with plugin.tx_displaycontroller (if defined)
		if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.'])) {
			$this->conf = t3lib_div::array_merge_recursive_overrule($GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'], $conf);
		} else {
			$this->conf = $conf;
		}
			// Override standard piVars definition
		$this->piVars = t3lib_div::_GPmerged($this->prefixId);
			// Finally load some additional data into the parser
		$this->loadParserData();
	}

	/**
	 * This method loads additional data into the parser, so that it is available for Data Filters
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
		$filter = array();

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
				try {
						// Get the secondary provider's information
					$secondaryProviderData = $this->getComponentData('provider', 2);
					try {
							// Get the corresponding component
						$secondaryProvider = $this->getDataProvider($secondaryProviderData);
						$secondaryProvider->setDataFilter($secondaryFilter);
					}
						// Something happened, skip passing the structure to the Data Consumer
					catch (Exception $e) {
						$this->passStructure = FALSE;
						if ($this->debug) {
							echo 'Secondary provider set passStructure to false with the following exception: ' . $e->getMessage();
						}
					}
				}
				catch (Exception $e) {
					// Nothing to do if no secondary provider was found
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
				echo 'The primary filter threw the following exception: ' . $e->getMessage();
			}
		}

			// Get the primary data provider
		try {
			$primaryProviderData = $this->getComponentData('provider', 1);
				// Get the primary data provider, if necessary
			if ($this->passStructure) {
				try {
					$primaryProvider = $this->getDataProvider($primaryProviderData, isset($secondaryProvider) ? $secondaryProvider : null);
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
					$this->passStructure = FALSE;
					if ($this->debug) {
						echo 'Primary provider set passStructure to false with the following exception: '.$e->getMessage();
					}
				}
			}

				// Get the data consumer
			try {
					// Get the consumer's information
				$consumerData = $this->getComponentData('consumer');
				try {
						// Get the corresponding Data Consumer component
					$this->consumer = tx_tesseract::getComponent(
						'dataconsumer',
						$consumerData['tablenames'],
						array('table' => $consumerData['tablenames'], 'uid' => $consumerData['uid_foreign']),
						$this
					);
						// Pass appropriate TypoScript to consumer
					$typoscriptKey = $this->consumer->getTypoScriptKey();
					$typoscriptConfiguration = isset($GLOBALS['TSFE']->tmpl->setup['plugin.'][$typoscriptKey]) ? $GLOBALS['TSFE']->tmpl->setup['plugin.'][$typoscriptKey] : array();
					$this->consumer->setTypoScript($typoscriptConfiguration);
					$this->consumer->setDataFilter($filter);
						// If the structure should be passed to the consumer, do it now and get the rendered content
					if ($this->passStructure) {
							// Check if Data Provider can provide the right structure for the Data Consumer
						if ($primaryProvider->providesDataStructure($this->consumer->getAcceptedDataStructure())) {
								// Get the data structure and pass it to the consumer
							$structure = $primaryProvider->getDataStructure();
								// Check if there's a redirection configuration
							$this->handleRedirection($structure);
								// Pass the data structure to the consumer
							$this->consumer->setDataStructure($structure);
								// Start the processing and get the rendered data
							$this->consumer->startProcess();
							$content = $this->consumer->getResult();
						} else {
							// TODO: Issue error if data structures are not compatible between provider and consumer
						}
					}
						// If no structure should be passed (see defineFilter()),
						// don't pass structure :-), but still do the rendering
						// (this gives the opportunity to the consumer to render its own error content, for example)
						// This is achieved by not calling startProcess(), but just getResult()
					else {
						$content = $this->consumer->getResult();
					}
				}
				catch (Exception $e) {
					if ($this->debug) {
						echo 'Could not get the data consumer. The following exception was returned: '.$e->getMessage();
					}
				}
			}
			catch (Exception $e) {
				if ($this->debug) {
					echo 'An error occurred querying the database for the data consumer.';
				}
			}
		}
		catch (Exception $e) {
			if ($this->debug) {
				echo 'An error occurred querying the database for the primary data provider.';
			}
		}
			// If debugging to output is active, prepend content with debugging messages
		if ($this->debugToOutput) {
			$content = $this->renderMessageQueue() . $content;
		}
		return $content;
	}

	/**
	 * Renders all messages and dumps their related data
	 *
	 * @return string Debug output
	 */
	protected function renderMessageQueue() {
		$debugOutput = '';
		foreach ($this->messageQueue as $messageList) {
			foreach ($messageList as $messageData) {
				$debugOutput .= $messageData['message']->render();
				if ($messageData['data'] !== NULL) {
					if (is_array($messageData['data'])) {
						$debugData = $messageData['data'];
					} else {
						$debugData = array($messageData['data']);
					}
					$debugOutput .= t3lib_utility_Debug::viewArray($debugData);
				}
			}
		}

		return $debugOutput;
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
		$filter = array();
		$clearCache = isset($this->piVars['clear_cache']) ? $this->piVars['clear_cache'] : t3lib_div::_GP('clear_cache');
			// If cache is not cleared, retrieve cached filter
		if (empty($clearCache)) {
			if (empty($key)) {
				$key = 'default';
			}
			$cacheKey = $this->prefixId . '_filterCache_' . $key . '_' . $this->cObj->data['uid'] . '_' . $GLOBALS['TSFE']->id;
			$cache = $GLOBALS['TSFE']->fe_user->getKey('ses', $cacheKey);
			if (isset($cache)) {
				$filter = $cache;
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
			$table = '';
			$field = $sortParts[0];
			if (count($sortParts) == 2) {
				$table = $sortParts[0];
				$field = $sortParts[1];
			}
			$order = isset($this->piVars['order']) ? $this->piVars['order'] : 'asc';
			$orderby = array(0 => array('table' => $table, 'field' => $field, 'order' => $order));
			$filter['orderby'] = $orderby;

			// If there were no variables, check a default sorting configuration
		} elseif (!empty($this->conf['listView.']['sort'])) {
			$sortParts = t3lib_div::trimExplode('.', $this->conf['listView.']['sort'], 1);
			$table = '';
			$field = $sortParts[0];
			if (count($sortParts) == 2) {
				$table = $sortParts[0];
				$field = $sortParts[1];
			}
			$order = isset($this->conf['listView.']['order']) ? $this->conf['listView.']['order'] : 'asc';
			$orderby = array(0 => array('table' => $table, 'field' => $field, 'order' => $order));
			$filter['orderby'] = $orderby;
		}

			// Save the filter's hash in session
		$cacheKey = $this->prefixId . '_filterCache_default_' . $this->cObj->data['uid'] . '_' . $GLOBALS['TSFE']->id;
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
		$filter = array();
			// Define rank based on call parameter
		$rank = 1;
		$checkField = 'tx_displaycontroller_emptyfilter';
		if ($type == 'secondary') {
			$rank = 2;
			$checkField = 'tx_displaycontroller_emptyfilter2';
		}
			// Get the data filter
		try {
				// Get the filter's information
			$filterData = $this->getComponentData('filter', $rank);
				// Get the corresponding Data Filter component
				/** @var $datafilter tx_tesseract_datafilter */
			$datafilter = tx_tesseract::getComponent(
				'datafilter',
				$filterData['tablenames'],
				array('table' => $filterData['tablenames'], 'uid' => $filterData['uid_foreign']),
				$this
			);
				// Initialise the filter
			$filter = $this->initFilter($filterData['uid_foreign']);
				// Pass the cached filter to the DataFilter
			$datafilter->setFilter($filter);
			try {
				$filter = $datafilter->getFilterStructure();
					// Store the filter in session
				$cacheKey = $this->prefixId . '_filterCache_' . $filterData['uid_foreign'] . '_' . $this->cObj->data['uid'] . '_' . $GLOBALS['TSFE']->id;
				$GLOBALS['TSFE']->fe_user->setKey('ses', $cacheKey, $filter);
					// Here handle case where the "filters" part of the filter is empty
					// If the display nothing flag has been set, we must somehow stop the process
					// The Data Provider should not even be called at all
					// and the Data Consumer should receive an empty (special?) structure
				if ($datafilter->isFilterEmpty() && empty($this->cObj->data[$checkField])) {
					$this->passStructure = FALSE;
				}
			}
			catch (Exception $e) {
				echo 'Error getting filter: '.$e->getMessage();
			}
		}
		catch (Exception $e) {
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
				/** @var $localCObj tslib_cObj */
			$localCObj = t3lib_div::makeInstance('tslib_cObj');
				// If there's at least one record, load it into the cObject
			if ($structure['count'] > 0) {
				$localCObj->start($structure['records'][0]);
			}

				// First interpret the enable property
			$enable = FALSE;
			if (!empty($redirectConfiguration['enable'])) {
				if (isset($this->conf['redirect.']['enable.'])) {
					$enable = $this->cObj->stdWrap($this->conf['redirect.']['enable'], $this->conf['redirect.']['enable.']);
				} else {
					$enable = $this->conf['redirect.']['enable'];
				}
			}

				// If the redirection is indeed enabled, continue
			if ($enable) {
					// Get the result of the condition
				$condition = FALSE;
				if (isset($redirectConfiguration['condition.'])) {
					$condition = $localCObj->checkIf($redirectConfiguration['condition.']);
				}
					// If the condition was true, calculate the URL
				if ($condition) {
					$url = '';
					if (isset($redirectConfiguration['url.'])) {
						$redirectConfiguration['url.']['returnLast'] = 'url';
						$url = $localCObj->typoLink('', $redirectConfiguration['url.']);
					}
					header('Location: ' . t3lib_div::locationHeaderUrl($url));
				}
			}
		}
	}

	/**
	 * Retrieves information about a component related to the controller
	 * An exception is thrown if none is found
	 *
	 * @param	string	$component: type of component (provider, consumer, filter)
	 * @param	integer	$rank: level of the component (1 = primary, 2 = secondary)
	 * @return	array	Database record from the MM-table linking the controller to its components
	 */
	protected function getComponentData($component, $rank = 1) {
			// Assemble base WHERE clause
		$whereClause = "component = '" . $component . "' AND rank = '" . $rank . "'";
			// Select the right uid for building the relation
			// If a _ORIG_uid is defined (i.e. we're in a workspace), use it preferentially
			// Otherwise, take the localized uid (i.e. we're using a translation), if it exists
		$referenceUid = $this->cObj->data['uid'];
		if (!empty($this->cObj->data['_ORIG_uid'])) {
			$referenceUid = $this->cObj->data['_ORIG_uid'];
		} elseif (!empty($this->cObj->data['_LOCALIZED_UID'])) {
			$referenceUid = $this->cObj->data['_LOCALIZED_UID'];
		}
		$where = $whereClause . " AND uid_local = '" . intval($referenceUid) . "'";
			// Query the database and return the fetched data
			// If the query fails or turns up no results, throw an exception
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_components_mm', $where);
		if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$componentData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		} else {
			$message = 'No component of type ' . $component . ' and level ' . $rank . ' found';
			throw new Exception($message, 1265577739);
		}
		return $componentData;
	}

	/**
	 * Gets a data provider.
	 *
	 * If a secondary provider is defined, it is fed into the first one
	 *
	 * @param array $providerInfo Information about a provider related to the controller
	 * @param tx_tesseract_dataprovider $secondaryProvider An instance of an object with a DataProvider interface
	 * @return tx_tesseract_dataprovider Object with a DataProvider interface
	 */
	public function getDataProvider($providerInfo, tx_tesseract_dataprovider $secondaryProvider = null) {
			// Get the related data providers
		$numProviders = count($providerInfo);
		if ($numProviders == 0) {
				// No provider, throw exception
			throw new Exception('No provider was defined', 1269414211);
		} else {
				// Get the Data Provider Component
				/** @var $provider tx_tesseract_dataprovider */
			$provider = tx_tesseract::getComponent(
				'dataprovider',
				$providerInfo['tablenames'],
				array('table' => $providerInfo['tablenames'], 'uid' => $providerInfo['uid_foreign']),
				$this
			);
				// If a secondary provider is defined and the types are compatible,
				// load it into the newly defined provider
			if (isset($secondaryProvider)) {
				if ($secondaryProvider->providesDataStructure($provider->getAcceptedDataStructure())) {
					$inputDataStructure = $secondaryProvider->getDataStructure();
						// If the secondary provider returned no list of items,
						// force provider to return an empty structure
					if ($inputDataStructure['count'] == 0) {
						$provider->initEmptyDataStructure($inputDataStructure['uniqueTable']);

						// Otherwise pass structure to the provider
					} else {
						$provider->setDataStructure($inputDataStructure);
					}
				}
					// Providers are not compatible, throw exception
				else {
					throw new Exception('Incompatible structures between primary and secondary providers', 1269414231);
				}
			}
			return $provider;
		}
	}

// tx_tesseract_datacontroller_output interface methods

	/**
	 * This method returns the plug-in's prefix id
	 *
	 * @return	string	The plug-in's prefix id
	 */
	public function getPrefixId() {
		return $this->prefixId;
	}

	/**
	 * Adds a debugging message to the controller's internal message queue
	 *
	 * @param string $key A key identifying a set the message belongs to (typically the calling extension's key)
	 * @param string $message Text of the message
	 * @param string $title An optional title for the message
	 * @param int $status A status/severity level for the message, based on the class constants from t3lib_FlashMessage
	 * @param mixed $debugData An optional variable containing additional debugging information
	 * @return void
	 */
	public function addMessage($key, $message, $title = '', $status = t3lib_FlashMessage::INFO, $debugData = NULL) {
			// Store the message only if debugging is active
		if ($this->debug) {
			if (!is_array($this->messageQueue[$key])) {
				$this->messageQueue[$key] = array();
			}
				// The message data that corresponds to the Flash Message is stored directly as a Flash Message object,
				// as this performs input validation on the data
			$this->messageQueue[$key][] = array(
				'message' => t3lib_div::makeInstance('t3lib_FlashMessage', $message, $title, $status),
				'data' => $debugData
			);
		}
	}

	/**
	 * Returns the complete message queue
	 *
	 * @return array The message queue
	 */
	public function getMessageQueue() {
		return $this->messageQueue;
	}

	/**
	 * Returns the message queue for a given key
	 *
	 * @param string $key The key to return the messages for
	 * @return array The message queue for the given key
	 */
	public function getMessageQueueForKey($key) {
		$messageList = array();
		if (isset($this->messageQueue[$key])) {
			$messageList = $this->messageQueue[$key];
		}
		return $messageList;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php']);
}

?>