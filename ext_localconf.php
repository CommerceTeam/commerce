<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Base configuration settings for commerce.
 * This file will be merged to typo3conf/temp_CACHED_hash_ext_localconf.php
 * together with all other ext_localconf.php files of other extensions.
 * The code in here will be executed very early on every frontend and backend access.
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 *
 * @TODO Check which parts could be moved to ext_tables.php to only include in BE processing
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

	// Definition of some helpfull constants
if (!defined('COMMERCE_EXTkey')) {
	/** @noinspection PhpUndefinedVariableInspection */
	define('COMMERCE_EXTkey', $_EXTKEY);
}
if (!defined('COMMERCE_EXTKEY')) {
	/** @noinspection PhpUndefinedVariableInspection */
	define('COMMERCE_EXTKEY', $_EXTKEY);
}

if (!defined('PATH_txcommerce')) {
	define('PATH_txcommerce', t3lib_extMgm::extPath(COMMERCE_EXTKEY));
}
if (!defined('PATH_TXCOMMERCE')) {
	define('PATH_TXCOMMERCE', t3lib_extMgm::extPath(COMMERCE_EXTKEY));
}

if (!defined('PATH_txcommerce_rel')) {
	define('PATH_txcommerce_rel', t3lib_extMgm::extRelPath(COMMERCE_EXTKEY));
}
if (!defined('PATH_TXCOMMERCE_REL')) {
	define('PATH_TXCOMMERCE_REL', t3lib_extMgm::extRelPath(COMMERCE_EXTKEY));
}

if (!defined('PATH_txcommerce_icon_table_rel')) {
	define('PATH_txcommerce_icon_table_rel', PATH_TXCOMMERCE_REL . 'res/icons/table/');
}
if (!defined('PATH_TXCOMMERCE_ICON_TABLE_REL')) {
	define('PATH_TXCOMMERCE_ICON_TABLE_REL', PATH_TXCOMMERCE_REL . 'res/icons/table/');
}

if (!defined ('PATH_txcommerce_icon_tree_rel')) {
	define('PATH_txcommerce_icon_tree_rel', PATH_TXCOMMERCE_REL . 'res/icons/table/');
}
if (!defined ('PATH_TXCOMMERCE_ICON_TREE_REL')) {
	define('PATH_TXCOMMERCE_ICON_TREE_REL', PATH_TXCOMMERCE_REL . 'res/icons/table/');
}

	// Define special article types
define('NORMALArticleType', 1);
define('NORMALARTICLETYPE', 1);

define('PAYMENTArticleType', 2);
define('PAYMENTARTICLETYPE', 2);

define('DELIVERYArticleType', 3);
define('DELIVERYARTICLETYPE', 3);


	// Unserialize the plugin configuration so we can use it
$_EXTCONF = unserialize($_EXTCONF);

	// This array holds global definitions of arbitrary commerce settings
	// Add unserialized ext conf settings to global array for easy access of those settings
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'] = $_EXTCONF;

	// Payment settings
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['PAYMENT'] = array(
	'tablefields' => array(
		'title' => 'SYSTEMPRODUCT_PAYMENT',
		'description' => 'Products to manage payment',
	),
	'types' => array(
		'invoice' => array(
			'class' => 'tx_commerce_payment_invoice',
			'type' => PAYMENTARTICLETYPE,
		),
		'prepayment' => array(
			'class' => 'tx_commerce_payment_prepayment',
			'type' => PAYMENTARTICLETYPE,
		),
		'cashondelivery' => array(
			'class' => 'tx_commerce_payment_cashondelivery',
			'type' => PAYMENTARTICLETYPE,
		),
		'creditcard' => array(
			'class' => 'tx_commerce_payment_creditcard',
			'type' => PAYMENTARTICLETYPE,
				// Language file for external credit card check
			'ccvs_language_files' => PATH_TXCOMMERCE . 'payment/ccvs/language',
			'provider' => array(
				'wirecard' => array(
						// @TODO: Remove this implementation if it turns out that it does not work anymore
					'class' => 'tx_commerce_payment_provider_wirecard',
				),
			),
		),
	),
);

	// Delivery settings
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['DELIVERY'] = array(
	'tablefields' => array(
		'title' => 'SYSTEMPRODUCT_DELIVERY',
		'description' => 'product zum Verwalten der Lieferarten',
	)
);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['DELIVERY']['types'] = array(
	'sysdelivery' => array(
		'type' => DELIVERYARTICLETYPE
	),
);


	// Add frontend plugins to content.default static template
t3lib_extMgm::addPItoST43(COMMERCE_EXTKEY, 'pi1/class.tx_commerce_pi1.php', '_pi1', 'list_type', 1);
t3lib_extMgm::addPItoST43(COMMERCE_EXTKEY, 'pi2/class.tx_commerce_pi2.php', '_pi2', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTKEY, 'pi3/class.tx_commerce_pi3.php', '_pi3', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTKEY, 'pi4/class.tx_commerce_pi4.php', '_pi4', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTKEY, 'pi6/class.tx_commerce_pi6.php', '_pi6', 'list_type', 0);


t3lib_extMgm::addTypoScript(COMMERCE_EXTKEY, 'editorcfg', '
	tt_content.CSS_editor.ch.tx_commerce_pi6 = < plugin.tx_commerce_pi6.CSS_editor
', 43);


if (TYPO3_MODE == 'BE') {
		// XCLASS for version preview
		// This XCLASS will create a link to singlePID / previewPageID in version module for commerce products
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/cm1/index.php'] = PATH_TXCOMMERCE . 'class.ux_versionindex.php';

		// XCLASS for db list enable the search module to search in OrderIds
		// Field tx_commerce_orders.order_id is of type none, but the BE list module doesn't search in those fields by default
		// @see http://bugs.typo3.org/view.php?id=5676
		// @see http://forge.typo3.org/issues/17329
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc'] = PATH_TXCOMMERCE . 'class.ux_localrecordlist.php';
}


	// Add linkhandler for "commerce"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] = 'EXT:commerce/hooks/class.tx_commerce_linkhandler.php:&tx_commerce_linkhandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'EXT:commerce/hooks/class.tx_commerce_browselinkshooks.php:tx_commerce_browselinkshooks';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'EXT:commerce/hooks/class.tx_commerce_browselinkshooks.php:tx_commerce_browselinkshooks';


	// Add ajax listener for tree in linkcommerce
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_commerce_browselinkshooks::ajaxExpandCollapse'] = 'EXT:commerce/hooks/class.tx_commerce_browselinkshooks.php:tx_commerce_browselinkshooks->ajaxExpandCollapse';


	// Hooks for datamap procesing
	// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_dmhooks.php:tx_commerce_dmhooks';

	// Hooks for commandmap processing
	// For new drawing of the category tree after having deleted a record
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_cmhooks.php:tx_commerce_cmhooks';

	// Hooks for version swap procesing
	// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_versionhooks.php:tx_commerce_versionhooks';


	// Adding some hooks for tx_commerce_article_processing
	// As basic hook for calculation the delivery_costs
if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'] = 'EXT:commerce/hooks/class.tx_commerce_articlehooks.php:tx_commerce_articlehooks';
}

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'][] = 'EXT:commerce/hooks/class.tx_commerce_ordermailhooks.php:tx_commerce_ordermailhooks';
}


	// Adding the AJAX listeners for Permission change/Browsing the Category tree
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['SC_mod_access_perm_ajax::dispatch'] = 'EXT:commerce/mod_access/class.sc_mod_access_perm_ajax.php:SC_mod_access_perm_ajax->dispatch';
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_commerce_access_navframe::ajaxExpandCollapse'] = 'EXT:commerce/mod_access/class.tx_commerce_access_navframe.php:tx_commerce_access_navframe->ajaxExpandCollapse';
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_commerce_category_navframe::ajaxExpandCollapse'] = 'EXT:commerce/mod_category/class.tx_commerce_category_navframe.php:tx_commerce_category_navframe->ajaxExpandCollapse';


	// This line configures to process the code selectConf with the class "tx_commerce_hooks"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_tcehooksHandler.php:tx_commerce_tcehooksHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_tcehooksHandler.php:tx_commerce_tcehooksHandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'][] = 'EXT:commerce/hooks/class.tx_commerce_irrehooks.php:tx_commerce_irrehooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'EXT:commerce/hooks/class.tx_commerce_tceforms_hooks.php:tx_commerce_tceforms_hooks';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] = 'EXT:commerce/hooks/class.tx_srfeuserregister_commerce_hooksHandler.php:tx_srfeuserregister_commerce_hooksHandler';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']['deleteAddress'][] = 'EXT:commerce/hooks/class.tx_commerce_pi4hooksHandler.php:tx_commerce_pi4hooksHandler';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']['saveAddress'][] = 'EXT:commerce/hooks/class.tx_commerce_pi4hooksHandler.php:tx_commerce_pi4hooksHandler';

	// CLI Skript configration
if (TYPO3_MODE == 'BE') {
		// Setting up scripts that can be run from the cli_dispatch.phpsh script
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][COMMERCE_EXTKEY] = array(
		'EXT:' . COMMERCE_EXTKEY . '/cli/class.tx_commerce_cli.php',
		'_CLI_commerce'
	);
}

	// Register dynaflex dca files
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_categories'][] = 'EXT:commerce/dcafiles/class.tx_commerce_categories_dfconfig.php:tx_commerce_categories_dfconfig';

?>