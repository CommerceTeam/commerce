<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005 - 2012 Ingo Schmitt <is@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Module 'Systemdata' for the 'commerce' extension.
 */
class Tx_Commerce_Controller_SystemdataController extends t3lib_SCbase {
	/**
	 * @var t3lib_db
	 */
	protected $database;

	/**
	 * @var language
	 */
	protected $language;

	/**
	 * @var t3lib_beUserAuth
	 */
	protected $user;

	/**
	 * @var array
	 */
	public $pageRow;

	/**
	 * Containing the Root-Folder-Pid of Commerce
	 *
	 * @var integer
	 */
	public $modPid;

	/**
	 * @var integer
	 */
	public $attributePid;

	/**
	 * @var string
	 */
	protected $tableForNewLink;

	/**
	 * @var array
	 */
	public $markers = array();

	/**
	 * @var array
	 */
	protected $referenceCount = array();

	/**
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->database = & $GLOBALS['TYPO3_DB'];
		$this->language = & $GLOBALS['LANG'];
		$this->user = & $GLOBALS['BE_USER'];

		$this->id = $this->modPid = (int) reset(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Commerce', 'commerce'));
		$this->attributePid = (int) reset(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Attributes', 'commerce', $this->modPid));

		$this->perms_clause = $this->user->getPagePermsClause(1);
		$this->pageRow = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_systemdata.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set moduleTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_systemdata.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_systemdata.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_systemdata.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 */
	public function menuConfig() {
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => 'attributes',
				'2' => 'manufacturer',
				'3' => 'supplier',
			)
		);
		parent::menuConfig();
	}

	/**
	 * @return void
	 */
	public function main() {
		$listUrl = t3lib_div::getIndpEnv('REQUEST_URI');

			// Access check!
			// The page will show only if there is a valid page and if user may access it
		if ($this->id && (is_array($this->pageRow) ? 1 : 0)) {
				// JavaScript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL) {
					document.location = URL;
				}
				function deleteRecord(table,id,url,warning) {
					if (
						confirm(eval(warning))
					)	{
						window.location.href = "' . $this->doc->backPath .
							'tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url);
					}
					return false;
				}
				' . $this->doc->redirectUrls($listUrl) . '
			');

			$this->doc->postCode = $this->doc->wrapScriptTags('
				script_ended = 1;
				if (top.fsMod) {
					top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
				}
			');

			$this->doc->inDocStylesArray['mod_systemdata'] = '';

				// Render content:
			$this->moduleContent();
		} else {
			$this->content = 'Access denied or commerce pages not created yet!';
		}

		$docHeaderButtons = $this->getHeaderButtons();

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content
		);
		$markers['FUNC_MENU'] = $this->doc->funcMenu(
			'',
			t3lib_BEfunc::getFuncMenu(
				$this->id,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			)
		);

			// put it all together
		$this->content = $this->doc->startPage($this->language->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageRow, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Create the panel of buttons for submitting the form or other operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	public function getHeaderButtons() {
		$buttons = array(
			'csh' => '',
				// group left 1
			'level_up' => '',
			'back' => '',
				// group left 2
			'new_record' => '',
			'paste' => '',
				// group left 3
			'view' => '',
			'edit' => '',
			'move' => '',
			'hide_unhide' => '',
				// group left 4
			'csv' => '',
			'export' => '',
				// group right 1
			'cache' => '',
			'reload' => '',
			'shortcut' => '',
		);

			// CSH
		if (!strlen($this->id)) {
			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txcommerceM1', 'list_module_noId', $GLOBALS['BACK_PATH'], '', TRUE);
		} elseif (!$this->id) {
			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txcommerceM1', 'list_module_root', $GLOBALS['BACK_PATH'], '', TRUE);
		} else {
			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txcommerceM1', 'list_module', $GLOBALS['BACK_PATH'], '', TRUE);
		}

			// New
		$newParams = '&edit[tx_commerce_' . $this->tableForNewLink . '][' . (int) $this->modPid . ']=new';
		$buttons['new_record'] = '<a href="#" onclick="' .
			htmlspecialchars(t3lib_BEfunc::editOnClick($newParams, $GLOBALS['BACK_PATH'], -1)) .
			'" title="' . $this->language->getLL('create_' . $this->tableForNewLink) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-document-new') .
			'</a>';

			// Reload
		$buttons['reload'] = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript()) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-system-refresh') . '</a>';

			// Shortcut
		if ($this->user->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon(
				'id, showThumbs, pointer, table, search_field, searchLevels, showLimit, sortField, sortRev',
				implode(',', array_keys($this->MOD_MENU)), 'txcommerceM1_systemdata');
		}

		return $buttons;
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return void
	 */
	protected function moduleContent() {
		switch ((string) $this->MOD_SETTINGS['function']) {
			case '2':
				$content = $this->getManufacturerListing();
				$this->content .= $this->doc->section('', $content, 0, 1);
			break;

			case '3':
				$content = $this->getSupplierListing();
				$this->content .= $this->doc->section('', $content, 0, 1);
			break;

			case '1':
			default:
				$this->modPid = $this->attributePid;
				$content = $this->getAttributeListing();
				$this->content .= $this->doc->section('', $content, 0, 1);
			break;
		}
	}

	/**
	 * @return string
	 */
	protected function getAttributeListing() {
		$headerRow = '<tr><td class="bgColor6" colspan="3"><strong>' . $this->language->getLL('title_attributes') .
			'</strong></td><td class="bgColor6"><strong>' . $this->language->getLL('title_values') . '</strong></td></tr>';

		$result = $this->fetchAttributes();
		$attributeRows = $this->renderAttributeRows($result);

		$this->tableForNewLink = 'attributes';

		return '<table>' . $headerRow . $attributeRows . '</table>';
	}

	/**
	 * @return resource
	 */
	protected function fetchAttributes() {
		return $this->database->exec_SELECTquery(
			'*',
			'tx_commerce_attributes',
			'pid=' . (int) $this->attributePid . ' AND hidden=0 AND deleted=0 and (sys_language_uid = 0 OR sys_language_uid = -1)',
			'',
			'internal_title, title'
		);
	}

	/**
	 * @param integer $uid
	 * @return resource
	 */
	protected function fetchAttributeTranslation($uid) {
		return $this->database->exec_SELECTquery(
			'*',
			'tx_commerce_attributes',
			'pid=' . (int) $this->attributePid . ' AND hidden=0 AND deleted=0 and sys_language_uid <>0 and l18n_parent =' .
				(int) $uid,
			'',
			'sys_language_uid'
		);
	}

	/**
	 * @param resource $result
	 * @return string
	 */
	protected function renderAttributeRows($result) {
		$recordList = t3lib_div::makeInstance('t3lib_recordList');
		$recordList->backPath = $this->doc->backPath;
		$recordList->initializeLanguages();

		$output = '';

		$table = 'tx_commerce_attributes';
		while ($attribute = $this->database->sql_fetch_assoc($result)) {
			$refCountMsg = t3lib_BEfunc::referenceCount(
				$table,
				$attribute['uid'],
				' ' . $this->language->sL(
					'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
				),
				$this->getReferenceCount($table, $attribute['uid'])
			);
			$editParams = '&edit[' . $table . '][' . (int) $attribute['uid'] . ']=edit';
			$deleteParams = '&cmd[' . $table . '][' . (int) $attribute['uid'] . '][delete]=1';

			$output .= '<tr><td class="bgColor4" align="center" valign="top"> ' .
				t3lib_befunc::thumbCode($attribute, 'tx_commerce_attributes', 'icon', $this->doc->backPath) . '</td>';
			if ($attribute['internal_title']) {
				$output .= '<td valign="top" class="bgColor4"><strong>' . htmlspecialchars($attribute['internal_title']) . '</strong> (' .
					htmlspecialchars($attribute['title']) . ')';
			} else {
				$output .= '<td valign="top" class="bgColor4"><strong>' . htmlspecialchars($attribute['title']) . '</strong>';
			}

			$catCount = $this->fetchRelationCount('tx_commerce_categories_attributes_mm', $attribute['uid']);
			$proCount = $this->fetchRelationCount('tx_commerce_products_attributes_mm', $attribute['uid']);

				// Select language versions
			$resLocalVersion = $this->fetchAttributeTranslation($attribute['uid']);
			if ($this->database->sql_num_rows($resLocalVersion) > 0) {
				$output .= '<table >';
				while ($localAttributes = $this->database->sql_fetch_assoc($resLocalVersion)) {
					$output .= '<tr><td>&nbsp;';
					$output .= '</td><td>';
					if ($localAttributes['internal_title']) {
						$output .= htmlspecialchars($localAttributes['internal_title']) . ' (' . htmlspecialchars($localAttributes['title']) . ')';
					} else {
						$output .= htmlspecialchars($localAttributes['title']);
					}
					$output .= '</td><td>';
					$output .= $recordList->languageFlag($localAttributes['sys_language_uid']);
					$output .= '</td></tr>';

				}
				$output .= '</table>';
			}

			$output .= '<br />' . $this->language->getLL('usage');
			$output .= ' <strong>' . $this->language->getLL('categories') . '</strong>: ' . $catCount;
			$output .= ' <strong>' . $this->language->getLL('products') . '</strong>: ' . $proCount;
			$output .= '</td>';

			$output .= '<td><a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($editParams, $this->doc->backPath, -1)) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $this->language->getLL('edit', TRUE))) . '</a>';
			$output .= '<a href="#" onclick="' . htmlspecialchars(
					'if (confirm(' . $this->language->JScharCode(
						$this->language->getLL('deleteWarningManufacturer') . ' "' . $attribute['title'] . '" ' . $refCountMsg
					) . ')) {jumpToUrl(\'' . $this->doc->issueCommand($deleteParams, -1) . '\');} return false;'
				) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $this->language->getLL('delete', TRUE))) . '</a>';

			$output .= '</td><td>';

			if ($attribute['has_valuelist'] == 1) {
				$valueRes = $this->database->exec_SELECTquery(
					'*',
					'tx_commerce_attribute_values',
					'attributes_uid=' . (int) $attribute['uid'] . ' AND hidden=0 AND deleted=0',
					'',
					'sorting'
				);
				if ($this->database->sql_num_rows($valueRes) > 0) {
					$output .= '<table border="0">';
					while ($value = $this->database->sql_fetch_assoc($valueRes)) {
						$output .= '<tr><td>' . htmlspecialchars($value['value']) . '</td></tr>';
					}
					$output .= '</table>';
				} else {
					$output .= $this->language->getLL('no_values');
				}
			} else {
				$output .= $this->language->getLL('no_valuelist');
			}

			$output .= '</td></tr>';
		}

		return $output;
	}

	/**
	 * generates a list of all saved Manufacturers
	 */
	protected function getManufacturerListing() {
		$fields = explode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['coManufacturers']);

		$headerRow = '<tr><td></td>';
		foreach ($fields as $field) {
			$headerRow .= '<td class="bgColor6"><strong>' .
				$this->language->sL(t3lib_BEfunc::getItemLabel('tx_commerce_manufacturer', htmlspecialchars($field))) .
				'</strong></td>';
		}
		$headerRow .= '</tr>';

		$result = $this->fetchDataByTable('tx_commerce_manufacturer');
		$manufacturerRows = $this->renderManufacturerRows($result, $fields);

		$this->tableForNewLink = 'manufacturer';

		return '<table>' . $headerRow . $manufacturerRows . '</table>';
	}

	/**
	 * @param resource $result
	 * @param array $fields
	 * @return string
	 */
	protected function renderManufacturerRows($result, $fields) {
		$output = '';

		$table = 'tx_commerce_manufacturer';
		while ($row = $this->database->sql_fetch_assoc($result)) {
			$refCountMsg = t3lib_BEfunc::referenceCount(
				$table,
				$row['uid'],
				' ' . $this->language->sL(
					'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
				),
				$this->getReferenceCount($table, $row['uid'])
			);
			$editParams = '&edit[' . $table . '][' . (int) $row['uid'] . ']=edit';
			$deleteParams = '&cmd[' . $table . '][' . (int) $row['uid'] . '][delete]=1';

			$output .= '<tr><td><a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($editParams, $this->doc->backPath, -1)) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $this->language->getLL('edit', TRUE))) . '</a>';
			$output .= '<a href="#" onclick="' . htmlspecialchars(
					'if (confirm(' . $this->language->JScharCode(
						$this->language->getLL('deleteWarningManufacturer') . ' "' . htmlspecialchars($row['title']) . '" ' . $refCountMsg
					) . ')) {jumpToUrl(\'' . $this->doc->issueCommand($deleteParams, -1) . '\');} return false;'
				) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $this->language->getLL('delete', TRUE))) . '</a>';
			$output .= '</td>';

			foreach ($fields as $field) {
				$output .= '<td valign="top" class="bgColor4"><strong>' . htmlspecialchars($row[$field]) . '</strong>';
			}

			$output .= '</td></tr>';
		}

		return $output;
	}

	/**
	 * generates a list of all saved Suppliers
	 *
	 * @return string
	 */
	protected function getSupplierListing() {
		$fields = explode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['coSuppliers']);

		$headerRow = '<tr><td></td>';
		foreach ($fields as $field) {
			$headerRow .= '<td class="bgColor6"><strong>' .
				$this->language->sL(t3lib_BEfunc::getItemLabel('tx_commerce_supplier', htmlspecialchars($field))) .
				'</strong></td>';
		}
		$headerRow .= '</tr>';

		$result = $this->fetchDataByTable('tx_commerce_supplier');
		$supplierRows = $this->renderSupplierRows($result, $fields);

		$this->tableForNewLink = 'supplier';

		return '<table>' . $headerRow . $supplierRows . '</table>';
	}

	/**
	 * @param resource $result
	 * @param array $fields
	 * @return string
	 */
	protected function renderSupplierRows($result, $fields) {
		$output = '';

		$table = 'tx_commerce_supplier';
		while ($row = $this->database->sql_fetch_assoc($result)) {
			$refCountMsg = t3lib_BEfunc::referenceCount(
				$table,
				$row['uid'],
				' ' . $this->language->sL(
					'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
				),
				$this->getReferenceCount($table, $row['uid'])
			);
			$editParams = '&edit[' . $table . '][' . (int) $row['uid'] . ']=edit';
			$deleteParams = '&cmd[' . $table . '][' . (int) $row['uid'] . '][delete]=1';

			$output .= '<tr><td><a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($editParams, $this->doc->backPath, -1)) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $this->language->getLL('edit', TRUE))) . '</a>';
			$output .= '<a href="#" onclick="' . htmlspecialchars(
					'if (confirm(' . $this->language->JScharCode(
						$this->language->getLL('deleteWarningSupplier') . ' "' . htmlspecialchars($row['title']) . '" ' . $refCountMsg
					) . ')) {jumpToUrl(\'' . $this->doc->issueCommand($deleteParams, -1) . '\');} return false;'
				) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $this->language->getLL('delete', TRUE))) . '</a>';
			$output .= '</td>';

			foreach ($fields as $field) {
				$output .= '<td valign="top" class="bgColor4"><strong>' . htmlspecialchars($row[$field]) . '</strong>';
			}

			$output .= '</td></tr>';
		}

		return $output;
	}

	/**
	 * @param string $table
	 * @return resource
	 */
	protected function fetchDataByTable($table) {
		return $this->database->exec_SELECTquery(
			'*',
			$table,
			'pid=' . (int) $this->modPid . ' AND hidden=0 AND deleted=0',
			'',
			'title'
		);
	}

	/**
	 * @param string $table
	 * @param integer $uidForeign
	 * @return integer
	 */
	protected function fetchRelationCount($table, $uidForeign) {
		$result = $this->database->exec_SELECTquery('COUNT(*) as count', $table, 'uid_foreign=' . (int) $uidForeign);
		$row = $this->database->sql_fetch_assoc($result);
		return $row['count'];
	}

	/**
	 * Gets the number of records referencing the record with the UID $uid in
	 * the table $tableName.
	 *
	 * @param string $tableName
	 *        table name of the referenced record, must not be empty
	 * @param integer $uid
	 *        UID of the referenced record, must be > 0
	 *
	 * @return integer the number of references to record $uid in table
	 *                 $tableName, will be >= 0
	 */
	protected function getReferenceCount($tableName, $uid) {
		if (!isset($this->referenceCount[$tableName][$uid])) {
			$numberOfReferences = $this->database->exec_SELECTcountRows(
				'*',
				'sys_refindex',
				'ref_table = ' . $this->database->fullQuoteStr(
					$tableName, 'sys_refindex'
				) .
					' AND ref_uid = ' . (int) $uid .
					' AND deleted = 0'
			);

			$this->referenceCount[$tableName][$uid] = $numberOfReferences;
		}

		return $this->referenceCount[$tableName][$uid];
	}
}

class_alias('Tx_Commerce_Controller_SystemdataController', 'Tx_Commerce_SystemData');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/SystemdataController.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/SystemdataController.php']);
}

?>