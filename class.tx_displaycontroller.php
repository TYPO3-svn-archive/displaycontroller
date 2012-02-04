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

require_once(t3lib_extMgm::extPath('tesseract', 'base/class.tx_tesseract_picontrollerbase.php'));

/**
 * Plugin 'Display Controller (cached)' for the 'displaycontroller' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller extends tx_tesseract_picontrollerbase {
	public $prefixId = 'tx_displaycontroller';		// Same as class name
	public $extKey		= 'displaycontroller';	// The extension key.
	/**
	 * Contains a reference to the frontend Data Consumer object
	 * @var tx_tesseract_feconsumerbase
	 */
	protected $consumer;
	/**
	 * @var bool FALSE if Data Consumer should not receive the structure
	 */
	protected $passStructure = TRUE;
	/**
	 * @var array General extension configuration
	 */
	protected $extensionConfiguration = array();
	/**
	 * @var bool Debug to output or not
	 */
	protected $debugToOutput = FALSE;
	/**
	 * @var bool Debug to devlog or not
	 */
	protected $debugToDevLog = FALSE;
	/**
	 * @var int Minimum level of message to be logged. Default is all.
	 */
	protected $debugMinimumLevel = -1;

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
			// Make sure the minimum debugging level is set and has a correct value
		if (isset($this->extensionConfiguration['minDebugLevel'])) {
			$level = intval($this->extensionConfiguration['minDebugLevel']);
			if ($level >= -1 && $level <= 3) {
				$this->debugMinimumLevel = $level;
			}
		}
	}

	/**
	 * Overrides the default pi_loadLL method, as displaycontroller provides two plugins sharing the same locallang files
	 *
	 * NOTE: TypoScript override of language labels is not implemented
	 *
	 * @return	void
	 */
	public function pi_loadLL() {
			// Read the strings in the required charset
		$this->LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->extKey . '/locallang.xml', $this->LLkey, $GLOBALS['TSFE']->renderCharset);
		if ($this->altLLkey) {
			$this->LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->extKey . '/locallang.xml', $this->altLLkey);
		}
		$this->LOCAL_LANG_loaded = 1;
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
			// Load the language labels
		$this->pi_loadLL();
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
		$secondaryProvider = $this->initializeSecondaryProvider();

			// Handle the primary provider
			// Define the filter (if any)
		try {
			$filter = $this->definePrimaryFilter();
			$this->addMessage(
				$this->extKey,
				$this->pi_getLL('info.calculated_filter'),
				$this->pi_getLL('info.primary_filter'),
				t3lib_FlashMessage::INFO,
				$filter
			);
		}
		catch (Exception $e) {
				// Issue error if a problem occurred with the filter
			$this->addMessage(
				$this->extKey,
				$e->getMessage() . ' (' . $e->getCode() . ')',
				$this->pi_getLL('error.primary_filter'),
				t3lib_FlashMessage::ERROR
			);
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
					$this->addMessage(
						$this->extKey,
						$e->getMessage() . ' (' . $e->getCode() . ')',
						$this->pi_getLL('error.primary_provider_interrupt'),
						t3lib_FlashMessage::WARNING
					);
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
							$this->addMessage(
								$this->extKey,
								$this->pi_getLL('error.incompatible_provider_consumer'),
								'',
								t3lib_FlashMessage::ERROR
							);
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
					$this->addMessage(
						$this->extKey,
						$e->getMessage() . ' (' . $e->getCode() . ')',
						$this->pi_getLL('error.no_consumer'),
						t3lib_FlashMessage::ERROR
					);
				}
			}
			catch (Exception $e) {
				$this->addMessage(
					$this->extKey,
					$e->getMessage() . ' (' . $e->getCode() . ')',
					$this->pi_getLL('error.no_consumer'),
					t3lib_FlashMessage::ERROR
				);
			}
		}
		catch (Exception $e) {
			$this->addMessage(
				$this->extKey,
				$e->getMessage() . ' (' . $e->getCode() . ')',
				$this->pi_getLL('error.no_primary_provider'),
				t3lib_FlashMessage::ERROR
			);
		}

			// If debugging to output is active, prepend content with debugging messages
		$content = $this->writeDebugOutput() . $content;
		return $content;
	}

	/**
	 * Initializes the secondary provider, possibly with its secondary filter
	 *
	 * @return null|tx_tesseract_dataprovider
	 */
	protected function initializeSecondaryProvider() {
		$secondaryProvider = NULL;
		if (!empty($this->cObj->data['tx_displaycontroller_provider2'])) {
				// Get the secondary data filter, if any
			$secondaryFilter = $this->getEmptyFilter();
			if (!empty($this->cObj->data['tx_displaycontroller_datafilter2'])) {
				$secondaryFilter = $this->defineAdvancedFilter('secondary');
				$this->addMessage(
					$this->extKey,
					$this->pi_getLL('info.calculated_filter'),
					$this->pi_getLL('info.secondary_filter'),
					t3lib_FlashMessage::INFO,
					$secondaryFilter
				);
			}
				// Get the secondary provider if necessary,
				// i.e. if the process was not blocked by the advanced filter (by setting the passStructure flag to false)
			if ($this->passStructure) {
				try {
						// Get the secondary provider's information
					$secondaryProviderData = $this->getComponentData('provider', 2);
					try {
							// Get the corresponding component
						$secondaryProviderObject = $this->getDataProvider($secondaryProviderData);
						$secondaryProvider = clone $secondaryProviderObject;
						$secondaryProvider->setDataFilter($secondaryFilter);
					}
						// Something happened, skip passing the structure to the Data Consumer
					catch (Exception $e) {
						$this->passStructure = FALSE;
						$this->addMessage(
							$this->extKey,
							$e->getMessage() . ' (' . $e->getCode() . ')',
							$this->pi_getLL('error.secondary_provider_interrupt'),
							t3lib_FlashMessage::WARNING
						);
					}
				}
				catch (Exception $e) {
					$this->addMessage(
						$this->extKey,
						$e->getMessage() . ' (' . $e->getCode() . ')',
						$this->pi_getLL('error.no_secondary_provider'),
						t3lib_FlashMessage::ERROR
					);
				}
			}
		}
		return $secondaryProvider;
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
	 * It will also consider a default sorting scheme represented by the "sort" and "order" parameters
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
				$this->addMessage(
					$this->extKey,
					$e->getMessage() . ' (' . $e->getCode() . ')',
					$this->pi_getLL('error.get_filter'),
					t3lib_FlashMessage::WARNING
				);
			}
		}
		catch (Exception $e) {
			throw new Exception($this->pi_getLL('exception.no_filter'), 1326454151);
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
			$message = sprintf($this->pi_getLL('exception.no_component'), $component, $rank);
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
			throw new Exception($this->pi_getLL('exception.no_provider'), 1269414211);
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
					throw new Exception($this->pi_getLL('exception.incompatible_providers'), 1269414231);
				}
			}
			return $provider;
		}
	}

// Override tx_tesseract_pidatacontroller_output interface methods

	/**
	 * Adds a debugging message to the controller's internal message queue
	 *
	 * @param string $key A key identifying the calling component (typically an extension's key)
	 * @param string $message Text of the message
	 * @param string $title An optional title for the message
	 * @param int $status A status/severity level for the message, based on the class constants from t3lib_FlashMessage
	 * @param mixed $debugData An optional variable containing additional debugging information
	 * @return void
	 */
	public function addMessage($key, $message, $title = '', $status = t3lib_FlashMessage::INFO, $debugData = NULL) {
			// Store the message only if debugging is active
		if ($this->debug) {
				// Validate status
				// Fall back to default if invalid
			$status = intval($status);
			if ($status < t3lib_FlashMessage::NOTICE || $status > t3lib_FlashMessage::ERROR) {
				$status = t3lib_FlashMessage::INFO;
			}
				// Match status to devLog levels
				// (which follow a more logical progression than Flash Message levels)
			switch ($status) {
				case t3lib_FlashMessage::OK:
					$level = -1;
					break;
				case t3lib_FlashMessage::NOTICE:
					$level = 1;
					break;
				default:
					$level = $status + 1;
			}
				// Actually store the message only if it meets the minimum severity level
			if ($level >= $this->debugMinimumLevel) {
					// Prepend title, if any, with key
				$fullTitle = '[' . $key . ']' . ((empty($title)) ? '' : ' ' . $title);
					// The message data that corresponds to the Flash Message is stored directly as a Flash Message object,
					// as this performs input validation on the data
					/** @var $flashMessage t3lib_FlashMessage */
				$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage', $message, $fullTitle, $status);
				$this->messageQueue[] = array(
					'message' => $flashMessage,
					'data' => $debugData
				);
					// Additionally write the message to the devLog if needed
				if ($this->debugToDevLog) {
						// Make sure debug data is either NULL or array
					$extraData = NULL;
					if ($debugData !== NULL) {
						if (is_array($debugData)) {
							$extraData = $debugData;
						} else {
							$extraData = array($debugData);
						}
					}
					t3lib_div::devLog($flashMessage->getTitle() . ': ' . $flashMessage->getMessage(), $key, $level, $extraData);
				}
			}
		}
	}

	/**
	 * Prepares the debugging output, if so configured, and returns it
	 *
	 * @return string HTML to output
	 */
	protected function writeDebugOutput() {
		$output = '';
			// Output only if activated and if a BE user is logged in
		if ($this->debugToOutput && isset($GLOBALS['BE_USER'])) {
				/** @var $debugger tx_displaycontroller_debugger */
			$debugger = NULL;
				// If a custom debugging class is declared, get an instance of it
			if (!empty($this->extensionConfiguration['debugger'])) {
				try {
					$debugger = t3lib_div::makeInstance(
						$this->extensionConfiguration['debugger'],
						$GLOBALS['TSFE']->getPageRenderer()
					);
				}
				catch (Exception $e) {
					$this->addMessage(
						$this->extKey,
						$this->pi_getLL('error.no_custom_debugger_info'),
						$this->pi_getLL('error.no_custom_debugger'),
						t3lib_FlashMessage::WARNING
					);
				}
			}
				// If no custom debugger class is defined or if it was not of the right type,
				// instantiate the default class
			if ($debugger === NULL || !($debugger instanceof tx_displaycontroller_debugger)) {
				$debugger = t3lib_div::makeInstance(
					'tx_displaycontroller_debugger',
					$GLOBALS['TSFE']->getPageRenderer()
				);
			}
			$output = $debugger->render($this->messageQueue);
		}
		return $output;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php']);
}

?>