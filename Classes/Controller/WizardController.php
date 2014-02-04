<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Erik Frister <typo3@marketing-factory.de>
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

class Tx_Commerce_Controller_WizardController {
	/**
	 * @var array
	 */
	public $pageinfo = array();

	/**
	 * @var array
	 */
	public $pidInfo = array();

	/**
	 * @var integer
	 */
	public $newContentInto;

	/**
	 * @var array
	 */
	public $web_list_modTSconfig = array();

	/**
	 * @var array
	 */
	public $web_list_modTSconfig_pid = array();

	/**
	 * @var array
	 */
	public $allowedNewTables = array();

	/**
	 * @var array
	 */
	public $allowedNewTables_pid = array();

	/**
	 * @var string
	 */
	public $code = '';

	/**
	 * @var string
	 */
	public $R_URI;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * Return url.
	 *
	 * @var string
	 */
	protected $returnUrl = '';

	/**
	 * pagesOnly flag.
	 *
	 * @var boolean
	 */
	protected $pagesOnly;

	/**
	 * @var string
	 */
	protected $perms_clause;

	/**
	 * @var mediumDoc
	 */
	protected $doc;

	/**
	 * Accumulated HTML output
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * @var string
	 */
	protected $head = '';

	/**
	 * @var array
	 */
	protected $param;

	/**
	 * default values to be used
	 *
	 * @var array
	 */
	protected $defVals;

	/**
	 * Constructor function for the class
	 *
	 * @return	void
	 */
	public function init() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// page-selection permission clause (reading)
		$this->perms_clause = $backendUser->getPagePermsClause(1);

			// Setting GPvars:
			// The page id to operate from
		$this->id = t3lib_div::_GP('id') ? (int) t3lib_div::_GP('id') : Tx_Commerce_Utility_BackendUtility::getProductFolderUid();
		$this->returnUrl = t3lib_div::_GP('returnUrl');

			// this to be accomplished from the caller: &edit['.$table.'][-'.$uid.']=new&
		$this->param = t3lib_div::_GP('edit');
		$this->defVals = t3lib_div::_GP('defVals');

			// Create instance of template class for output
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->JScode = '';

		$this->head = $language->getLL('newRecordGeneral', 1);

			// Creating content
		$this->content = '';
		$this->content .= $this->doc->startPage($this->head);
		$this->content .= $this->doc->header($this->head);

			// Id a positive id is supplied, ask for the page record with permission information contained:
		if ($this->id > 0) {
			$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		}

			// If a page-record was returned, the user had read-access to the page.
		if ($this->pageinfo['uid']) {
				// Get record of parent page
			$this->pidInfo = t3lib_BEfunc::getRecord('pages', $this->pageinfo['pid']);
				// Checking the permissions for the user with regard to the parent page: Can he create new pages, new content record, new page after?
			if ($backendUser->doesUserHaveAccess($this->pageinfo, 16)) {
				$this->newContentInto = 1;
			}
		} elseif ($backendUser->isAdmin()) {
				// Admins can do it all
			$this->newContentInto = 1;
		} else {
				// People with no permission can do nothing
			$this->newContentInto = 0;
		}
	}

	/**
	 * Main processing, creating the list of new record tables to select from
	 *
	 * @return void
	 */
	public function main() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// If there was a page - or if the user is admin (admins has access to the root) we proceed:
		if ($this->pageinfo['uid'] || $backendUser->isAdmin()) {
				// Acquiring TSconfig for this module/current page:
			$this->web_list_modTSconfig = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
			$this->allowedNewTables = t3lib_div::trimExplode(',', $this->web_list_modTSconfig['properties']['allowedNewTables'], 1);

				// Acquiring TSconfig for this module/parent page:
			$this->web_list_modTSconfig_pid = t3lib_BEfunc::getModTSconfig($this->pageinfo['pid'], 'mod.web_list');
			$this->allowedNewTables_pid = t3lib_div::trimExplode(',', $this->web_list_modTSconfig_pid['properties']['allowedNewTables'], 1);

				// Set header-HTML and return_url
			$this->code = $this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']) . '<br />
			';
			$this->R_URI = $this->returnUrl;

			$this->regularNew();

				// Create go-back link.
			if ($this->R_URI) {
				$this->code .= '<br />
					<a href="' . htmlspecialchars($this->R_URI) . '" class="typo3-goBack">' .
						t3lib_iconWorks::getSpriteIcon('actions-view-go-back', array('title' => $language->getLL('goBack', 1))) .
					'</a>';
			}
				// Add all the content to an output section
			$this->content .= $this->doc->section('', $this->code);
		}
		$this->content .= $this->doc->endPage();
	}

	/**
	 * Ending page output and echo'ing content to browser.
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create a regular new element (pages and records)
	 *
	 * @return void
	 */
	protected function regularNew() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Slight spacer from header:
		$this->code .= '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/ol/halfline.gif', 'width="18" height="8"') . ' alt="" /><br />';

			// New tables INSIDE this category
		foreach ($this->param as $table => $param) {
			if (
				$this->showNewRecLink($table)
				&& $this->isTableAllowedForThisPage($this->pageinfo, $table)
				&& $backendUser->check('tables_modify', $table)
				&& ($param['ctrl']['rootLevel'] xor $this->id || $param['ctrl']['rootLevel'] == -1)
			) {
				$val = key($param);
				$cmd = ($param[$val]);
				switch ($cmd) {
					case 'new':
							// Create new link for record:
						$rowContent = '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/ol/join.gif', 'width="18" height="16"') . ' alt="" />' .
							$this->linkWrap(
								t3lib_iconWorks::getSpriteIconForRecord($table, array()) .
									$language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], 1),
								$table,
								$this->id
							);

							// Compile table row:
						$tRows[] = '
				<tr>
					<td nowrap="nowrap">' . $rowContent . '</td>
					<td>' . t3lib_BEfunc::cshItem($table, '', $GLOBALS['BACK_PATH'], '') . '</td>
				</tr>
				';
					break;

					default:
					break;
				}
			}
		}

			// Compile table row:
		$tRows[] = '
			<tr>
				<td><img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/ol/stopper.gif', 'width="18" height="16"') . ' alt="" /></td>
				<td></td>
			</tr>
		';

			// Make table:
		$this->code .= '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-newRecord">
			' . implode('', $tRows) . '
			</table>
		';

			// Add CSH:
		$this->code .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_regular', $GLOBALS['BACK_PATH'], '<br/>');
	}

	/**
	 * Links the string $code to a create-new form for a record in $table created on page $pid
	 *
	 * @param string $code Link string
	 * @param string $table Table name (in which to create new record)
	 * @param integer $pid PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
	 * @return string The link.
	 */
	protected function linkWrap($code, $table, $pid) {
		$params = '&edit[' . $table . '][' . $pid . ']=new' . $this->compileDefVals($table);
		$onClick = t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'], $this->returnUrl);
		return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $code . '</a>';
	}

	/**
	 * @param string $table
	 * @return string
	 */
	protected function compileDefVals($table) {
		$data = t3lib_div::_GP('defVals');
		if (is_array($data[$table])) {
			$result = '';
			foreach ($data[$table] as $key => $value) {
				$result .= '&defVals[' . $table . '][' . $key . ']=' . urlencode($value);
			}
		} else {
			$result = '';
		}
		return $result;
	}

	/**
	 * Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 *
	 * @param array $pid_row Record for parent page.
	 * @param string $checkTable Table name to check
	 * @return boolean Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 */
	protected function isTableAllowedForThisPage($pid_row, $checkTable) {
		$result = FALSE;

		if (!is_array($pid_row)) {
			if ($GLOBALS['BE_USER']->user['admin']) {
				$result = TRUE;
			} else {
				$result = FALSE;
			}
		} else {
				// be_users and be_groups may not be created anywhere but in the root.
			if ($checkTable == 'be_users' || $checkTable == 'be_groups') {
				$result = FALSE;
			} else {
					// Checking doktype:
				$doktype = (int) $pid_row['doktype'];
				if (!$allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables']) {
					$allowedTableList = $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
				}

					// If all tables or the table is listed as a allowed type, return true
				if (strstr($allowedTableList, '*') || t3lib_div::inList($allowedTableList, $checkTable)) {
					$result = TRUE;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns true if the $table tablename is found in $allowedNewTables (or if $allowedNewTables is empty)
	 *
	 * @param string $table Table name to test if in allowedTables
	 * @param array $allowedNewTables Array of new tables that are allowed.
	 * @return boolean Returns true if the $table tablename is found in $allowedNewTables (or if $allowedNewTables is empty)
	 */
	protected function showNewRecLink($table, $allowedNewTables = array()) {
		$allowedNewTables = is_array($allowedNewTables) ? $allowedNewTables : $this->allowedNewTables;
		return !count($allowedNewTables) || in_array($table, $allowedNewTables);
	}
}

class_alias('Tx_Commerce_Controller_WizardController', 'tx_commerce_cmd_wizard');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/WizardController.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/WizardController.php']);
}

?>