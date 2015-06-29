<?php
namespace CommerceTeam\Commerce\Configuration\Dca;
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

/**
 * Implements the dynaflex config for the 'tx_commerce_articles' table
 *
 * Class \CommerceTeam\Commerce\Configuration\Dca\Articles
 *
 * @author 2005-2006 Thomas Hempel <thomas@work.de>
 */
class Articles {

	/**
	 * Rows to check
	 *
	 * @var array
	 */
	public $rowChecks = array();

	/**
	 * Dynamic configuration array
	 *
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
					'allUserFunc' => 'CommerceTeam\\Commerce\\Utility\\AttributeEditorUtility->getAttributeEditFields',
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
	 * Cleanup field
	 *
	 * @var string
	 */
	public $cleanUpField = 'attributes';

	/**
	 * Hooks
	 *
	 * @var array
	 */
	public $hooks = array();

	/**
	 * Alter dca on load
	 *
	 * @param array $resultDca Result dca
	 *
	 * @return void
	 */
	public function alterDCA_onLoad(array &$resultDca = array()) {
		if (
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('data') != NULL
			&& $this->getBackendUser()->uc['txcommerce_afterDatabaseOperations'] != 1
		) {
			$resultDca = array();
		}
	}


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
