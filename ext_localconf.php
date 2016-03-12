<?php

call_user_func(function ($packageKey) {
    // Define special article types
    define('NORMALARTICLETYPE', 1);
    define('PAYMENTARTICLETYPE', 2);
    define('DELIVERYARTICLETYPE', 3);

    $typo3ConfVars = &$GLOBALS['TYPO3_CONF_VARS'];
    $scOptions = &$typo3ConfVars['SC_OPTIONS'];
    $extConf = &$typo3ConfVars['EXT']['extConf'][$packageKey];

    // Unserialize the plugin configuration so we can use it
    // This array holds global definitions of arbitrary commerce settings
    // Add unserialized ext conf settings to global array for easy access
    if (is_string($extConf)) {
        $extConf = unserialize($extConf);
    }

    // Payment settings
    $extConf['SYSPRODUCTS']['PAYMENT'] = [
        'tablefields' => [
            'title' => 'SYSTEMPRODUCT_PAYMENT',
            'description' => 'Products to manage payment articles',
        ],
        'types' => [
            'invoice' => [
                'type' => PAYMENTARTICLETYPE,
                'class' => \CommerceTeam\Commerce\Payment\Invoice::class,
            ],
            'prepayment' => [
                'type' => PAYMENTARTICLETYPE,
                'class' => \CommerceTeam\Commerce\Payment\Prepayment::class,
            ],
            'cashondelivery' => [
                'type' => PAYMENTARTICLETYPE,
                'class' => \CommerceTeam\Commerce\Payment\Cashondelivery::class,
            ],
            'creditcard' => [
                'type' => PAYMENTARTICLETYPE,
                'class' => \CommerceTeam\Commerce\Payment\Creditcard::class,
                'provider' => [
                    'wirecard' => [
                        'class' => \CommerceTeam\Commerce\Payment\Provider\Wirecard::class,
                    ],
                ],
            ],
        ],
    ];

    // Delivery settings
    $extConf['SYSPRODUCTS']['DELIVERY'] = [
        'tablefields' => [
            'title' => 'SYSTEMPRODUCT_DELIVERY',
            'description' => 'Product to manage delivery articles',
        ],
        'types' => [
            'sysdelivery' => [
                'type' => DELIVERYARTICLETYPE,
            ],
        ]
    ];

    if (is_array($extConf)) {
        $extConf = serialize($extConf);
    }

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
        $typo3ConfVars['SYS']['caching']['cacheConfigurations']['commerce_navigation'] = [];
    }

    if (TYPO3_MODE == 'BE') {
        // XCLASS for version preview
        // This XCLASS will create a link to singlePID / previewPageID
        // in version module for commerce products
        $typo3ConfVars['SYS']['Objects'][\TYPO3\CMS\Version\Controller\VersionModuleController::class] = [
            'className' => \CommerceTeam\Commerce\Xclass\VersionModuleController::class,
        ];

        // For TYPO3 6.2
        $typo3ConfVars['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\NewRecordController::class] = [
            'className' => \CommerceTeam\Commerce\Xclass\NewRecordController::class,
        ];

        // Add category tree control with data provider
        $typo3ConfVars['SYS']['formEngine']['nodeRegistry']['1456642633182'] = [
            'nodeName' => 'commerceCategoryTree',
            'priority' => 100,
            'class' => \CommerceTeam\Commerce\Form\Element\CategoryTreeElement::class
        ];
        $typo3ConfVars['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
            [\CommerceTeam\Commerce\Form\FormDataProvider\TcaSelectItems::class] =
        [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
            ],
            'before' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
            ],
        ];

        // Add existing articles control for product
        $typo3ConfVars['SYS']['formEngine']['nodeRegistry']['1456642633183'] = [
            'nodeName' => 'commerceExistingArticles',
            'priority' => 100,
            'class' => \CommerceTeam\Commerce\Form\Element\ExistingArticlesElement::class
        ];
        // Add available articles control for product
        $typo3ConfVars['SYS']['formEngine']['nodeRegistry']['1456642633184'] = [
            'nodeName' => 'commerceAvailableArticles',
            'priority' => 100,
            'class' => \CommerceTeam\Commerce\Form\Element\AvailableArticlesElement::class
        ];
        $typo3ConfVars['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
            [\CommerceTeam\Commerce\Form\FormDataProvider\DatabaseRowArticleData::class] =
        [
            'depends' => [
                \CommerceTeam\Commerce\Form\FormDataProvider\TcaSelectItems::class,
            ],
            'before' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
            ],
        ];

        // CLI Script configuration
        // Add statistic task
        /* @noinspection PhpUndefinedVariableInspection */
        $scOptions['scheduler']['tasks'][\CommerceTeam\Commerce\Task\StatisticTask::class] = [
            'extension' => $packageKey,
            'title' => 'LLL:EXT:' . $packageKey
                . '/Resources/Private/Language/locallang_be.xlf:tx_commerce_task_statistictask.name',
            'description' => 'LLL:EXT:' . $packageKey
                . '/Resources/Private/Language/locallang_be.xlf:tx_commerce_task_statistictask.description',
            'additionalFields' => \CommerceTeam\Commerce\Task\StatisticTaskAdditionalFieldProvider::class,
        ];
    }

    // Adding some hooks for tx_commerce_article_processing
    // As basic hook for calculation the delivery_costs
    if (empty($typo3ConfVars['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'])) {
        $typo3ConfVars['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'] =
            \CommerceTeam\Commerce\Hooks\ArticleHook::class;
    }

    // Add hook to handle orders while moving them to a different state
    if (empty($typo3ConfVars['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders'])) {
        $typo3ConfVars['EXTCONF']['commerce/Classes/Hook/DataMapHooks.php']['moveOrders']['commerce'] =
            \CommerceTeam\Commerce\Hooks\OrdermailHook::class;
    }

    // Hook called while deleting an address in addresses controller
    $typo3ConfVars['EXTCONF']['commerce/Controller/AddressesController']['deleteAddress']['commerce'] =
        \CommerceTeam\Commerce\Hooks\Pi4Hook::class;
    $typo3ConfVars['EXTCONF']['commerce/Controller/AddressesController']['saveAddress']['commerce'] =
        \CommerceTeam\Commerce\Hooks\Pi4Hook::class;
    // Add hook to create or update address data to sr_feuser_register create and edit feuser process
    $typo3ConfVars['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess']['commerce'] =
        \CommerceTeam\Commerce\Hooks\SrfeuserregisterPi1Hook::class;


    // register for RteHtmlParser::TS_links_rte and ContentObjectRenderer::resolveMixedLinkParameter
    $scOptions['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] =
        \CommerceTeam\Commerce\LinkHandler\CommerceLinkHandler::class;


    // Hooks for datamap processing
    // For processing the order sfe, when changing the pid
    $scOptions['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['commerce'] =
        \CommerceTeam\Commerce\Hooks\DataMapHook::class;

    // Hooks for commandmap processing
    // For new drawing of the category tree after having deleted a record
    $scOptions['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['commerce'] =
        \CommerceTeam\Commerce\Hooks\CommandMapHook::class;

    // Tree update javascript rendering hooks
    $scOptions['t3lib/class.t3lib_befunc.php']['updateSignalHook']['updateCategoryTree'] =
        \CommerceTeam\Commerce\Hooks\UpdateSignalHook::class . '->updateCategoryTree';

    // Hooks for version swap processing
    // For processing the order sfe, when changing the pid
    //@todo check if needed
    $scOptions['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass']['commerce'] =
        \CommerceTeam\Commerce\Hooks\VersionHook::class;

    //@todo check if needed
    $scOptions['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook']['commerce'] =
        \CommerceTeam\Commerce\Hooks\IrreHook::class;

    // Hook to render recordlist parts differently
    //@todo check if needed
    $scOptions['typo3/class.db_list_extra.inc']['actions']['commerce'] =
        \CommerceTeam\Commerce\Hooks\LocalRecordListHook::class;

}, 'commerce');
