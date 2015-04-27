<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2006 Thomas Hempel <thomas@work.de>
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
 * Implements the dynaflex config for the 'tx_commerce_articles' table
 */
class Tx_Commerce_Configuration_Dca_Articles {

	/**
	 * @var array
	 */
	public $rowChecks = array();

	/**
	 * @var array
	 */
	public $DCA = array(
		0 => array(
			'path' => 'tx_commerce_articles/columns/attributesedit/config/ds/default',
			'modifications' => array(
				array(
					'method' => 'remove',
					'inside' => 'ROOT/el',
					'element' => 'dummy',
				),
				array(
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array(
						'table' => 'tx_commerce_articles_article_attributes_mm',
						'select' => '*',
						'where' => 'uid_local=###uid###',
						'orderby' => 'sorting'
					),
					'allUserFunc' => 'Tx_Commerce_Utility_AttributeEditorUtility->getAttributeEditFields',
				),
			),
		),
		/**
		 * This configuration is for the prices sheet. We have to give the user the
		 * possibility to add a free number of prices to all products. Each of that
		 * prices have it's own access fields, so the user can define different prices
		 * for various usergroups.
		 */
		1 => array(
			'path' => 'tx_commerce_articles/types/0/showitem',
			'parseXML' => FALSE,
			'modifications' => array(
				array(
					'method' => 'add',
					'type' => 'append',
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.extras'
					),
				),
			),
		),
	);

	/**
	 * @var string
	 */
	public $cleanUpField = 'attributes';

	/**
	 * @var array
	 */
	public $hooks = array();

	/**
	 * @param array $resultDca
	 * @return void
	 */
	public function alterDCA_onLoad(&$resultDca) {
		/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		if (
			!(
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('data') == NULL
			) &&
			$backendUser->uc['txcommerce_afterDatabaseOperations'] != 1
		) {
			$resultDca = array();
		}
	}
}
