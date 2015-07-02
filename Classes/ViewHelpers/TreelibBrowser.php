<?php
namespace CommerceTeam\Commerce\ViewHelpers;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\TreelibBrowser
 *
 * @author 2003-2011 Rene Fritz <r.fritz@colorcube.de>
 */
class TreelibBrowser extends \TYPO3\CMS\Backend\Module\BaseScriptClass {
	/**
	 * Table
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Field
	 *
	 * @var string
	 */
	protected $field;

	/**
	 * Uid
	 *
	 * @var int
	 */
	protected $uid;

	/**
	 * Item form element name
	 *
	 * @var string
	 */
	protected $itemFormElName;

	/**
	 * Flex config
	 *
	 * @var string
	 */
	protected $flexConfig;

	/**
	 * Back path
	 *
	 * @var string
	 */
	protected $backPath;

	/**
	 * Current sub script
	 *
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
		$this->table = GeneralUtility::_GP('table');
		$this->field = GeneralUtility::_GP('field');
		$this->uid = GeneralUtility::_GP('uid');
		$this->itemFormElName = GeneralUtility::_GP('elname');
		$this->flexConfig = GeneralUtility::_GP('config');
		$seckey = GeneralUtility::_GP('seckey');
		$allowProducts = GeneralUtility::_GP('allowProducts');

		if (!($seckey === GeneralUtility::shortMD5($this->table . '|' . $this->field . '|' . $this->uid . '|' . $this->itemFormElName .
				'|' . $this->flexConfig . '|' . $allowProducts . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']))) {
			die('access denied');
		}

		if ($this->flexConfig) {
			$this->flexConfig = unserialize(base64_decode($this->flexConfig));
		}

		$this->backPath = $GLOBALS['BACK_PATH'];

		// Initialize template object
		/**
		 * Document template
		 *
		 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
		 */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc = $doc;
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $this->backPath;

		// from tx_dam_SCbase
		$buttonColor = '#e3dfdb';
		$buttonColorHover = GeneralUtility::modifyHTMLcolor($buttonColor, -20, -20, -20);

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
				Tree.ajaxID = "CommerceTeam_Commerce_CategoryViewHelper::ajaxExpandCollapse";
			');
		} else {
			// Check if we need to allow browsing of products.
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				Tree.ajaxID = "CommerceTeam_Commerce_CategoryViewHelper::ajaxExpandCollapseWithoutProduct";
			');
		}

		// Setting JavaScript for menu
		// in this context, the function jumpTo is different
		// it adds the Category to the mountpoints
		$this->doc->JScode .= $this->doc->wrapScriptTags(
			($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '

			function jumpTo(id, linkObj, highLightID, script) {
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

		/**
		 * Form engine
		 *
		 * @var \TYPO3\CMS\Backend\Form\FormEngine $form
		 */
		$form = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$form->initDefaultBEmode();
		$form->backPath = $this->backPath;

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
		/**
		 * Data preprocessor
		 *
		 * @var \TYPO3\CMS\Backend\Form\DataPreprocessor $trData
		 */
		$trData = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
		$trData->addRawData = TRUE;
		$trData->lockRecords = TRUE;
		$trData->fetchRecord($this->table, $this->uid, '');
		reset($trData->regTableItems_data);

		$row = current($trData->regTableItems_data);

		return $row;
	}
}
