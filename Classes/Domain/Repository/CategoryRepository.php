<?php
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
 * Database Class for tx_commerce_categories. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_category to get informations for articles.
 *
 * Class Tx_Commerce_Domain_Repository_CategoryRepository
 *
 * @author 2005-2012 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Repository_CategoryRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * Database table
	 *
	 * @var string
	 */
	public $databaseTable = 'tx_commerce_categories';

	/**
	 * Database parent category relation table
	 *
	 * @var string
	 */
	protected $databaseParentCategoryRelationTable = 'tx_commerce_categories_parent_category_mm';

	/**
	 * Database attribute relation table
	 *
	 * @var string Attribute rel table
	 */
	protected $databaseAttributeRelationTable = 'tx_commerce_categories_attributes_mm';

	/**
	 * Category sorting field
	 *
	 * @var string
	 */
	protected $categoryOrderField = 'tx_commerce_categories.sorting';

	/**
	 * Product sorting field
	 *
	 * @var string
	 */
	protected $productOrderField = 'tx_commerce_products.sorting';

	/**
	 * Uid of current Category
	 *
	 * @var int
	 */
	protected $uid;

	/**
	 * Language Uid
	 *
	 * @var int
	 */
	protected $lang_uid;

	/**
	 * Gets the "master" category from this category
	 *
	 * @param int $uid Category uid
	 *
	 * @return int Category uid
	 */
	public function getParentCategory($uid) {
		$database = $this->getDatabaseConnection();

		$result = 0;
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) && $uid > 0) {
			$this->uid = $uid;
			$row = $database->exec_SELECTgetSingleRow(
				'uid_foreign',
				$this->databaseParentCategoryRelationTable,
				'uid_local = ' . (int) $uid . ' and is_reference = 0'
			);
			if (is_array($row) && count($row)) {
				$result = $row['uid_foreign'];
			}
		}

		return $result;
	}

	/**
	 * Returns the permissions information for the category with the uid
	 *
	 * @param int $uid Category UID
	 *
	 * @return array Array with permission information
	 */
	public function getPermissionsRecord($uid) {
		$database = $this->getDatabaseConnection();

		$result = array();
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) && ($uid > 0)) {
			$result = $database->exec_SELECTgetSingleRow(
				'perms_everybody, perms_user, perms_group, perms_userid, perms_groupid, editlock',
				$this->databaseTable,
				'uid = ' . $uid
			);
		}

		return $result;
	}

	/**
	 * Gets the parent categories from this category
	 *
	 * @param int $uid Category uid
	 *
	 * @return array Parent categories Uids
	 */
	public function getParentCategories($uid) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}

		$database = $this->getDatabaseConnection();
		$this->uid = $uid;
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$additionalWhere = $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
		} else {
			$additionalWhere = ' AND ' . $this->databaseTable . '.deleted = 0';
		}

		$result = $database->exec_SELECT_mm_query(
			'uid_foreign',
			$this->databaseTable,
			$this->databaseParentCategoryRelationTable,
			$this->databaseTable,
			' AND ' . $this->databaseParentCategoryRelationTable . '.uid_local= ' . (int) $uid . ' ' . $additionalWhere
		);

		if ($result) {
			$data = array();
			while (($row = $database->sql_fetch_assoc($result))) {
				// @todo access_check for data sets
				$data[] = (int) $row['uid_foreign'];
			}
			$database->sql_free_result($result);
			return $data;
		}
		return FALSE;
	}

	/**
	 * Returns an array of sys_language_uids of the i18n categories
	 * Only use in BE
	 *
	 * @param int $uid Uid of the category we want to get the i18n languages from
	 *
	 * @return array Array of UIDs
	 */
	public function getL18nCategories($uid) {
		if ((empty($uid)) || (!is_numeric($uid))) {
			return FALSE;
		}

		$database = $this->getDatabaseConnection();
		$this->uid = $uid;
		$res = $database->exec_SELECTquery(
			't1.title, t1.uid, t2.flag, t2.uid as sys_language',
			$this->databaseTable . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
			'l18n_parent = ' . $uid . ' AND deleted = 0'
		);
		$uids = array();
		while (($row = $database->sql_fetch_assoc($res))) {
			$uids[] = $row;
		}
		return $uids;
	}

	/**
	 * Gets the child categories from this category
	 *
	 * @param int $uid Product UID
	 * @param int $languageUid Language UID
	 *
	 * @return array Array of child categories UID
	 */
	public function getChildCategories($uid, $languageUid = -1) {
		if (empty($uid) || !is_numeric($uid)) {
			return array();
		}

		if ($languageUid == -1) {
			$languageUid = 0;
		}
		$this->uid = $uid;
		if ($languageUid == 0 && $this->getFrontendController()->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$languageUid = $this->getFrontendController()->tmpl->setup['config.']['sys_language_uid'];
		}
		$this->lang_uid = $languageUid;

		// @todo Sorting should be by database
		// 'tx_commerce_categories_parent_category_mm.sorting'
		// as TYPO3 isn't currently able to sort by MM tables
		// We are using $this->databaseTable.sorting

		$localOrderField = $this->categoryOrderField;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'categoryOrder\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'categoryOrder\']
			');
			$hookObj = GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']
			);
			if (method_exists($hookObj, 'categoryOrder')) {
				$localOrderField = $hookObj->categoryOrder($this->categoryOrderField, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryOrder']) {
			$hookObj = GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryOrder']
			);
			if (method_exists($hookObj, 'categoryOrder')) {
				$localOrderField = $hookObj->categoryOrder($this->categoryOrderField, $this);
			}
		}

		$additionalWhere = $this->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);

		$database = $this->getDatabaseConnection();

		$result = $database->exec_SELECT_mm_query(
			'uid_local',
			$this->databaseTable, $this->databaseParentCategoryRelationTable,
			$this->databaseTable,
			' AND ' . $this->databaseParentCategoryRelationTable . '.uid_foreign= ' . (int) $uid . ' ' . $additionalWhere,
			'',
			$localOrderField
		);
		$return = array();
		if ($result) {
			$data = array();
			while (($row = $database->sql_fetch_assoc($result))) {
				// @todo access_check for datasets
				if ($languageUid == 0) {
					$data[] = (int) $row['uid_local'];
				} else {
					// Check if a localised product is availiabe for this product
					// @todo Check if this is correct in Multi Tree Sites
					$lresult = $database->exec_SELECTquery(
						'uid',
						'tx_commerce_categories',
						'l18n_parent = ' . (int) $row['uid_local'] . ' AND sys_language_uid=' . $languageUid .
							$this->enableFields('tx_commerce_categories', $GLOBALS['TSFE']->showHiddenRecords)
					);

					if ($database->sql_num_rows($lresult)) {
						$data[] = (int) $row['uid_local'];
					}
				}
			}

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook']) {
				GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'categoryQueryPostHook\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'categoryQueryPostHook\']
				');
				$hookObj = GeneralUtility::getUserObj(
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook']
				);
				if (is_object($hookObj) && method_exists($hookObj, 'categoryQueryPostHook')) {
					$data = $hookObj->categoryQueryPostHook($data, $this);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryQueryPostHook']) {
				$hookObj = GeneralUtility::getUserObj(
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryQueryPostHook']
				);
				if (is_object($hookObj) && method_exists($hookObj, 'categoryQueryPostHook')) {
					$data = $hookObj->categoryQueryPostHook($data, $this);
				}
			}

			$database->sql_free_result($result);
			$return = $data;
		}
		return $return;
	}

	/**
	 * Gets child products from this category
	 *
	 * @param int $uid Product uid
	 * @param int $languageUid Language uid
	 *
	 * @return array Array of child products UIDs
	 */
	public function getChildProducts($uid, $languageUid = -1) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}

		if ($languageUid == -1) {
			$languageUid = 0;
		}
		$this->uid = $uid;
		if ($languageUid == 0 && $this->getFrontendController()->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$languageUid = $this->getFrontendController()->tmpl->setup['config.']['sys_language_uid'];
		}
		$this->lang_uid = $languageUid;

		$localOrderField = $this->productOrderField;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'productOrder\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'productOrder\']
			');
			$hookObj = GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']
			);
			if (is_object($hookObj) && method_exists($hookObj, 'productOrder')) {
				$localOrderField = $hookObj->productOrder($localOrderField, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productOrder']) {
			$hookObj = GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productOrder']
			);
			if (is_object($hookObj) && method_exists($hookObj, 'productOrder')) {
				$localOrderField = $hookObj->productOrder($localOrderField, $this);
			}
		}

		$whereClause = 'AND tx_commerce_products_categories_mm.uid_foreign = ' . (int) $uid . '
			AND tx_commerce_products.uid=tx_commerce_articles.uid_product
			AND tx_commerce_articles.uid=tx_commerce_article_prices.uid_article ';
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$whereClause .= $this->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords);
			$whereClause .= $this->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
			$whereClause .= $this->enableFields('tx_commerce_article_prices', $GLOBALS['TSFE']->showHiddenRecords);
		}

			// Versioning - no deleted or versioned records, nor live placeholders
		$whereClause .= ' AND tx_commerce_products.deleted = 0 AND tx_commerce_products.pid != -1 AND tx_commerce_products.t3ver_state != 1';
		$queryArray = array(
			'SELECT' => 'tx_commerce_products.uid',
			'FROM' => 'tx_commerce_products ,tx_commerce_products_categories_mm,tx_commerce_articles, tx_commerce_article_prices',
			'WHERE' => 'tx_commerce_products.uid=tx_commerce_products_categories_mm.uid_local ' . $whereClause,
			'GROUPBY' => '',
			'ORDERBY' => $localOrderField,
			'LIMIT' => ''
		);

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'productQueryPreHook\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'productQueryPreHook\']
			');
			$hookObj = GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']
			);
			if (is_object($hookObj) && method_exists($hookObj, 'productQueryPreHook')) {
				$queryArray = $hookObj->productQueryPreHook($queryArray, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPreHook']) {
			$hookObj = GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPreHook']
			);
			if (is_object($hookObj) && method_exists($hookObj, 'productQueryPreHook')) {
				$queryArray = $hookObj->productQueryPreHook($queryArray, $this);
			}
		}

		$database = $this->getDatabaseConnection();

		$return = FALSE;
		$result = $database->exec_SELECT_queryArray($queryArray);
		if ($result !== FALSE) {
			$data = array();
			while (($row = $database->sql_fetch_assoc($result)) !== FALSE) {
				if ($languageUid == 0) {
					$data[] = (int) $row['uid'];
				} else {
					// Check if a locallized product is availabe
					// @todo Check if this is correct in multi tree sites
					$lresult = $database->exec_SELECTquery(
						'uid',
						'tx_commerce_products', 'l18n_parent = ' . (int) $row['uid'] . ' AND sys_language_uid=' . $languageUid .
							$this->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords)
					);
					if ($database->sql_num_rows($lresult)) {
						$data[] = (int) $row['uid'];
					}
				}
			}
			$database->sql_free_result($result);

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']) {
				GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'productQueryPostHook\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'productQueryPostHook\']
				');
				$hookObj = GeneralUtility::getUserObj(
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']
				);
				if (is_object($hookObj) && method_exists($hookObj, 'productQueryPostHook')) {
					$data = $hookObj->productQueryPostHook($data, $this);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPostHook']) {
				$hookObj = GeneralUtility::getUserObj(
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPostHook']
				);
				if (is_object($hookObj) && method_exists($hookObj, 'productQueryPostHook')) {
					$data = $hookObj->productQueryPostHook($data, $this);
				}
			}

			$return = $data;
		}
		return $return;
	}

	/**
	 * Returns an array of array for the TS rootline
	 * Recursive Call to build rootline
	 *
	 * @param int $categoryUid Category uid
	 * @param string $clause Where clause
	 * @param array $result Result
	 *
	 * @return array
	 */
	public function getCategoryRootline($categoryUid, $clause = '', array $result = array()) {
		if ($categoryUid) {
			$row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
				'tx_commerce_categories.uid, mm.uid_foreign AS parent',
				'tx_commerce_categories
					INNER JOIN tx_commerce_categories_parent_category_mm AS mm ON tx_commerce_categories.uid = mm.uid_local',
				'tx_commerce_categories.uid = ' . (int) $categoryUid .
				$this->enableFields('tx_commerce_categories', $GLOBALS['TSFE']->showHiddenRecords)
			);

			if (is_array($row) && count($row) && $row['parent'] <> $categoryUid) {
				$result = $this->getCategoryRootline((int) $row['parent'], $clause, $result);
			}

			$result[] = array(
				'uid' => $row['uid'],
			);
		}

		return $result;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}
