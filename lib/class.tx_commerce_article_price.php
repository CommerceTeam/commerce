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
	 * @param integer $lang_uid Uid of language, unused
	 * @return booloan TRUE if $uid is > 0
	 */
	public function init($uid, $lang_uid = 0) {

		$this->database_class = 'tx_commerce_db_price';

		$this->fieldlist = array(
			'price_net',
			'price_gross',
			'fe_group',
			'price_scale_amount_start',
			'price_scale_amount_end'
		);

		$initializationResult = FALSE;
		$uid = intval($uid);
		if ($uid > 0) {
			$this->uid = $uid;

			$this->conn_db = new $this->database_class;

			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'] as $classRef) {
					$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
				}
			}
			foreach($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}

			$initializationResult = TRUE;
		}

		return $initializationResult;
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
		foreach($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postpricenet')) {
				$hookObj->postpricenet($this);
			}
		}

		return $this->price_net;
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
		foreach($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postpricegross')) {
				$hookObj->postpricegross($this);
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
	 * @TODO: Move this method somewhere, it does not belong here
	 * @params array Record value
	 * @params object Parent Object
	 * @return array New record values
	 */
	public function getTCARecordTitle($params, $pObj) {
		$params['title'] =
			$GLOBALS['LANG']->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_gross'), 1) . ': ' . tx_commerce_div::FormatPrice($params['row']['price_gross'] / 100) .
			' ,' . $GLOBALS['LANG']->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_net'), 1) . ': ' . tx_commerce_div::FormatPrice($params['row']['price_net']/100) .
			' (' . $GLOBALS['LANG']->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices','price_scale_amount_start'),1) . ': ' . $params['row']['price_scale_amount_start'] .
			'  ' . $GLOBALS['LANG']->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'price_scale_amount_end'), 1) . ': ' . $params['row']['price_scale_amount_end'] . ') ' .
			' ' . ($params['row']['fe_group'] ? ($GLOBALS['LANG']->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices', 'fe_group'), 1) . ' ' . t3lib_BEfunc::getProcessedValueExtra('tx_commerce_article_prices', 'fe_group', $params['row']['fe_group'], 100, $params['row']['uid'])) : '')
		;

		return $params;
	}

	/**
	 * @deprecated
	 */
	public function get_price_net() {
		return $this->getPriceNet();
	}

	/**
	 * @deprecated
	 */
	public function get_price_gross() {
		return $this->getPriceGross();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']);
}
?>