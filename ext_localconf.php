<?php
defined('TYPO3_MODE') or die();

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

// Definition of some helpfull constants
if (!defined('COMMERCE_EXTKEY')) {
    /* @noinspection PhpUndefinedVariableInspection */
    define('COMMERCE_EXTKEY', $_EXTKEY);
}

if (!defined('PATH_TXCOMMERCE')) {
    define('PATH_TXCOMMERCE', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(COMMERCE_EXTKEY));
}

if (!defined('PATH_TXCOMMERCE_REL')) {
    define('PATH_TXCOMMERCE_REL', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath(COMMERCE_EXTKEY));
}

if (!defined('PATH_TXCOMMERCE_ICON_TABLE_REL')) {
    define('PATH_TXCOMMERCE_ICON_TABLE_REL', PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/');
}

if (!defined('PATH_TXCOMMERCE_ICON_TREE_REL')) {
    define('PATH_TXCOMMERCE_ICON_TREE_REL', PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/');
}

// Define special article types
define('NORMALARTICLETYPE', 1);
define('PAYMENTARTICLETYPE', 2);
define('DELIVERYARTICLETYPE', 3);

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
            'class' => 'CommerceTeam\\Commerce\\Payment\\Invoice',
            'type' => PAYMENTARTICLETYPE,
        ),
        'prepayment' => array(
            'class' => 'CommerceTeam\\Commerce\\Payment\\Prepayment',
            'type' => PAYMENTARTICLETYPE,
        ),
        'cashondelivery' => array(
            'class' => 'CommerceTeam\\Commerce\\Payment\\Cashondelivery',
            'type' => PAYMENTARTICLETYPE,
        ),
        'creditcard' => array(
            'class' => 'CommerceTeam\\Commerce\\Payment\\Creditcard',
            'type' => PAYMENTARTICLETYPE,
                // Language file for external credit card check
            'ccvs_language_files' => PATH_TXCOMMERCE . 'payment/ccvs/language',
            'provider' => array(
                'wirecard' => array(
                    // @todo: Remove this implementation if it turns out that it does not work
                    'class' => 'CommerceTeam\\Commerce\\Payment\\Provider\\Wirecard',
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
    ),
);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['DELIVERY']['types'] = array(
    'sysdelivery' => array(
        'type' => DELIVERYARTICLETYPE,
    ),
);

// Add frontend plugins to content.default static template
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    COMMERCE_EXTKEY,
    'Classes/Controller/ListController.php',
    '_pi1',
    'list_type',
    1
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    COMMERCE_EXTKEY,
    'Classes/Controller/BasketController.php',
    '_pi2',
    'list_type',
    0
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    COMMERCE_EXTKEY,
    'Classes/Controller/CheckoutController.php',
    '_pi3',
    'list_type',
    0
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    COMMERCE_EXTKEY,
    'Classes/Controller/AddressesController.php',
    '_pi4',
    'list_type',
    0
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    COMMERCE_EXTKEY,
    'Classes/Controller/InvoiceController.php',
    '_pi6',
    'list_type',
    0
);

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['commerce_navigation'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['commerce_navigation'] = array();
}

if (TYPO3_MODE == 'BE') {
    // XCLASS for version preview
    // This XCLASS will create a link to singlePID / previewPageID
    // in version module for commerce products
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Version\\Controller\\VersionModuleController'] = array(
        'className' => 'CommerceTeam\\Commerce\\Xclass\\VersionModuleController',
    );

    // For TYPO3 6.2
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\NewRecordController'] = array(
        'className' => 'CommerceTeam\\Commerce\\Xclass\\NewRecordController',
    );

    require_once PATH_TXCOMMERCE . 'Classes/Utility/TyposcriptConfig.php';

    // CLI Script configuration
    // Add statistic task
    /* @noinspection PhpUndefinedVariableInspection */
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['CommerceTeam\\Commerce\\Task\\StatisticTask'] =
        array(
            'extension' => $_EXTKEY,
            'title' => 'LLL:EXT:' . $_EXTKEY .
                '/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.name',
            'description' => 'LLL:EXT:' . $_EXTKEY .
                '/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.description',
            'additionalFields' => 'CommerceTeam\\Commerce\\Task\\StatisticTaskAdditionalFieldProvider',
        );
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPreProcess']['commerce'] =
    'CommerceTeam\\Commerce\\Hook\\BackendHooks->addJsFiles';
 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
     'TYPO3.Components.SystemdataNavframe.DataProvider',
     'CommerceTeam\\Commerce\\Tree\\Pagetree\\ExtdirectSystemdataNavigationProvider',
     'commerce',
     'user,group'
 );


// Add linkhandler for "commerce"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] =
    'EXT:commerce/Classes/Hook/LinkhandlerHooks.php:&CommerceTeam\\Commerce\\Hook\\LinkhandlerHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] =
    'EXT:commerce/Classes/Hook/BrowselinksHooks.php:CommerceTeam\\Commerce\\Hook\\BrowselinksHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] =
    'EXT:commerce/Classes/Hook/BrowselinksHooks.php:CommerceTeam\\Commerce\\Hook\\BrowselinksHooks';

// Add ajax listener for tree in linkcommerce
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['CommerceTeam\\Commerce\\Hook\\BrowselinksHooks::ajaxExpandCollapse'] =
    'EXT:commerce/Classes/Hook/BrowselinksHooks.php:CommerceTeam\\Commerce\\Hook\\BrowselinksHooks->ajaxExpandCollapse';

// Hooks for datamap procesing
// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['commerce'] =
    'EXT:commerce/Classes/Hook/DataMapHooks.php:CommerceTeam\\Commerce\\Hook\\DataMapHooks';

// Hooks for commandmap processing
// For new drawing of the category tree after having deleted a record
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['commerce'] =
    'EXT:commerce/Classes/Hook/CommandMapHooks.php:CommerceTeam\\Commerce\\Hook\\CommandMapHooks';

// Hooks for version swap processing
// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass']['commerce'] =
    'EXT:commerce/Classes/Hook/VersionHooks.php:CommerceTeam\\Commerce\\Hook\\VersionHooks';

// Adding some hooks for tx_commerce_article_processing
// As basic hook for calculation the delivery_costs
if (empty(
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost']
)) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'] =
        'EXT:commerce/Classes/Hook/ArticleHooks.php:CommerceTeam\\Commerce\\Hook\\ArticleHooks';
}

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'][] =
        'EXT:commerce/Classes/Hook/OrdermailHooks.php:CommerceTeam\\Commerce\\Hook\\OrdermailHooks';
}

// Configuration to process the code selectConf
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']['commerce'] =
    'EXT:commerce/Classes/Hook/TcehooksHandlerHooks.php:CommerceTeam\\Commerce\\Hook\\TcehooksHandlerHooks';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'][] =
    'EXT:commerce/Classes/Hook/IrreHooks.php:CommerceTeam\\Commerce\\Hook\\IrreHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] =
    'EXT:commerce/Classes/Hook/TceFormsHooks.php:CommerceTeam\\Commerce\\Hook\\TceFormsHooks';

// Hook to render recordlist parts differently
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] =
    'EXT:commerce/Classes/Hook/LocalRecordListHooks.php:CommerceTeam\\Commerce\\Hook\\LocalRecordListHooks';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] =
    'EXT:commerce/Classes/Hook/SrfeuserregisterPi1Hook.php:CommerceTeam\\Commerce\\Hook\\SrfeuserregisterPi1Hook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/AddressesController.php']['deleteAddress'][] =
    'EXT:commerce/Classes/Hook/Pi4Hooks.php:CommerceTeam\\Commerce\\Hook\\Pi4Hooks';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/AddressesController.php']['saveAddress'][] =
    'EXT:commerce/Classes/Hook/Pi4Hooks.php:CommerceTeam\\Commerce\\Hook\\Pi4Hooks';

// Register dynaflex dca files
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_categories'][] =
    'EXT:commerce/Configuration/DCA/Categories.php:CommerceTeam\\Commerce\\Configuration\\Dca\\Categories';
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_products'][] =
    'EXT:commerce/Configuration/DCA/Products.php:CommerceTeam\\Commerce\\Configuration\\Dca\\Products';
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_articles'][] =
    'EXT:commerce/Configuration/DCA/Articles.php:CommerceTeam\\Commerce\\Configuration\\Dca\\Articles';
