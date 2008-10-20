<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Thomas Hempel (thomas@work.de)
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
 * A metaclass for creating inputfield fields in the backend.
 *
 * @package TYPO3
 * @subpackage commerce
 * @author Thomas Hempel <thomas@work.de>
 * 
 * @maintainer Thomas Hempel <thomas@work.de>	
 * 
 * $Id: class.tx_commerce_attributeeditor.php 446 2006-12-04 21:52:42Z ingo $
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_create_folder.php');

class tx_commerce_attributeEditor {
	var $belib;

	/**
	 * A simple constructor for instaciating the backend library.
	 *
	 * @return	void
	 */
	function tx_commerce_attributeEditor() {
		$this->belib = t3lib_div::makeInstance('tx_commerce_belib');
	}

	/**
	 * This method creates a dynaflex field configuration from the submitted database entry.
	 * The variable "configData" contains the complete dynaflex configuration of the field and
	 * the data that where maybe fetched from the database.
	 *
	 * We have to fill the fields
	 *
	 * $config['name']
	 * $config['label']
	 * $config['config']
	 *
	 * @param	array		$data: The data array contains in element "row" the dataset of the table we're creating
	 * @param	array		$config: The config array is the fynaflex fieldconfiguration.
	 * @param	boolean		$fetchFromDB: If true the attribute data is fetched from DB
	 * @param	boolean		$onlyDisplay: If true the field is not an input field but is displayed
	 * @return	The	modified dynaflex configuration
	 */
	function getAttributeEditField($aData, &$config, $fetchFromDB = true, $onlyDisplay = false)	{
			// first of all, fetch data from attribute table
		if ($fetchFromDB)	{
			$aData = $this->belib->getAttributeData($aData['row']['uid_foreign'], 'uid,title,has_valuelist,multiple,unit,deleted');
		}
		
		if ($aData['deleted'] == 1) return array();
	
		/** 
		 * Try to detect article UID since there is currently no way to get the data from the method
		 * and get language_uid from article
		 * @author ingo schmitt <is@marketing-factory.de>
		 */
		 
		$getPostedit=t3lib_div::GParrayMerged('edit');
		if (is_array($getPostedit['tx_commerce_articles']))	{
			$articleUid = array_keys($getPostedit['tx_commerce_articles']);
			if ($articleUid[0] > 0)	{
				$lok_data = t3lib_BEfunc::getRecord('tx_commerce_articles', $articleUid[0], 'sys_language_uid');
				$sys_language_uid = $lok_data['sys_language_uid'];
			}
			if (empty($sys_language_uid))	{
				$sys_language_uid = 0;
			}
		}elseif (is_array($getPostedit['tx_commerce_products']))	{
			$articleUid = array_keys($getPostedit['tx_commerce_products']);
			if ($articleUid[0] > 0)	{
				$lok_data = t3lib_BEfunc::getRecord('tx_commerce_products', $articleUid[0], 'sys_language_uid');
				$sys_language_uid = $lok_data['sys_language_uid'];
			}
			if (empty($sys_language_uid))	{
				$sys_language_uid = 0;
			}
		}
		
		// set label and name
		$config['label'] = $aData['title'];
		$config['name'] = 'attribute_' .$aData['uid'];
		
		/**
		 * Try to get language label
		 */
		if ($sys_language_uid>0)	{
			$lok_data=t3lib_BEfunc::getRecordRaw('tx_commerce_attributes','sys_language_uid='.$sys_language_uid.' AND l18n_parent='.$aData['uid'],'*');
		}
		
		// get the value
		if ($onlyDisplay)	{
			$config['config']['type'] = 'user';
			$config['config']['userFunc'] = 'tx_commerce_attributeEditor->displayAttributeValue';
			$config['config']['aUid'] = $aData['uid'];
			return $config;
		}
		
		/**
		 * Get PID to select only the Attribute Values in the correct PID
		 * @since 3rd January 2006
		 * @author Ingo Schmitt <is@marketing-factory.de>
		 */
		 
		tx_commerce_create_folder::init_folders();
        list($modPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Commerce', 'commerce');
        list($prodPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Products', 'commerce', $modPid);
		list($attrPid, $defaultFolder, $folderList) = tx_graytree_folder_db::initFolders('Attributes', 'commerce', $modPid);
	  
		if ($aData['has_valuelist'] == 1)	{
			$config['config'] = array (
				'type' => 'select',
				'foreign_table' => 'tx_commerce_attribute_values',
				'foreign_table_where' => 'AND attributes_uid=' .intval($aData['uid']) .' and tx_commerce_attribute_values.pid='.intval($attrPid).'  ORDER BY value',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'items' => array (
					array('', 0)
				),
			);
			if (intval($aData['multiple']) == 1)	{
					// create a selectbox for multiple selection
				$config['config']['multiple'] = 1;
				$config['config']['size'] = 5;
				$config['config']['maxitems'] = 100;
				unset($config['config']['items']);
			}
		} else {
				// the field should be a simple input field
			($aData['unit'] != '') ? $config['label'] .= ' (' .$aData['unit'] .')' : '';
			$config['config'] = array('type' => 'input');
		}
		#debug($config);
		# Dont display in lokalised version Attributes with valuelist
		if (($aData['has_valuelist'] == 1) && ($sys_language_uid <> 0)) {
			$config['config']['type'] = '';
			return false;
		}
		
		return $config;
	}

	/**
	 * Returns the editfield dynaflex config for all attributes of a product
	 *
	 * @param	array		$funcDataArray: ...
	 * @param	array		$baseConfig: ...
	 * @return	An array with fieldconfigs
	 */
	function getAttributeEditFields($funcDataArray, $baseConfig)	{
		$result = array();

		$sortedAttributes = array();
		foreach ($funcDataArray as $funcData)	{
			if ($funcData['row']['uid_foreign'] == 0)	continue;
			
			$aData = $this->belib->getAttributeData($funcData['row']['uid_foreign'], 'uid,title,has_valuelist,multiple,unit,deleted');

				// get correlationtype for this attribute and the product of this article
				// first get the product for this aticle
			$productUid = $this->belib->getProductOfArticle($funcData['row']['uid_local'], false);

			$uidCT = $this->belib->getCtForAttributeOfProduct($funcData['row']['uid_foreign'], $productUid);
			$sortedAttributes[$uidCT][] = $aData;
		}
		ksort($sortedAttributes);
		reset($sortedAttributes);

		foreach ($sortedAttributes as $ctUid => $attributes)	{
				// add a userfunction as header
			$onlyDisplay = false;
			foreach ($attributes as $attribute)	{
				$onlyDisplay = (($ctUid == 1 && ($attribute['has_valuelist'])) || $ctUid == 4);
				$fieldConfig = $this->getAttributeEditField($attribute, $baseConfig, false, $onlyDisplay);
				
				if (is_array($fieldConfig) && (count($fieldConfig) > 0)) $result[] = $fieldConfig;
			}
		}
		
		return $result;
	}

	/**
	 * Simply returns the value of an attribute of an article.
	 *
	 * @param	array		$PA: 
	 * @param	array		$fObj: The form object
	 * @return	[type]		...
	 */
	function displayAttributeValue($PA, $fObj) {
			// attribute value uid
		$aUid = $PA['fieldConf']['config']['aUid'];
		
		$relRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_valuelist,default_value,value_char',
			'tx_commerce_articles_article_attributes_mm',
			'uid_local=' .intval($PA['row']['uid']) .' AND uid_foreign=' .intval($aUid)
		);
		
		$attributeData = $this->belib->getAttributeData($aUid, 'has_valuelist,multiple,unit');
		$relationData = NULL;
		if ($attributeData['multiple'] == 1)	{
			while ($relData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($relRes))	{
				$relationData[] = $relData;
			}
		} else {
			$relationData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($relRes);
		}
		
		return $this->belib->getAttributeValue(
			$PA['row']['uid'],
			$aUid,
			'tx_commerce_articles_article_attributes_mm',
			$relationData,
			$attributeData
		);
	}
	
	
	
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/class.tx_commerce_attributeeditor.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/class.tx_commerce_attributeeditor.php"]);
}
?>