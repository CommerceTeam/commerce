<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Ingo Schmitt (is@marketing-factory.de)
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
 * 
 * Part of the COMMERCE (Advanced Shopping System) extension.* 
 *
 * his class contains a hook for extending the fe user object with teh tx_commerce basket object
 * @see tx_commerce_basket
 * @see tx_commerce_basic_basket
 *
 * @package TYPO3
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 *
 * $Id: class.tx_commerce_feuserhooks.php 308 2006-07-26 22:23:51Z ingo $
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_basket.php');

class tx_commerce_feuserhooks	{
	
	function user_addBasket($params,&$pObj)	{
		$pObj->fe_user->tx_commerce_basket = t3lib_div::makeInstance('tx_commerce_basket');	
		$pObj->fe_user->tx_commerce_basket->set_session_id($pObj->fe_user->id);
		$pObj->fe_user->tx_commerce_basket->load_data();
	}
}
 
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/hooks/class.tx_commerce_feuserhooks.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/hooks/class.tx_commerce_feuserhooks.php']);
}
 ?>