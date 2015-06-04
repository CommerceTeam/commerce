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

/**
 * Libary for frontend rendering of article prices.
 *
 * Class Tx_Commerce_Domain_Model_ArticlePrice
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Model_ArticlePrice extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_ArticlePriceRepository';

	/**
	 * @var Tx_Commerce_Domain_Repository_ArticlePriceRepository
	 */
	public $databaseConnection;

	/**
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
	 * @var String Currency for price
	 */
	protected $currency = 'EUR';

	/**
	 * @var integer Price scale amount start
	 */
	protected $price_scale_amount_start = 1;

	/**
	 * @var integer Price scale amount end
	 */
	protected $price_scale_amount_end = 1;

	/**
	 * @var integer Price gross
	 */
	protected $price_gross = 0;

	/**
	 * @var integer Price net
	 */
	protected $price_net = 0;

	/**
	 * Usual init method
	 *
	 * @param integer $uid Uid of product
	 * @param integer $languageUid Uid of language, unused
	 * @return boolean TRUE if $uid is > 0
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
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						/** @noinspection PhpUndefinedMethodInspection */
						$hookObj->postinit($this);
					}
				}
			}
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						/** @noinspection PhpUndefinedMethodInspection */
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
	 * Setter
	 *
	 * @param String $currency
	 * @return void
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
	}

	/**
	 * @return String
	 */
	public function getCurrency() {
		return $this->currency;
	}

	/**
	 * @param integer $priceNet
	 * @return void
	 */
	public function setPriceNet($priceNet) {
		$this->price_net = (int) $priceNet;
	}

	/**
	 * Get net price
	 *
	 * @return integer Price net
	 */
	public function getPriceNet() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article_price.php\'][\'postpricenet\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/ArticlePrice.php\'][\'postPriceNet\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricenet')) {
					/** @noinspection PhpUndefinedMethodInspection */
					$hookObj->postpricenet($this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceNet'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceNet'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricenet')) {
					/** @noinspection PhpUndefinedMethodInspection */
					$hookObj->postpricenet($this);
				}
			}
		}

		return $this->price_net;
	}

	/**
	 * @param integer $priceGross
	 * @return void
	 */
	public function setPriceGross($priceGross) {
		$this->price_gross = (int) $priceGross;
	}

	/**
	 * Get price gross
	 *
	 * @return integer price gross
	 */
	public function getPriceGross() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article_price.php\'][\'postpricegross\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/ArticlePrice.php\'][\'postPriceGross\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricegross')) {
					/** @noinspection PhpUndefinedMethodInspection */
					$hookObj->postpricegross($this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceGross'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/ArticlePrice.php']['postPriceGross'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postpricegross')) {
					/** @noinspection PhpUndefinedMethodInspection */
					$hookObj->postpricegross($this);
				}
			}
		}

		return $this->price_gross;
	}

	/**
	 * Get price scale amount start
	 *
	 * @return integer Scale amount start
	 */
	public function getPriceScaleAmountStart() {
		return $this->price_scale_amount_start;
	}

	/**
	 * Get price scale amount end
	 *
	 * @return integer Scale amount end
	 */
	public function getPriceScaleAmountEnd() {
		return $this->price_scale_amount_end;
	}

	/**
	 * Returns TCA label, used in TCA only
	 *
	 * @param array &$params Record value
	 * @return void
	 */
	public function getTcaRecordTitle(&$params) {
		$language = $this->getLanguageService();

		$params['title'] =
			$language->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_gross'), 1) . ': ' .
				sprintf('%01.2f', $params['row']['price_gross'] / 100) .
				', ' . $language->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_net'), 1) . ': ' .
				sprintf('%01.2f', $params['row']['price_net'] / 100) .
				' (' . $language->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_start'), 1) . ': ' .
				$params['row']['price_scale_amount_start'] .
				' ' . $language->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_end'), 1) . ': ' .
				$params['row']['price_scale_amount_end'] . ') ' .
				' ' . ($params['row']['fe_group'] ? ($language->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tx_commerce_article_prices', 'fe_group'), 1) . ' ' .
				\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValueExtra('tx_commerce_article_prices', 'fe_group', $params['row']['fe_group'], 100, $params['row']['uid'])) : '');
	}


	/**
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceNet instead
	 * @return integer
	 */
	public function get_price_net() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getPriceNet();
	}

	/**
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceGross instead
	 * @return integer
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
