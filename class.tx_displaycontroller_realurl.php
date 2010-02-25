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
 * @author	Francois Suter <typo3@cobweb.ch>
 * @author	Fabien Udriot <typo3@cobweb.ch>
 *
 * $Id: class.tx_displaycontroller_realurl.php 213 2009-08-19 07:38:30Z fsuter $
 * $Rev: 13309 $
 */
class tx_displaycontroller_realurl {

	private $postVarSets = 'item';
	private $defaultValueEmpty = 'unknown';
	static private $languageConfiguration;

	/**
	 * Returns an URL segment
	 *
	 * @param	array	$parameters: 'pObj' => 'tx_realurl', 'value' -> '1' (pid) , 'decodeAlias' => OR 'encodeAlias' =>
	 * @param	object	$ref
	 * @return	string
	 */
	public function main($parameters, $ref) {
		if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_displaycontroller.']['detailView.']['postVarSets'])) {
			$this->postVarSets = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_displaycontroller.']['detailView.']['postVarSets'];
		}
		
		#if (!is_int($parameters['tx_displaycontroller[showUid]'])) {echo $parameters['tx_displaycontroller[showUid]']; return;};
		if (!empty($parameters['value'])) {
			if ($parameters['decodeAlias']) {
				return $this->decodeAlias($parameters, $ref);
			}
			else {
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
	private function decodeAlias($parameters, &$ref) {

		// In addition of the parameters received, we need the before last URL segment
		// which contains the table alias
		$segments = $ref->dirParts;
		array_pop($segments);
		$speakingTable = array_pop($segments);

		// select the configuration array
		// Defines the $field_id. The value is going to be used in a SQL statement WHERE $field_id = showUid
		if ($ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$speakingTable]) {
			$table = $ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$speakingTable];
		}
		else {
			$table = $speakingTable;
		}

		// Query the unique alias table to find the primary key
		$where = "tablename = '$table' AND value_alias = '".$parameters['value']."' AND (expire > " . time() . ' OR expire = 0)';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('value_id', 'tx_realurl_uniqalias', $where);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			return false;
		}
		else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row['value_id'];
		}
	}

	/**
	 * This method takes the 2 tx_displaycontroller variables (table and showUid) and create a speaking URL out of them
	 * by querying the links and information database
	 *
	 * @param	array	$parameters: query variables passed by the tx_realurl object
	 * @param	object	$ref: reference to the realurl object
	 *
	 * @return	string	speaking URL
	 */
	private function encodeAlias($parameters, &$ref) {

		// Error handling
		if (empty($parameters['tx_displaycontroller[table]'])) {
			$this->throwException('Error: tx_displaycontroller[table] is empty.');
		}

		if (empty($parameters['tx_displaycontroller[showUid]'])) {
			$this->throwException('Error: tx_displaycontroller[showUid] is empty.');
		}

		// Translates speaking URL table name to database table name
		$table = $parameters['tx_displaycontroller[table]'];
		
		// Check if the table needs to be translated
		if (isset($ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$table])) {
			$table = $ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][0]['valueMap'][$table];
		}

		// Default value;
		$configurations = array();

		// select the configuration array
		// Defines the $field_id. The value is going to be used in a SQL statement WHERE $field_id = showUid
		if ($ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][1]['userFunc.']) {
			$configurations = $ref->extConf['postVarSets']['_DEFAULT'][$this->postVarSets][1]['userFunc.'];
		}

		// Finds out the right configuration array containing table, alias_field, alias_id (possibly)
		if (empty($configurations[$table])) {
			// If no configuration was foune throw an error
			$this->throwException('Error realurl configuration: no configuration found for table ' . $table);
		}

		$configuration = $configurations[$table];
		$configuration += array('id_field' => 'uid');

		if (!isset($configuration['alias_field'])) {
			$this->throwException('Error realurl configuration: unknown alias_field for table ' . $table);
		}

			// Make sure the language variable is set
		if (isset($ref->extConf['pagePath']['languageGetVar']) && isset($parameters[$ref->extConf['pagePath']['languageGetVar']])) {
			$lang = $parameters[$ref->extConf['pagePath']['languageGetVar']];
		}
		else {
			$lang = 0;
		}

			// Get the id parameter
		$id = intval($parameters['tx_displaycontroller[showUid]']);

			// Get the name of the field to fetch the alias from
			// Check if field alias contains a curly brace, if yes, call the basecontroller parser
		if (strpos($configuration['alias_field'], '{') !== false) {
			$field_alias = tx_expressions_parser::evaluateString($configuration['alias_field']);
		}
		else {
			$field_alias = $configuration['alias_field'];
		}
			// Now check if the field alias contains a ###LANG### marker
			// If yes, substitute it with language code taken from RealURL config
		if (strpos($field_alias, '###LANG###') !== false) {
				// Check if predictable language setup can be found
			if (!isset(self::$languageConfiguration)) {
				self::$languageConfiguration = $this->getLanguageConfiguration($ref->extConf);
			}
			$flippedLanguageArray = array_flip(self::$languageConfiguration);
			$languageCode = (isset($flippedLanguageArray[$lang])) ? $flippedLanguageArray[$lang] : '';
			$field_alias = str_replace('###LANG###', $languageCode, $field_alias);
		}

			// Get the name of the field that contains the id's
		$field_id = $configuration['id_field'];

			// Check if an alias already exists for that item
		$where = "tablename = '$table' AND value_id = '$id'";
			// Add the language as a filter, if not default
		if (!empty($lang)) {
			$where .= " AND lang = '".$lang."'";
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, expire, value_alias', 'tx_realurl_uniqalias', $where);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) { // As alias exists
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			// Check if the existing alias has expired
			if ($row['expire'] < time() && $row['expire'] > 0) {

				// It has expired, updates the record
				$cleanAlias = $this->getItemAlias($table, $field_alias, $field_id, $id, $ref);
				$expireDate = strtotime('+'.($ref->extConf['pagePath']['expireDays']).' days'); // Set new expiry date
				$fields = array('tstamp' => time(), 'value_alias' => $cleanAlias, 'expire' => $expireDate);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_realurl_uniqalias', "uid = '".$row['uid']."'", $fields);
			}
			else { // It has not expired, return the alias
				$cleanAlias = $row['value_alias'];
			}
		}
		else {
			// No alias exists, create one and store it
			$cleanAlias = $this->getItemAlias($table, $field_alias, $field_id, $id, $ref);

			// Stores alias into realURL's unique alias table
			$expireDate = strtotime('+'.($ref->extConf['pagePath']['expireDays']).' days');
			$fields	 = array('tstamp' => time(), 'tablename' => $table, 'field_alias' => $field_alias, 'field_id' => $field_id, 'value_alias' => $cleanAlias, 'value_id' => $id, 'lang' => $lang, 'expire' => $expireDate);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_realurl_uniqalias', $fields);
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
	private function getItemAlias($table, $field_alias, $field_id, $id, $ref) {

		// Which field to query depends on the table
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_alias, $table, $field_id . '=' . $id);

		// Makes sure records has a default value
		$records += array(array($field_alias => ''));
		$name = $records[0][$field_alias];

		// Transform fields into clean alias, using realURL functions
		$name = str_replace("'", '', stripslashes($name)); // First remove single quotes
		$config = array('useUniqueCache_conf' => array('strtolower' => 1, 'spaceCharacter' => '-'));
		$alias = $ref->lookUp_cleanAlias($config, $name);

		if ($alias == '') {
			$alias = $this->defaultValueEmpty;
		}

		// Makes sure the alias is unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('value_alias', 'tx_realurl_uniqalias', 'value_alias = "' . $alias . '"');
		$loop = 1;

		while($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0 && $loop < 5000) {
			$_alias = $alias . $config['useUniqueCache_conf']['spaceCharacter'] . $loop;
			$loop ++;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('value_alias', 'tx_realurl_uniqalias', 'value_alias = "' . $_alias . '"');
		}

		if (isset($_alias)) {
			$alias = $_alias;
		}
		return $alias;
	}

	/**
	 * This method tries to find a language configuration inside the RealURL configuration
	 * 
	 * @param	array	$conf: RealURL configuration
	 * @return	array	Mapping of language codes to language id's
	 */
	private function getLanguageConfiguration($conf) {
		$languageConfig = array();
			// First check if configuration is in a standard place
		if (isset($conf['preVars']['lang']['valueMap'])) {
			$languageConfig = $conf['preVars']['lang']['valueMap'];
		}
			// If not search for it inside the whole configuration
			// The search looks in preVars,...
			// and tries to find a value map for a variable whose name matches
			// the declared language variable's name in the pagePath setup
		else {
			$langVarName = $conf['pagePath']['languageGetVar'];
				// First search among the preVars
			if (isset($conf['preVars'])) {
				foreach ($conf['preVars'] as $configuration) {
					if (isset($configuration['GETvar']) && $configuration['GETvar'] == $langVarName) {
						if (isset($configuration['valueMap'])) {
							$languageConfig = $configuration['valueMap'];
							break;
						}
					}
				}
			}
			// If not found in the preVars, search in the fixedPostVars and postVarSets
			// NOTE: does that make sense?
			// TODO: implement if it becomes necessary
		}
		return $languageConfig;
	}

	/*
	 * Throws an error message catched by the controller (e.g. displaycontroller)
	 *
	 * @param	string	$message: the message outputed
	 */
	private function throwException($message) {
		throw new Exception('<div style="color:red">' . $message . '</div>');
	}
}
?>