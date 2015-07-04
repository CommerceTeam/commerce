<?php
namespace CommerceTeam\Commerce\Hook;
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

use CommerceTeam\Commerce\Factory\SettingsFactory;

/**
 * Class \CommerceTeam\Commerce\Hook\TceFormsHooks
 *
 * @author 2008-2009 Ingo Schmitt <is@marketing-factory.de>
 */
class TceFormsHooks {
	/**
	 * Extension configuration
	 *
	 * @var array
	 */
	protected $extconf;

	/**
	 * Last max items
	 *
	 * @var bool
	 */
	protected $lastMaxItems = FALSE;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->extconf = SettingsFactory::getInstance()->getExtConfComplete();
	}

	/**
	 * This hook gets called before a field in tceforms gets rendered. We use this
	 * to adjust TCA, to hide the NEW-Buttons for articles in simple mode
	 *
	 * @param string $table The name of the database table
	 * @param string $field The name of the field we work on in $table
	 * @param array $row The values of all $fields in $table
	 *
	 * @return void
	 */
	public function getSingleField_preProcess($table, $field, &$row) {
		if ($table == 'tx_commerce_products' && $this->extconf['simpleMode'] == 1) {
			$this->lastMaxItems = SettingsFactory::getInstance()->getTcaValue('tx_commerce_products.columns.articles.config.maxitems');
			$productColumns = & $GLOBALS['TCA']['tx_commerce_products']['columns'];

			if ($row['uid'] != $this->extconf['paymentID']
				&& $row['uid'] != $this->extconf['deliveryID']
				&& $row['l18n_parent'] != $this->extconf['paymentID']
				&& $row['l18n_parent'] != $this->extconf['deliveryID']
			) {
				$productColumns['articles']['config']['maxitems'] = 1;
			} else {
				$productColumns['articles']['config']['maxitems'] = count(explode(',', $row['articles']));
			}
		}

		if ($table == 'tx_commerce_article_prices') {
			$row['price_gross'] = $this->centurionDivision((int) $row['price_gross']);
			$row['price_net'] = $this->centurionDivision((int) $row['price_net']);
			$row['purchase_price'] = $this->centurionDivision((int) $row['purchase_price']);
		}
	}

	/**
	 * Converts a database price into a human readable one i.e. dividing
	 * it by 100 using . as a separator
	 *
	 * @param int $price The database price
	 *
	 * @return string The $price divided by 100
	 */
	protected function centurionDivision($price) {
		$price = floatval($price);
		$result = sprintf('%01.2f', ($price / 100));
		return $result;
	}

	/**
	 * This hook gets called after a field in tceforms gets rendered. We use this to
	 * restore the old values after the hook above got called
	 *
	 * @param string $table The name of the database table
	 * @param string $field The name of the field we work on in $table
	 * @param array $row The values of all $fields in $table
	 * @param string $out Unknown, just for calling compatibility
	 * @param string $palette Unknown, just for calling compatibility
	 * @param string $extra Unknown, just for calling compatibility
	 *
	 * @return void
	 */
	public function getSingleField_postProcess($table, $field, array $row, &$out, $palette, $extra) {
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
	 *
	 * @param int $uid Uid
	 *
	 * @return string
	 */
	protected function getScaleAmount($uid) {
		return '<div class="bgColor5">price scale startamount</div>
			<div class="bgColor4">
				<input style="width: 77px;" class="formField1" maxlength="20" name="data[tx_commerce_articles][' . $uid .
			'][create_new_scale_prices_startamount]" type="text"/>
			</div>
		</div>
		<div>
			<div class="bgColor5">price scale add prices</div>
			<div class="bgColor4">
				<input style="width: 77px;" class="formField1" maxlength="20" name="data[tx_commerce_articles][' . $uid .
			'][create_new_scale_prices_count]" type="text"/>
			</div>
		</div>
		<div>
			<div class="bgColor5">price scale steps</div>
			<div class="bgColor4">
				<input style="width: 77px;" class="formField1" maxlength="20" name="data[tx_commerce_articles][' . $uid .
			'][create_new_scale_prices_steps]" type="text"/>
			</div>
		</div>
		<div>
			<div class="bgColor5">price scale access</div>
			<div class="bgColor4">
				<input name="data[tx_commerce_articles][' . $uid .
			'][prices][data][sDEF][lDEF][create_new_scale_prices_fe_group][vDEF]_selIconVal" value="0" type="hidden"/>
				<select name="data[tx_commerce_articles][' . $uid .
			'][prices][data][sDEF][lDEF][create_new_scale_prices_fe_group][vDEF]" class="select"
			 		onchange="if (this.options[this.selectedIndex].value == \'--div--\') { this.selectedIndex = 0; } TBE_EDITOR.fieldChanged(\'tx_commerce_articles\',\'' .
			$uid . '\',\'prices\',\'data[tx_commerce_articles][' . $uid . '][prices]\');">
					<option value="0" selected="selected"></option>
					<option value="-1">Hide at login</option>
					<option value="-2">Show at any login</option>
				</select>
			</div>
			';
	}
}
