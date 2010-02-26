<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Class for updating the display controller
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class ext_update {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string	HTML to display
	 */
	function main() {
		$content = '<h2>Updating old components relationships</h2>';
			// List of old MM tables and their values in the new single MM-table
		$tablesToCheck = array(
							'tx_displaycontroller_consumers_mm' => array(
																		'type' => 'consumer',
																		'rank' => 1
																	),
							'tx_displaycontroller_filters_mm' => array(
																		'type' => 'filter',
																		'rank' => 1
																	),
							'tx_displaycontroller_providers_mm' => array(
																		'type' => 'provider',
																		'rank' => 1
																	),
							'tx_displaycontroller_filters2_mm' => array(
																		'type' => 'filter',
																		'rank' => 2
																	),
							'tx_displaycontroller_providers2_mm' => array(
																		'type' => 'provider',
																		'rank' => 2
																	),
						);
			// Check if update button was clicked
		$updateMM = t3lib_div::_GP('updateMM');
		if ($updateMM) {
			foreach ($tablesToCheck as $table => $tableInfo) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '');
				if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
					$numUpdates = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
					while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
						$fields = $row;
							// Add the additional information for the new components table
						$fields['component'] = $tableInfo['type'];
						$fields['rank'] = $tableInfo['rank'];
						$fields['local_table'] = 'tt_content';
						$fields['local_field'] = 'CType';
							// Insert the information in the new relations table
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_displaycontroller_components_mm', $fields);
					}
					$content .= '<p>' . $numUpdates . ' were performed for ' . $tableInfo['type'] . '-' . $tableInfo['rank'] . ' relationships.</p>';
				}
			}
		} else {
				// Check every old MM table and show number of records in each
			$updates = 0;
			foreach ($tablesToCheck as $table => $tableInfo) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '');
				if ($res) {
					$numUpdates = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
					$updates += $numUpdates;
					$content .= '<p>There are ' . $numUpdates . ' ' . $tableInfo['type'] . '-' . $tableInfo['rank'] . ' relationships to update.</p>';
				}
			}
				// If some updates must be done, display button
			if ($updates > 0) {
					// Check if some entries already exist in the new MM table
					// This should be happening only if relations were already migrated
					// so the update should not be performed twice => issue warning
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_components_mm', '');
				$numNewRelations = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
				if ($numNewRelations > 0) {
					$content .= '<p><strong>WARNING: There are already entries in the new relations table. This means the update might already have been performed. Be careful not to perform it several times, as it will mess up your relations.</strong></p>';
				}
				$content .= '<form name="updateForm" action="" method ="post">';
				$content .= '<p><input type="submit" name="updateMM" value ="Update"></p>';
				$content .= '</form>';
			} else {
				$content .= '<p><strong>All relationships are up to date.</strong></p>';
			}
		}
		$content .= '<h2>Updating secondary providers relationships</h2>';
			// Get a list of all secondary providers (i.e. providers with a sorting of 2)
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_providers_mm', "sorting = '2'");
		if ($res) {
				// No secondary provider exists
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
				$content .= '<p>There are no relationships to update.</p>';
			}
				// There are secondary providers
			else {
				$update = t3lib_div::_GP('submitButton');
					// The update button was not clicked, display information message
				if (empty($update)) {
					$content .= '<p>There are '.$GLOBALS['TYPO3_DB']->sql_num_rows($res).' relationships to update. Click on the update button below to start the process.</p>';
					$content .= '<form name="updateForm" action="" method ="post">';
					$content .= '<p><input type="submit" name="submitButton" value ="Update"></p>';
					$content .= '</form>';
				}
					// The update button was clicked, perform the update
				else {
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$fields = $row;
						$fields['sorting'] = 1;
							// Create new relationship
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_displaycontroller_providers2_mm', $fields);
							// Set relationship counter in tt_content
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', "uid = '".$row['uid_local']."'", array('tx_displaycontroller_provider2' => 1));
						$content .= '<p>New relationship created:</p>';
						$content .= t3lib_div::view_array($fields);
					}
						// Delete old relationships
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_displaycontroller_providers_mm', "sorting = '2'");
					$content .= '<p>Old relationships deleted<br /></p>';
					$content .= '<p><strong>Update complete!</strong></p>';
				}
			}
		}
		else {
			$content .= '<p><strong>ERROR: Could not get a list of existing secondary providers. Check if anything is wrong with your database and try again.</strong></p>';
		}
		$content .= '<h2>Checking old hook names</h2>';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['displaycontroller']['setExtraDataForFilter']) && count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['displaycontroller']['setExtraDataForFilter']) > 0) {
			$content .= '<p>There are some hooks that you need to change <em>(&quot;setExtraDataForFilter&quot; =&gt; &quot;setExtraDataForParser&quot;)</em></p>';
			$content .= '<ul>';
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['displaycontroller']['setExtraDataForFilter'] as $hook) {
				$content .= '<li>'.$hook.'</li>';
			}
			$content .= '</ul>';
		}
		else {
			$content .= '<p>There are no old hooks to change.</p>';
		}
		return $content;
	}

	/**
	 * This method checks whether it is necessary to display the UPDATE option at all
	 *
	 * @param	string	$what: What should be updated
	 */
	function access($what = 'all') {
		return true;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.ext_update.php']);
}
?>
