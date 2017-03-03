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
        '',
        '_pi1',
        'list_type',
        1
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($packageKey, '', '_pi2');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($packageKey, '', '_pi3');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($packageKey, '', '_pi4');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($packageKey, '', '_pi6');

    if (!is_array($typo3ConfVars['SYS']['caching']['cacheConfigurations']['commerce_navigation'])) {
        $typo3ConfVars['SYS']['caching']['cacheConfigurations']['commerce_navigation'] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'options' => array(
                'compression' => true,
                // 30 days; set this to a lower value in case your cache gets too big
                'defaultLifetime' => 2592000,
            ),
            'groups' => array('pages', 'all')
        ];
    }

    if (TYPO3_MODE == 'BE') {
        // XCLASS for version preview
        // This XCLASS will create a link to singlePID / previewPageID
        // in version module for commerce products
        // @todo check if needed
        $typo3ConfVars['SYS']['Objects'][\TYPO3\CMS\Version\Controller\VersionModuleController::class] = [
            'className' => \CommerceTeam\Commerce\Xclass\VersionModuleController::class,
        ];

        // XCLASS for new record controller to be able to treat categories like pages
        // and add a default value for categories and products
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
            'depends' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class],
            'before' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class],
        ];

        // Add existing articles control for product
        $typo3ConfVars['SYS']['formEngine']['nodeRegistry']['1456642633183'] = [
            'nodeName' => 'commerceExistingArticles',
            'priority' => 100,
            'class' => \CommerceTeam\Commerce\Form\Element\ExistingArticlesElement::class
        ];
        // Add available articles control for product
        $typo3ConfVars['SYS']['formEngine']['nodeRegistry']['1456642633184'] = [
            'nodeName' => 'commerceProducibleArticles',
            'priority' => 100,
            'class' => \CommerceTeam\Commerce\Form\Element\ProducibleArticlesElement::class
        ];
        $typo3ConfVars['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
            [\CommerceTeam\Commerce\Form\FormDataProvider\DatabaseRowArticleData::class] =
        [
            'depends' => [\CommerceTeam\Commerce\Form\FormDataProvider\TcaSelectItems::class],
            'before' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class],
        ];
        $typo3ConfVars['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
            [\CommerceTeam\Commerce\Form\FormDataProvider\DatabaseRowPriceData::class] =
        [
            'depends' => [\CommerceTeam\Commerce\Form\FormDataProvider\TcaSelectItems::class],
            'before' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class],
        ];

        // Add attribute select fields
        $typo3ConfVars['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
            [\CommerceTeam\Commerce\Form\FormDataProvider\TcaAttributeFields::class] =
        [
            'depends' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class],
            'before' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class],
        ];

        // Add attribute select values
        $typo3ConfVars['SYS']['formEngine']['formDataGroup']['flexFormSegment']
            [\CommerceTeam\Commerce\Form\FormDataProvider\DatabaseRowAttributeData::class] =
        [
            'depends' => [\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class],
            'before' => [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class],
        ];


        // CLI Script configuration
        // Add statistic task
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


    // register for RteHtmlParser::TS_links_rte and ContentObjectRenderer::resolveMixedLinkParameter
    $scOptions['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] =
        \CommerceTeam\Commerce\LinkHandler\CommerceLinkHandler::class;


    // Hooks for datamap processing
    // For processing the order sfe, when changing the pid
    $scOptions['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['commerce'] =
        \CommerceTeam\Commerce\Hooks\DataMapHook::class;
    $scOptions['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue']['commerce'] =
        \CommerceTeam\Commerce\Hooks\DataHandlerHook::class;

    // Hooks for commandmap processing
    // For new drawing of the category tree after having deleted a record
    $scOptions['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['commerce'] =
        \CommerceTeam\Commerce\Hooks\CommandMapHook::class;

    // Tree update javascript rendering hooks
    $scOptions['t3lib/class.t3lib_befunc.php']['updateSignalHook']['updateCategoryTree'] =
        \CommerceTeam\Commerce\Hooks\UpdateSignalHook::class . '->updateCategoryTree';
    // Tree update javascript rendering hooks
    $scOptions['t3lib/class.t3lib_befunc.php']['updateSignalHook']['updateOrderTree'] =
        \CommerceTeam\Commerce\Hooks\UpdateSignalHook::class . '->updateOrderTree';

    // Hook to render recordlist parts differently
    // Used to change recordlist rendering for move orders in order record list
    $scOptions['typo3/class.db_list_extra.inc']['actions']['commerce'] =
        \CommerceTeam\Commerce\Hooks\LocalRecordListHook::class;

    // Hooks for version swap processing
    // For processing the order sfe, when changing the pid
    // @todo check if needed
    $scOptions['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass']['commerce'] =
        \CommerceTeam\Commerce\Hooks\VersionHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['processedFilesChecksum'] =
        \CommerceTeam\Commerce\Updates\TceformsUpdateWizard::class;

    // @todo check if needed
    $scOptions['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook']['commerce'] =
        \CommerceTeam\Commerce\Hooks\IrreHook::class;
}, 'commerce');
