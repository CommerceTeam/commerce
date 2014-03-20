<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2009 Ingo Schmitt <is@marketing-factory.de>
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
 * Class Tx_Commerce_Hook_TceFormsHooks
 */
class Tx_Commerce_Hook_TceFormsHooks {
	/**
	 * @var array
	 */
	protected $extconf;

	/**
	 * @var boolean
	 */
	protected $lastMaxItems = FALSE;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->extconf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];
	}

	/**
	 * This hook gets called before a field in tceforms gets rendered. We use this to adjust TCA, to hide the NEW-Buttons for articles in simple mode
	 *
	 * @param string $table: The name of the database table (just for calling compatibility)
	 * @param string $field: The name of the field we work on in $table (just for calling compatibility)
	 * @param array $row: The values of all $fields in $table
	 * @return void
	 */
	public function getSingleField_preProcess($table, $field, &$row) {
		if (
			$table == 'tx_commerce_products'
			&& $this->extconf['simpleMode'] == 1
			&& $row['uid'] != $this->extconf['paymentID']
			&& $row['uid'] != $this->extconf['deliveryID']
			&& $row['l18n_parent'] != $this->extconf['paymentID']
			&& $row['l18n_parent'] != $this->extconf['deliveryID']
		) {
			$this->lastMaxItems = $GLOBALS['TCA']['tx_commerce_products']['columns']['articles']['config']['maxitems'];
			$GLOBALS['TCA']['tx_commerce_products']['columns']['articles']['config']['maxitems'] = 1;
		} elseif (
			$table == 'tx_commerce_products'
			&& $this->extconf['simpleMode'] == 1
			&& (
				$row['uid'] == $this->extconf['paymentID']
				|| $row['l18n_parent'] == $this->extconf['paymentID']
				|| $row['l18n_parent'] == $this->extconf['deliveryID']
			)
		) {
			$articlesArray = explode(',', $row['articles']);
			$this->lastMaxItems = $GLOBALS['TCA']['tx_commerce_products']['columns']['articles']['config']['maxitems'];
			$GLOBALS['TCA']['tx_commerce_products']['columns']['articles']['config']['maxitems'] = count($articlesArray);
		}

		if ($table == 'tx_commerce_article_prices') {
			$row['price_gross'] = $this->centurionDivision((int) $row['price_gross']);
			$row['price_net'] = $this->centurionDivision((int) $row['price_net']);
			$row['purchase_price'] = $this->centurionDivision((int) $row['purchase_price']);
		}
	}

	/**
	 * Converts a database price into a human readable one i.e. dividing it by 100 using . as a separator
	 *
	 * @param integer $price : The database price
	 * @return string: The $price divided by 100
	 */
	protected function centurionDivision($price) {
		$price = floatval($price);
		$result = sprintf('%01.2f', ($price / 100));
		return $result;
	}

	/**
	 * This hook gets called after a field in tceforms gets rendered. We use this to restore the old values after the hook above got called
	 *
	 * @param string $table: The name of the database table (just for calling compatibility)
	 * @param string $field: The name of the field we work on in $table (just for calling compatibility)
	 * @param array $row: The values of all $fields in $table
	 * @param string $out: Unknown, just for calling compatibility
	 * @param string $palette: Unknown, just for calling compatibility
	 * @param string $extra: Unknown, just for calling compatibility
	 * @return void: Nothing
	 */
	public function getSingleField_postProcess($table, $field, $row, &$out, $palette, $extra) {
			// This value is set, if the preProcess updated the tca earlyer
		if ($this->lastMaxItems !== FALSE) {
			$GLOBALS['TCA']['tx_commerce_products']['columns']['articles']['config']['maxitems'] = $this->lastMaxItems;
			$this->lastMaxItems = FALSE;
		}

		if (
			$table == 'tx_commerce_articles'
			&& $field == 'prices'
			&& !$row['sys_language_uid']
			&& strpos($row['uid_product'], '_' . $this->extconf['paymentID'] . '|') === FALSE
			&& strpos($row['uid_product'], '_' . $this->extconf['deliveryID'] . '|') === FALSE
			&& is_numeric($row['uid'])
		) {
			$splitText = '<div class="typo3-newRecordLink">';
			$outa = explode($splitText, $out, 2);
			$out = $outa[0] . $this->getScaleAmount($row['uid']) . $splitText . $outa[1];
		}
	}

	/**
	 * This function returns the html code for the scale price calculation
	 */
	protected function getScaleAmount($uid) {
		return '<div class="bgColor5">price scale startamount</div>
			<div class="bgColor4">
				<input style="width: 77px;" class="formField1" maxlength="20" name="data[tx_commerce_articles][' . $uid . '][create_new_scale_prices_startamount]" type="input">
			</div>
		</div>
		<div>
			<div class="bgColor5">price scale add prices</div>
			<div class="bgColor4">
				<input style="width: 77px;" class="formField1" maxlength="20" name="data[tx_commerce_articles][' . $uid . '][create_new_scale_prices_count]" type="input">
			</div>
		</div>
		<div>
			<div class="bgColor5">price scale steps</div>
			<div class="bgColor4">
				<input style="width: 77px;" class="formField1" maxlength="20" name="data[tx_commerce_articles][' . $uid . '][create_new_scale_prices_steps]" type="input">
			</div>
		</div>
		<div>
			<div class="bgColor5">price scale access</div>
			<div class="bgColor4">
				<input name="data[tx_commerce_articles][' . $uid . '][prices][data][sDEF][lDEF][create_new_scale_prices_fe_group][vDEF]_selIconVal" value="0" type="hidden">
				<select name="data[tx_commerce_articles][' . $uid . '][prices][data][sDEF][lDEF][create_new_scale_prices_fe_group][vDEF]" class="select" onchange="if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex=0;} TBE_EDITOR.fieldChanged(\'tx_commerce_articles\',\'' . $uid . '\',\'prices\',\'data[tx_commerce_articles][' . $uid . '][prices]\');">
					<option value="0" selected="selected"></option>
					<option value="-1">Hide at login</option>
					<option value="-2">Show at any login</option>
				</select>
			</div>
			';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/TceFormsHooks.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/TceFormsHooks.php']);
}

?>