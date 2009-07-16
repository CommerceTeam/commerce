<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2006  Ingo Schmitt <is@marketing-factory.de>
*  (c)  2006  J�rg Sprung <jsp@marketing-factory.de>
*  All   rights reserved
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
 * Abstract library for handling basket and orders, should be extende by 
 * basket and order 
 * @see tx_commerce_order
 * @see tx_commerce_basket 

 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 * 
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @author	J�rg Sprung <jsp@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_item 
 *
 * $Id$
 *  */

 

 
 class tx_commerce_itemset {
 	
 	

 }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_itemset.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_temset.php']);
}
?>
