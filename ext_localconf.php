<?php
defined('TYPO3_MODE') or die();

$boot = function ($packageKey) {
    $typo3ConfVars = &$GLOBALS['TYPO3_CONF_VARS'];
    $scOptions = &$typo3ConfVars['SC_OPTIONS'];

    // Definition of some helpful constants
    if (!defined('COMMERCE_EXTKEY')) {
        /* @noinspection PhpUndefinedVariableInspection */
        define('COMMERCE_EXTKEY', $packageKey);
    }

    if (!defined('PATH_TXCOMMERCE')) {
        define('PATH_TXCOMMERCE', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($packageKey));
    }

    if (!defined('PATH_TXCOMMERCE_REL')) {
        define('PATH_TXCOMMERCE_REL', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($packageKey));
    }

    if (!defined('PATH_TXCOMMERCE_ICON_TABLE_REL')) {
        define('PATH_TXCOMMERCE_ICON_TABLE_REL', PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/');
    }

    // Define special article types
    define('NORMALARTICLETYPE', 1);
    define('PAYMENTARTICLETYPE', 2);
    define('DELIVERYARTICLETYPE', 3);

    // Unserialize the plugin configuration so we can use it
    // This array holds global definitions of arbitrary commerce settings
    // Add unserialized ext conf settings to global array for easy access
    if (is_string($typo3ConfVars['EXT']['extConf'][$packageKey])) {
        $typo3ConfVars['EXT']['extConf'][$packageKey] = unserialize($typo3ConfVars['EXT']['extConf'][$packageKey]);
    }

    // Payment settings
    $typo3ConfVars['EXT']['extConf'][$packageKey]['SYSPRODUCTS']['PAYMENT'] = array(
        'tablefields' => array(
            'title' => 'SYSTEMPRODUCT_PAYMENT',
            'description' => 'Products to manage payment',
        ),
        'types' => array(
            'invoice' => array(
                'class' => \CommerceTeam\Commerce\Payment\Invoice::class,
                'type' => PAYMENTARTICLETYPE,
            ),
            'prepayment' => array(
                'class' => \CommerceTeam\Commerce\Payment\Prepayment::class,
                'type' => PAYMENTARTICLETYPE,
            ),
            'cashondelivery' => array(
                'class' => \CommerceTeam\Commerce\Payment\Cashondelivery::class,
                'type' => PAYMENTARTICLETYPE,
            ),
            'creditcard' => array(
                'class' => \CommerceTeam\Commerce\Payment\Creditcard::class,
                'type' => PAYMENTARTICLETYPE,
                // Language file for external credit card check
                'ccvs_language_files' => PATH_TXCOMMERCE . 'payment/ccvs/language',
                'provider' => array(
                    'wirecard' => array(
                        'class' => \CommerceTeam\Commerce\Payment\Provider\Wirecard::class,
                    ),
                ),
            ),
        ),
    );

    // Delivery settings
    $typo3ConfVars['EXT']['extConf'][$packageKey]['SYSPRODUCTS']['DELIVERY'] = array(
        'tablefields' => array(
            'title' => 'SYSTEMPRODUCT_DELIVERY',
            'description' => 'product zum Verwalten der Lieferarten',
        ),
    );
    $typo3ConfVars['EXT']['extConf'][$packageKey]['SYSPRODUCTS']['DELIVERY']['types'] = array(
        'sysdelivery' => array(
            'type' => DELIVERYARTICLETYPE,
        ),
    );

    // Add frontend plugins to content.default static template
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
        $packageKey,
        'Classes/Controller/ListController.php',
        '_pi1',
        'list_type',
        1
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
        $packageKey,
        'Classes/Controller/BasketController.php',
        '_pi2',
        'list_type',
        0
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
        $packageKey,
        'Classes/Controller/CheckoutController.php',
        '_pi3',
        'list_type',
        0
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
        $packageKey,
        'Classes/Controller/AddressesController.php',
        '_pi4',
        'list_type',
        0
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
        $packageKey,
        'Classes/Controller/InvoiceController.php',
        '_pi6',
        'list_type',
        0
    );

    if (!is_array($typo3ConfVars['SYS']['caching']['cacheConfigurations']['commerce_navigation'])) {
        $typo3ConfVars['SYS']['caching']['cacheConfigurations']['commerce_navigation'] = array();
    }

    if (TYPO3_MODE == 'BE') {
        // XCLASS for version preview
        // This XCLASS will create a link to singlePID / previewPageID
        // in version module for commerce products
        $typo3ConfVars['SYS']['Objects'][\TYPO3\CMS\Version\Controller\VersionModuleController::class] = array(
            'className' => \CommerceTeam\Commerce\Xclass\VersionModuleController::class,
        );

        // For TYPO3 6.2
        $typo3ConfVars['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\NewRecordController::class] = array(
            'className' => \CommerceTeam\Commerce\Xclass\NewRecordController::class,
        );

        // CLI Script configuration
        // Add statistic task
        /* @noinspection PhpUndefinedVariableInspection */
        $scOptions['scheduler']['tasks'][\CommerceTeam\Commerce\Task\StatisticTask::class] = array(
            'extension' => $packageKey,
            'title' => 'LLL:EXT:' . $packageKey
                . '/Resources/Private/Language/locallang_be.xlf:tx_commerce_task_statistictask.name',
            'description' => 'LLL:EXT:' . $packageKey
                . '/Resources/Private/Language/locallang_be.xlf:tx_commerce_task_statistictask.description',
            'additionalFields' => \CommerceTeam\Commerce\Task\StatisticTaskAdditionalFieldProvider::class,
        );
    }

    $scOptions['typo3/backend.php']['renderPreProcess']['commerce'] =
        \CommerceTeam\Commerce\Hook\BackendHooks::class . '->addJsFiles';

    // Hooks for datamap procesing
    // For processing the order sfe, when changing the pid
    $scOptions['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['commerce'] =
        \CommerceTeam\Commerce\Hook\DataMapHooks::class;

    // Hooks for commandmap processing
    // For new drawing of the category tree after having deleted a record
    $scOptions['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['commerce'] =
        \CommerceTeam\Commerce\Hook\CommandMapHooks::class;

    // Hooks for version swap processing
    // For processing the order sfe, when changing the pid
    $scOptions['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass']['commerce'] =
        \CommerceTeam\Commerce\Hook\VersionHooks::class;

    // Adding some hooks for tx_commerce_article_processing
    // As basic hook for calculation the delivery_costs
    if (empty($typo3ConfVars['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'])) {
        $typo3ConfVars['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'] =
            \CommerceTeam\Commerce\Hook\ArticleHooks::class;
    }

    if (empty($typo3ConfVars['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'])) {
        $typo3ConfVars['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders']['commerce'] =
            \CommerceTeam\Commerce\Hook\OrdermailHooks::class;
    }

    // Configuration to process the code selectConf
    $scOptions['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']['commerce'] =
        \CommerceTeam\Commerce\Hook\TcehooksHandlerHooks::class;
    $scOptions['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']['commerce'] =
        \CommerceTeam\Commerce\Hook\TceFormsHooks::class;

    $scOptions['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook']['commerce'] =
        \CommerceTeam\Commerce\Hook\IrreHooks::class;

    // Hook to render recordlist parts differently
    $scOptions['typo3/class.db_list_extra.inc']['actions']['commerce'] =
        \CommerceTeam\Commerce\Hook\LocalRecordListHooks::class;

    $typo3ConfVars['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess']['commerce'] =
        \CommerceTeam\Commerce\Hook\SrfeuserregisterPi1Hook::class;
    $typo3ConfVars['EXTCONF']['commerce/Controller/AddressesController']['deleteAddress']['commerce'] =
        \CommerceTeam\Commerce\Hook\Pi4Hooks::class;
    $typo3ConfVars['EXTCONF']['commerce/Controller/AddressesController']['saveAddress']['commerce'] =
        \CommerceTeam\Commerce\Hook\Pi4Hooks::class;

    // Register dynaflex dca files
    $GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_categories']['commerce'] =
        \CommerceTeam\Commerce\Configuration\Dca\Categories::class;
    $GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_products']['commerce'] =
        \CommerceTeam\Commerce\Configuration\Dca\Products::class;
    $GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_articles']['commerce'] =
        \CommerceTeam\Commerce\Configuration\Dca\Articles::class;

    if (is_array($typo3ConfVars['EXT']['extConf'][$packageKey])) {
        $typo3ConfVars['EXT']['extConf'][$packageKey] = serialize($typo3ConfVars['EXT']['extConf'][$packageKey]);
    }
};

$boot('commerce');
unset($boot);
