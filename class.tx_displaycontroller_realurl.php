<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter <support@cobweb.ch>
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
 * RealURL translator for the 'displaycontroller' extension.
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @author		Fabien Udriot <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller_realurl {

	protected $extKey = 'displaycontroller';
	protected $postVarSets = 'item';
	protected $defaultValueEmpty = 'unknown';
	/**
	 * Extension configuration
	 * 
	 * @var	array
	 */
	protected $configuration = array();
	static protected $languageConfiguration;
	static protected $defaultLanguageCode;

	public function __construct() {
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * This method performs either encoding or decoding to/from a speaking URL segment
	 * and returns the relevant information
	 *
	 * @param	array	$parameters: 'pObj' => 'tx_realurl', 'value' -> '1' (pid) , 'decodeAlias' => OR 'encodeAlias' =>
	 * @param	object	$ref
	 * @return	string
	 */
	public function main($parameters, $ref) {
		if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_displaycontroller.']['detailView.']['postVarSets'])) {
			$this->postVarSets = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_displaycontroller.']['detailView.']['postVarSets'];
		}
		
		if (!empty($parameters['value'])) {
			if ($parameters['decodeAlias']) {
				return $this->decodeAlias($parameters, $ref);
			} else {
				return $this->encodeAlias($ref->orig_paramKeyValues, $ref);
			}
		}
	}

	/**
	 * This method takes a speaking url alias and returns a primarey key for the right table
	 *
	 * @param	array		$parameters: parameters passed by RealURL
	 * @param	object		$ref: reference to the RealURL object
	 *
	 * @return	integer		primary key
	 */
	protected function decodeAlias($parameters, &$ref) {

			// In addition of the parameters received, we need the before last URL segment
			// which contains the table alias
		$segments = $ref->dirParts;
		array_pop($segments);
		$speakingTable = array_pop($segments);
		$table = $speakingTable;

			// Check if the table is a "speaking" name mapped to a real table name
		if ($ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$speakingTable]) {
			$table = $ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$speakingTable];
		}

			// Query the unique alias table to find the primary key
		$where = 'tablename = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'tx_realurl_uniqalias');
		$where .= ' AND value_alias = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parameters['value'], 'tx_realurl_uniqalias');
		$where .= ' AND (expire > ' . time() . ' OR expire = 0)';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('value_id', 'tx_realurl_uniqalias', $where);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			return FALSE;
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row['value_id'];
		}
	}

	/**
	 * This method takes the 2 tx_displaycontroller variables (table and showUid) and creates a speaking URL out of them
	 * by querying the links and information database
	 *
	 * @param	array	$parameters: query variables passed by the tx_realurl object
	 * @param	object	$ref: reference to the realurl object
	 *
	 * @return	string	speaking URL
	 */
	protected function encodeAlias($parameters, &$ref) {
		$cleanAlias = '';
		$table = '';
		$id = 0;

			// Error handling
			// Get the table name from the GET/POST parameters
		if (empty($parameters['tx_displaycontroller[table]'])) {
			if ($this->configuration['debug'] || TYPO3_DLOG) {
				t3lib_div::devLog('tx_displaycontroller[table] is empty', $this->extKey, 3, $parameters);
			}
		} else {
			$table = $parameters['tx_displaycontroller[table]'];
		}
			// Get the record's id from the GET/POST parameters
		if (empty($parameters['tx_displaycontroller[showUid]'])) {
			if ($this->configuration['debug'] || TYPO3_DLOG) {
				t3lib_div::devLog('tx_displaycontroller[showUid] is empty', $this->extKey, 3, $parameters);
			}
		} else {
			$id = intval($parameters['tx_displaycontroller[showUid]']);
		}

			// If the table parameter is not empty, try to get its configuration
		$configuration = array();
		if (!empty($table)) {
				// Check if the table needs to be translated
				// If not, it is used as is
			if (isset($ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$table])) {
				$table = $ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$table];
			}

				// Get the configurations for all tables
				// The configurations contain which DB field to get the alias from
			$configurations = array();
			if ($ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][1]['userFunc.']) {
				$configurations = $ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][1]['userFunc.'];
			}

				// Find out the right configuration for the table
			if (empty($configurations[$table])) {
					// If no configuration, log the error
				if ($this->configuration['debug'] || TYPO3_DLOG) {
					t3lib_div::devLog(sprintf('No alias configuration found for table %s, falling back on default (uid)', $table), $this->extKey, 2, $configurations);
				}
					// Fall back on default configuration
					// NOTE: uid hard-coded, it could be made configurable if need arises
				$configuration = array('id_field' => 'uid', 'alias_field' => 'uid');
			} else {
				$configuration = $configurations[$table];
					// NOTE: uid hard-coded, it could be made configurable if need arises
				$configuration['id_field'] = 'uid';

					// Issue an error if no alias field was found
				if (empty($configuration['alias_field'])) {
					if ($this->configuration['debug'] || TYPO3_DLOG) {
						t3lib_div::devLog(sprintf('Undefined alias field for table %1$s, using %2$s instead', $table, $configuration['id_field']), $this->extKey, 2, $configuration);
					}
					$configuration['alias_field'] = $configuration['id_field'];
				}
			}
		}

			// If both table and id are defined, continue with assembling the alias based on the found configuration
		if (!empty($id) && !empty($table)) {

				// Make sure the language variable is set
			$lang = 0;
			if (isset($ref->extConf['pagePath']['languageGetVar']) && isset($parameters[$ref->extConf['pagePath']['languageGetVar']])) {
				$lang = intval($parameters[$ref->extConf['pagePath']['languageGetVar']]);
			}

				// Get the name of the field to fetch the alias from
				// Check if field alias contains a curly brace, if yes, call the expressions parser
			$field_alias = $configuration['alias_field'];
			if (strpos($configuration['alias_field'], '{') !== FALSE) {
				$field_alias = tx_expressions_parser::evaluateString($configuration['alias_field']);
			}
				// Now check if the field alias contains a ###LANG### marker
				// If yes, substitute it with language code taken from RealURL config
			if (strpos($field_alias, '###LANG###') !== FALSE) {
					// Check if predictable language setup can be found
				if (!isset(self::$languageConfiguration)) {
					$this->getLanguageConfiguration($ref->extConf);
				}
				$languageCode = (isset(self::$languageConfiguration[$lang])) ? self::$languageConfiguration[$lang] : self::$defaultLanguageCode;
				$field_alias = str_replace('###LANG###', $languageCode, $field_alias);
			}

				// Get the name of the field that contains the id's
			$field_id = $configuration['id_field'];

				// Check if an alias already exists for that item
			$where = "tablename = " . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'tx_realurl_uniqalias') . " AND value_id = '" . $id . "'";
				// Add the language as a filter
			$where .= " AND lang = '" . $lang . "'";
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, expire, value_alias', 'tx_realurl_uniqalias', $where);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) { // As alias exists
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

					// Check if the existing alias has expired
				if ($row['expire'] < time() && $row['expire'] > 0) {

						// It has expired, updates the record
					$cleanAlias = $this->getItemAlias($table, $field_alias, $field_id, $id, $ref);
					$fields = array('tstamp' => time(), 'value_alias' => $cleanAlias);
						// Set new expiry date, if defined
					if (!empty($ref->extConf['pagePath']['expireDays'])) {
						$expireDate = strtotime('+' . ($ref->extConf['pagePath']['expireDays']) . ' days');
						$fields['expire'] = $expireDate;
					}
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_realurl_uniqalias', "uid = '" . $row['uid'] . "'", $fields);

					// It has not expired, return the alias
				} else {
					$cleanAlias = $row['value_alias'];
				}
			} else {
					// No alias exists, create one and store it
				$cleanAlias = $this->getItemAlias($table, $field_alias, $field_id, $id, $ref);

					// Stores alias into realURL's unique alias table
				$expireDate = strtotime('+' . ($ref->extConf['pagePath']['expireDays']) . ' days');
				$fields	 = array('tstamp' => time(), 'tablename' => $table, 'field_alias' => $field_alias, 'field_id' => $field_id, 'value_alias' => $cleanAlias, 'value_id' => $id, 'lang' => $lang, 'expire' => $expireDate);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_realurl_uniqalias', $fields);
			}

			// Some information was missing for creating a proper alias
			// (table name, id, configuration), return an empty alias
		} else {
			$cleanAlias = '';
		}
		return $cleanAlias;
	}

	/**
	 * This method gets the relevant name field from a given table and returns a cleaned up (RealURL-wise) alias
	 *
	 * @param	string		$table: name of the table to query
	 * @param	string		$field_id: name of the primary key
	 * @param	integer		$id: value of the primary key
	 * @param	object		$ref: reference to the RealURL object
	 *
	 * @return	string		cleaned up alias for the item
	 */
	protected function getItemAlias($table, $field_alias, $field_id, $id, $ref) {

			// Which field to query depends on the table
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_alias, $table, $field_id . '=' . $id);

			// Makes sure records has a default value
		if (count($records) == 0) {
			$records = array(array($field_alias => ''));
		}
		$name = $records[0][$field_alias];

			// Transform fields into clean alias, using realURL functions
		$name = str_replace("'", '', stripslashes($name)); // First remove single quotes
		$config = array('useUniqueCache_conf' => array('strtolower' => 1, 'spaceCharacter' => '-'));
		$alias = $ref->lookUp_cleanAlias($config, $name);

		if ($alias == '') {
			$alias = $this->defaultValueEmpty;
		}

			// Check alias unicity
		$alias = $this->checkUniqueAlias($alias, $config['useUniqueCache_conf']['spaceCharacter']);
		return $alias;
	}

	/**
	 * This method is used to check whether a given alias is unique or not
	 * If not it will append stuff to the alias to make it unique
	 *
	 * @param	string	$alias: alias to check
	 * @param	string	$separator: character used instead of white space inside speaking URLs
	 * @return	string	The unique alias
	 */
	protected function checkUniqueAlias($alias, $separator) {
		$uniqueAlias = $alias;
		$hasUniqueAlias = FALSE;
		$loop = 1;
			// If the alias is not unique, try making it unique by appending a number
			// Try a maximum of 100 times
		do {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('value_alias', 'tx_realurl_uniqalias', 'value_alias = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($uniqueAlias, 'tx_realurl_uniqalias'));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
				$hasUniqueAlias = TRUE;
				break;
			} else {
				$uniqueAlias = $alias . $separator . $loop;
				$loop++;
			}
		} while (!$hasUniqueAlias && $loop < 100);

			// If everything failed, append short hash based on microtime
			// (we don't do this before, because it's nicer if we can manage to append
			// only a few numbers)
		if (!$hasUniqueAlias) {
			$uniqueAlias = $alias . $separator . t3lib_div::shortMD5(microtime());
		}
		return $uniqueAlias;
	}

	/**
	 * This method tries to find a language configuration inside the RealURL configuration
	 * 
	 * @param	array	$conf: RealURL configuration
	 */
	protected function getLanguageConfiguration($conf) {
		$languageConfig = array();
			// First check if configuration is in a standard place
		if (isset($conf['preVars']['lang'])) {
			$languageConfig = $conf['preVars']['lang'];

			// If not search for it inside the whole configuration
			// The search looks in preVars,...
			// and tries to find a value map for a variable whose name matches
			// the declared language variable's name in the pagePath setup
		} else {
			$langVarName = $conf['pagePath']['languageGetVar'];
				// First search among the preVars
			if (isset($conf['preVars'])) {
				foreach ($conf['preVars'] as $configuration) {
					if (isset($configuration['GETvar']) && $configuration['GETvar'] == $langVarName) {
						$languageConfig = $configuration;
						break;
					}
				}
			}
			// If not found in the preVars, search in the fixedPostVars and postVarSets
			// NOTE: does that make sense?
			// TODO: implement if it becomes necessary
		}
		if (isset($languageConfig['valueMap'])) {
			self::$languageConfiguration = array_flip($languageConfig['valueMap']);
		} else {
			self::$languageConfiguration = array();
		}
		if (isset($languageConfig['valueDefault'])) {
			self::$defaultLanguageCode = $languageConfig['valueDefault'];
		} else {
			self::$defaultLanguageCode = '';
		}
	}
}
?>