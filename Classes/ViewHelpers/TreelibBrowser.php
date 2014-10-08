<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2011 Rene Fritz <r.fritz@colorcube.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Base class for the (iframe) treeview in TCEforms elements
 *
 * Can display
 * - non-browseable trees (all expanded)
 * - and browsable trees that runs inside an iframe which is needed
 *   not to reload the whole page all the time
 *
 * If we want to display a browseable tree, we need to run the tree in
 * an iframe element. In consequence this means that the display of the
 * browseable tree needs to be generated from an extra script.
 * This is the base class for such a script.
 *
 * The class itself do not render the tree but call tceforms to render the field.
 * In beforehand the TCA config value of treeViewBrowseable will be set to
 * 'iframeContent' to force the right rendering.
 *
 * That means the script do not know anything about trees. It just set
 * parameters and render the field with TCEforms.
 *
 * Might be possible with AJAX ...
 */
class Tx_Commerce_ViewHelpers_TreelibBrowser extends t3lib_SCbase {
	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $field;

	/**
	 * @var integer
	 */
	protected $uid;

	/**
	 * @var string
	 */
	protected $itemFormElName;

	/**
	 * @var string
	 */
	protected $flexConfig;

	/**
	 * @var string
	 */
	protected $backPath;

	/**
	 * @var string
	 */
	protected $currentSubScript;

	/**
	 * Constructor function for script class.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		// Setting GPvars:
		$this->table = t3lib_div::_GP('table');
		$this->field = t3lib_div::_GP('field');
		$this->uid = t3lib_div::_GP('uid');
		$this->itemFormElName = t3lib_div::_GP('elname');
		$this->flexConfig = t3lib_div::_GP('config');
		$seckey = t3lib_div::_GP('seckey');
		$allowProducts = t3lib_div::_GP('allowProducts');

		if (!($seckey === t3lib_div::shortMD5($this->table . '|' . $this->field . '|' . $this->uid . '|' . $this->itemFormElName .
				'|' . $this->flexConfig . '|' . $allowProducts . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']))) {
			die('access denied');
		}

		if ($this->flexConfig) {
			$this->flexConfig = unserialize(base64_decode($this->flexConfig));
		}

		$this->backPath = $GLOBALS['BACK_PATH'];

		// Initialize template object
		/** @var template $doc */
		$doc = t3lib_div::makeInstance('template');
		$this->doc = $doc;
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $this->backPath;

		// from tx_dam_SCbase
		$buttonColor = '#e3dfdb';
		$buttonColorHover = t3lib_div::modifyHTMLcolor($buttonColor, -20, -20, -20);

		// in typo3/stylesheets.css css is defined with id instead of
		// a class: TABLE#typo3-tree that's why we need TABLE.typo3-browsetree
		$this->doc->inDocStylesArray['typo3-browsetree'] = '
			/* Trees */
			TABLE.typo3-browsetree A { text-decoration: none;  }
			TABLE.typo3-browsetree TR TD { white-space: nowrap; vertical-align: middle; }
			TABLE.typo3-browsetree TR TD IMG { vertical-align: middle; }
			TABLE.typo3-browsetree TR TD IMG.c-recIcon { margin-right: 1px;}
			TABLE.typo3-browsetree { margin-bottom: 10px; width: 95%; }

			TABLE.typo3-browsetree TR TD.typo3-browsetree-control {
				padding: 0px;
			}
			TABLE.typo3-browsetree TR TD.typo3-browsetree-control a {
				padding: 0px 3px 0px 3px;
				background-color: ' . $buttonColor . ';
			}
			TABLE.typo3-browsetree TR TD.typo3-browsetree-control > a:hover {
				background-color:' . $buttonColorHover . ';
			}';

		$this->doc->inDocStylesArray['background-color'] = '
			#ext-dam-mod-treebrowser-index-php { background-color:#fff; }
			#ext-treelib-browser { background-color:#fff; }
		';

		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib('js/tree.js');

		if ($allowProducts) {
			// Check if we need to allow browsing of products.
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				Tree.thisScript = "../../../../../typo3/ajax.php",
				Tree.ajaxID = "Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapse";
			');
		} else {
			// Check if we need to allow browsing of products.
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				Tree.thisScript = "../../../../../typo3/ajax.php",
				Tree.ajaxID = "Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapseWithoutProduct";
			');
		}

		// Setting JavaScript for menu
		// in this context, the function jumpTo is different
		// it adds the Category to the mountpoints
		$this->doc->JScode .= $this->doc->wrapScriptTags(
			($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '

			function jumpTo(id,linkObj,highLightID,script)	{
				var catUid = id.substr(id.lastIndexOf("=") + 1); //We can leave out the "="
				var text   = (linkObj.firstChild) ? linkObj.firstChild.nodeValue : "Unknown";
				//Params (field, value, caption)
				parent.setFormValueFromBrowseWin("data[' . $this->table . '][' . $this->uid . '][' . $this->field . ']", catUid, text);
			}
		');
	}

	/**
	 * Main function - generating the click menu in whatever form it has.
	 *
	 * @return void
	 */
	public function main() {
		// get the data of the field - the currently selected items
		$row = $this->getRecordProcessed();

		$this->content .= $this->doc->startPage('Treeview Browser');

		/** @var t3lib_tceforms $form */
		$form = t3lib_div::makeInstance('t3lib_tceforms');
		$form->initDefaultBEmode();
		$form->backPath = $this->backPath;

		// modifying TCA to force the right rendering - not nice but works
		t3lib_div::loadTCA($this->table);

		$row['uid'] = $this->uid;

		$parameter = array();

		if (is_array($this->flexConfig)) {
			$parameter['fieldConf'] = array(
				'label' => $form->sL($this->flexConfig['label']),
				'config' => $this->flexConfig['config'],
				'defaultExtras' => $this->flexConfig['defaultExtras']
			);
		} else {
			$parameter['fieldConf'] = array(
				'label' => $form->sL($GLOBALS['TCA'][$this->table]['columns'][$this->field]['label']),
				'config' => $GLOBALS['TCA'][$this->table]['columns'][$this->field]['config']
			);
		}

		$parameter['fieldConf']['config']['treeViewBrowseable'] = 'iframeContent';
		$parameter['fieldConf']['config']['noTableWrapping'] = TRUE;
		$parameter['itemFormElName'] = $this->itemFormElName;
		$parameter['itemFormElName_file'] = $this->itemFormElName;

		$this->content .= $form->getSingleField_SW($this->table, $this->field, $row, $parameter);
	}

	/**
	 * End page and output content.
	 *
	 * @return void
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Fetch the record data and return processed data for TCEforms
	 *
	 * @return array Record
	 */
	protected function getRecordProcessed() {
		// This will render MM relation fields in the correct way.
		// Read the whole record, which is not needed, but there's no other way.
		/** @var t3lib_transferData $trData */
		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = TRUE;
		$trData->lockRecords = TRUE;
		$trData->fetchRecord($this->table, $this->uid, '');
		reset($trData->regTableItems_data);
		$row = current($trData->regTableItems_data);

		return $row;
	}
}
