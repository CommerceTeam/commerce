<?php
/**
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

defined('TYPO3_MODE') or die('Access denied.');

// Definition of some helpfull constants
if (!defined('COMMERCE_EXTKEY')) {
	/** @noinspection PhpUndefinedVariableInspection */
	define('COMMERCE_EXTKEY', $_EXTKEY);
}
if (!defined('COMMERCE_EXTkey')) {
	define('COMMERCE_EXTkey', COMMERCE_EXTKEY);
}

if (!defined('PATH_TXCOMMERCE')) {
	define('PATH_TXCOMMERCE', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(COMMERCE_EXTKEY));
}
if (!defined('PATH_txcommerce')) {
	define('PATH_txcommerce', PATH_TXCOMMERCE);
}

if (!defined('PATH_TXCOMMERCE_REL')) {
	define('PATH_TXCOMMERCE_REL', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath(COMMERCE_EXTKEY));
}
if (!defined('PATH_txcommerce_rel')) {
	define('PATH_txcommerce_rel', PATH_TXCOMMERCE_REL);
}

if (!defined('PATH_TXCOMMERCE_ICON_TABLE_REL')) {
	define('PATH_TXCOMMERCE_ICON_TABLE_REL', PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/');
}
if (!defined('PATH_txcommerce_icon_table_rel')) {
	define('PATH_txcommerce_icon_table_rel', PATH_TXCOMMERCE_ICON_TABLE_REL);
}

if (!defined ('PATH_TXCOMMERCE_ICON_TREE_REL')) {
	define('PATH_TXCOMMERCE_ICON_TREE_REL', PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/');
}
if (!defined ('PATH_txcommerce_icon_tree_rel')) {
	define('PATH_txcommerce_icon_tree_rel', PATH_TXCOMMERCE_ICON_TREE_REL);
}

	// Define special article types
define('NORMALARTICLETYPE', 1);
define('NORMALArticleType', NORMALARTICLETYPE);

define('PAYMENTARTICLETYPE', 2);
define('PAYMENTArticleType', PAYMENTARTICLETYPE);

define('DELIVERYARTICLETYPE', 3);
define('DELIVERYArticleType', DELIVERYARTICLETYPE);


// Unserialize the plugin configuration so we can use it
$_EXTCONF = unserialize($_EXTCONF);

// This array holds global definitions of arbitrary commerce settings
// Add unserialized ext conf settings to global array for easy access
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'] = $_EXTCONF;

	// Payment settings
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['PAYMENT'] = array(
	'tablefields' => array(
		'title' => 'SYSTEMPRODUCT_PAYMENT',
		'description' => 'Products to manage payment',
	),
	'types' => array(
		'invoice' => array(
			'class' => 'Tx_Commerce_Payment_Invoice',
			'type' => PAYMENTARTICLETYPE,
		),
		'prepayment' => array(
			'class' => 'Tx_Commerce_Payment_Prepayment',
			'type' => PAYMENTARTICLETYPE,
		),
		'cashondelivery' => array(
			'class' => 'Tx_Commerce_Payment_Cashondelivery',
			'type' => PAYMENTARTICLETYPE,
		),
		'creditcard' => array(
			'class' => 'Tx_Commerce_Payment_Creditcard',
			'type' => PAYMENTARTICLETYPE,
				// Language file for external credit card check
			'ccvs_language_files' => PATH_TXCOMMERCE . 'payment/ccvs/language',
			'provider' => array(
				'wirecard' => array(
					// @todo: Remove this implementation if it turns out that it does not work
					'class' => 'Tx_Commerce_Payment_Provider_Wirecard',
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(COMMERCE_EXTKEY, 'Classes/Controller/ListController.php', '_pi1', 'list_type', 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(COMMERCE_EXTKEY, 'Classes/Controller/BasketController.php', '_pi2', 'list_type', 0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(COMMERCE_EXTKEY, 'Classes/Controller/CheckoutController.php', '_pi3', 'list_type', 0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(COMMERCE_EXTKEY, 'Classes/Controller/AddressesController.php', '_pi4', 'list_type', 0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(COMMERCE_EXTKEY, 'Classes/Controller/InvoiceController.php', '_pi6', 'list_type', 0);


if (TYPO3_MODE == 'BE') {
	// XCLASS for version preview
	// This XCLASS will create a link to singlePID / previewPageID
	// in version module for commerce products
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/cm1/index.php'] =
		PATH_TXCOMMERCE . 'Classes/Xclass/ux_versionindex.php';

	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/db_new.php'] =
		PATH_TXCOMMERCE . 'Classes/Xclass/ux_db_new.php';

	require_once(PATH_TXCOMMERCE . 'Classes/Utility/TyposcriptConfig.php');
}


// Add linkhandler for "commerce"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] =
	'EXT:commerce/Classes/Hook/LinkhandlerHooks.php:&Tx_Commerce_Hook_LinkhandlerHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] =
	'EXT:commerce/Classes/Hook/BrowselinksHooks.php:Tx_Commerce_Hook_BrowselinksHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] =
	'EXT:commerce/Classes/Hook/BrowselinksHooks.php:Tx_Commerce_Hook_BrowselinksHooks';

// Add ajax listener for tree in linkcommerce
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Tx_Commerce_Hook_BrowselinksHooks::ajaxExpandCollapse'] =
	'EXT:commerce/Classes/Hook/BrowselinksHooks.php:Tx_Commerce_Hook_BrowselinksHooks->ajaxExpandCollapse';


// Hooks for datamap procesing
// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['commerce'] =
	'EXT:commerce/Classes/Hook/DataMapHooks.php:Tx_Commerce_Hook_DataMapHooks';

// Hooks for commandmap processing
// For new drawing of the category tree after having deleted a record
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['commerce'] =
	'EXT:commerce/Classes/Hook/CommandMapHooks.php:Tx_Commerce_Hook_CommandMapHooks';

// Hooks for version swap procesing
// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass']['commerce'] =
	'EXT:commerce/Classes/Hook/VersionHooks.php:Tx_Commerce_Hook_VersionHooks';


// Adding some hooks for tx_commerce_article_processing
// As basic hook for calculation the delivery_costs
if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'] =
		'EXT:commerce/Classes/Hook/ArticleHooks.php:Tx_Commerce_Hook_ArticleHooks';
}

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'][] =
		'EXT:commerce/Classes/Hook/OrdermailHooks.php:Tx_Commerce_Hook_OrdermailHooks';
}

// This line configures to process the code selectConf
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']['commerce'] =
	'EXT:commerce/Classes/Hook/TcehooksHandlerHooks.php:Tx_Commerce_Hook_TcehooksHandlerHooks';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'][] =
	'EXT:commerce/Classes/Hook/IrreHooks.php:Tx_Commerce_Hook_IrreHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] =
	'EXT:commerce/Classes/Hook/TceFormsHooks.php:Tx_Commerce_Hook_TceFormsHooks';

// Hook to render recordlist parts differently
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] =
	'EXT:commerce/Classes/Hook/LocalRecordListHooks.php:Tx_Commerce_Hook_LocalRecordListHooks';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] =
	'EXT:commerce/Classes/Hook/SrfeuserregisterPi1Hook.php:Tx_Commerce_Hook_SrfeuserregisterPi1Hook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/AddressesController.php']['deleteAddress'][] =
	'EXT:commerce/Classes/Hook/Pi4Hooks.php:Tx_Commerce_Hook_Pi4Hooks';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/AddressesController.php']['saveAddress'][] =
	'EXT:commerce/Classes/Hook/Pi4Hooks.php:Tx_Commerce_Hook_Pi4Hooks';

// CLI Script configuration
if (TYPO3_MODE == 'BE') {
	// Setting up scripts that can be run from the cli_dispatch.phpsh script
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][COMMERCE_EXTKEY] = array(
		PATH_TXCOMMERCE . 'Classes/Cli/Statistic.php',
		'_CLI_commerce'
	);

	// Add statistic task
	/** @noinspection PhpUndefinedVariableInspection */
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Commerce_Task_StatisticTask'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.name',
		'description' =>
			'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.description',
		'additionalFields' => 'Tx_Commerce_Task_StatisticTaskAdditionalFieldProvider',
	);
}

// Register dynaflex dca files
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_categories'][] =
	'EXT:commerce/Configuration/DCA/Categories.php:Tx_Commerce_Configuration_Dca_Categories';
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_products'][] =
	'EXT:commerce/Configuration/DCA/Product.php:Tx_Commerce_Configuration_Dca_Products';
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_articles'][] =
	'EXT:commerce/Configuration/DCA/Articles.php:Tx_Commerce_Configuration_Dca_Articles';
