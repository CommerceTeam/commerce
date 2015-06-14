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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Libary for frontend rendering of article prices.
 *
 * Class Tx_Commerce_Domain_Model_ArticlePrice
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Model_ArticlePrice extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * Database class name
	 *
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_ArticlePriceRepository';

	/**
	 * Database connection
	 *
	 * @var Tx_Commerce_Domain_Repository_ArticlePriceRepository
	 */
	public $databaseConnection;

	/**
	 * Field list
	 *
	 * @var array
	 */
	protected $fieldlist = array(
		'price_net',
		'price_gross',
		'fe_group',
		'price_scale_amount_start',
		'price_scale_amount_end'
	);

	/**
	 * Currency for price
	 *
	 * @var string
	 */
	protected $currency = 'EUR';

	/**
	 * Price scale amount start
	 *
	 * @var int
	 */
	protected $price_scale_amount_start = 1;

	/**
	 * Price scale amount end
	 *
	 * @var int
	 */
	protected $price_scale_amount_end = 1;

	/**
	 * Price gross
	 *
	 * @var int
	 */
	protected $price_gross = 0;

	/**
	 * Price net
	 *
	 * @var int
	 */
	protected $price_net = 0;


	/**
	 * Constructor Method, calles init method
	 *
	 * @param int $uid Uid
	 * @param int $languageUid Language uid
	 *
	 * @return self
	 */
	public function __construct($uid = 0, $languageUid = 0) {
		if ((int) $uid) {
			$this->init($uid, $languageUid);
		}
	}

	/**
	 * Usual init method
	 *
	 * @param int $uid Uid of product
	 * @param int $languageUid Uid of language, unused
	 *
	 * @return bool TRUE if $uid is > 0
	 */
	public function init($uid, $languageUid = 0) {
		$initializationResult = FALSE;
		$this->uid = (int) $uid;
		if ($this->uid > 0) {
			$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);

			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'])) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article_price.php\'][\'postinit\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/ArticlePrice.php\'][\'postinit\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'] as
					$classRef
				) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}

			$initializationResult = TRUE;
		}
		$this->lang_uid = (int) $languageUid;

		return $initializationResult;
	}

	/**
	 * Set currency
	 *
	 * @param string $currency Currency
	 *
	 * @return void
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
	}

	/**
	 * Get currency
	 *
	 * @return string
	 */
	public function getCurrency() {
		return $this->currency;
	}

	/**
	 * Set price net
	 *
	 * @param int $priceNet Price net
	 *
	 * @return void
	 */
	public function setPriceNet($priceNet) {
		$this->price_net = (int) $priceNet;
	}

	/**
	 * Get net price
	 *
	 * @return int Price net
	 */
	public function getPriceNet() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article_price.php\'][\'postpricenet\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/ArticlePrice.php\'][\'postPriceNet\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'] as
				$classRef
			) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricenet')) {
					$hookObj->postpricenet($this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceNet'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceNet'] as
				$classRef
			) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricenet')) {
					$hookObj->postpricenet($this);
				}
			}
		}

		return $this->price_net;
	}

	/**
	 * Price gross
	 *
	 * @param int $priceGross Price gross
	 *
	 * @return void
	 */
	public function setPriceGross($priceGross) {
		$this->price_gross = (int) $priceGross;
	}

	/**
	 * Get price gross
	 *
	 * @return int price gross
	 */
	public function getPriceGross() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article_price.php\'][\'postpricegross\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/ArticlePrice.php\'][\'postPriceGross\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'] as
				$classRef
			) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricegross')) {
					$hookObj->postpricegross($this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceGross'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceGross'] as
				$classRef
			) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricegross')) {
					$hookObj->postpricegross($this);
				}
			}
		}

		return $this->price_gross;
	}

	/**
	 * Get price scale amount start
	 *
	 * @return int Scale amount start
	 */
	public function getPriceScaleAmountStart() {
		return $this->price_scale_amount_start;
	}

	/**
	 * Get price scale amount end
	 *
	 * @return int Scale amount end
	 */
	public function getPriceScaleAmountEnd() {
		return $this->price_scale_amount_end;
	}

	/**
	 * Returns TCA label, used in TCA only
	 *
	 * @param array $params Record value
	 *
	 * @return void
	 */
	public function getTcaRecordTitle(array &$params) {
		$language = $this->getLanguageService();

		$params['title'] = $language->sL(BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_gross'), 1) . ': ' .
			sprintf('%01.2f', $params['row']['price_gross'] / 100) .
			', ' . $language->sL(BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_net'), 1) . ': ' .
			sprintf('%01.2f', $params['row']['price_net'] / 100) .
			' (' . $language->sL(BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_start'), 1) . ': ' .
			$params['row']['price_scale_amount_start'] .
			' ' . $language->sL(BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_end'), 1) . ': ' .
			$params['row']['price_scale_amount_end'] . ') ' .
			(
				$params['row']['fe_group'] ? (
					$language->sL(BackendUtility::getItemLabel('tx_commerce_article_prices', 'fe_group'), 1) .
					BackendUtility::getProcessedValueExtra(
						'tx_commerce_article_prices',
						'fe_group',
						$params['row']['fe_group'],
						100,
						$params['row']['uid']
					)
				) :
				''
			);
	}


	/**
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceNet instead
	 * @return int
	 */
	public function get_price_net() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getPriceNet();
	}

	/**
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceGross instead
	 * @return int
	 */
	public function get_price_gross() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getPriceGross();
	}


	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}
