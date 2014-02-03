<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Libary for frontend rendering of article prices.
 *
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @coauthor Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_article_price extends tx_commerce_element_alib {

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
		$this->databaseClass = 'tx_commerce_db_price';

		$this->fieldlist = array(
			'price_net',
			'price_gross',
			'fe_group',
			'price_scale_amount_start',
			'price_scale_amount_end'
		);

		$initializationResult = FALSE;
		$uid = (int) $uid;
		if ($uid > 0) {
			$this->uid = $uid;

			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);

			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'] as $classRef) {
					$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
				}
			}
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postinit')) {
					/** @noinspection PhpUndefinedMethodInspection */
					$hookObj->postinit($this);
				}
			}

			$initializationResult = TRUE;
		}
		$this->lang_uid = (int) $languageUid;

		return $initializationResult;
	}

	/**
	 * @param String $currency
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
	 * Get net price
	 *
	 * @return integer Price net
	 */
	public function getPriceNet() {
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postpricenet')) {
				/** @noinspection PhpUndefinedMethodInspection */
				$hookObj->postpricenet($this);
			}
		}

		return $this->price_net;
	}

	/**
	 * @param integer $priceNet
	 * @return void
	 */
	public function setPriceNet($priceNet) {
		$this->price_net = (int) $priceNet;
	}

	/**
	 * Get price gross
	 *
	 * @return integer price gross
	 */
	public function getPriceGross() {
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postpricegross')) {
				/** @noinspection PhpUndefinedMethodInspection */
				$hookObj->postpricegross($this);
			}
		}

		return $this->price_gross;
	}

	/**
	 * @param integer $priceGross
	 * @return void
	 */
	public function setPriceGross($priceGross) {
		$this->price_gross = (int) $priceGross;
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
	 * @param array $params Record value
	 * @return array New record values
	 */
	public function getTCARecordTitle($params) {
		/** @var language $language */
		$language = & $GLOBALS['LANG'];
		$params['title'] =
			$language->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_gross'), 1) . ': ' .
				sprintf('%01.2f', $params['row']['price_gross'] / 100) .
				' ,' . $language->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_net'), 1) . ': ' .
				sprintf('%01.2f', $params['row']['price_net'] / 100) .
				' (' . $language->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_start'), 1) . ': ' .
				$params['row']['price_scale_amount_start'] .
				' ' . $language->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_end'), 1) . ': ' .
				$params['row']['price_scale_amount_end'] . ') ' .
				' ' . ($params['row']['fe_group'] ? ($language->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'fe_group'), 1) . ' ' .
				t3lib_BEfunc::getProcessedValueExtra('tx_commerce_article_prices', 'fe_group', $params['row']['fe_group'], 100, $params['row']['uid'])) : '');

		return $params;
	}

	/**
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getPriceNet instead
	 * @return integer
	 */
	public function get_price_net() {
		t3lib_div::logDeprecatedFunction();

		return $this->getPriceNet();
	}

	/**
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getPriceGross instead
	 * @return integer
	 */
	public function get_price_gross() {
		t3lib_div::logDeprecatedFunction();

		return $this->getPriceGross();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']);
}

?>