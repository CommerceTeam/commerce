<?php

call_user_func(function ($packageKey) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $packageKey,
        'Configuration/TypoScript/',
        'COMMERCE'
    );

    if (TYPO3_MODE == 'BE') {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $packageKey . '/Configuration/PageTS/ModWizards.ts">'
        );

        // register commerce link handlers
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
        TCEMAIN.linkHandler {
            commerce {
                handler = ' . \CommerceTeam\Commerce\LinkHandler\CommerceLinkHandler::class . '
                label = LLL:EXT:commerce/Resources/Private/Language/locallang_mod_main.xlf:mlang_tabs_tab
                displayAfter = page
                displayBefore = file
                scanAfter = page
            }
        }
        ');

        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Imaging\IconRegistry::class
        );
        $iconRegistry->registerIcon(
            'extensions-commerce-globus',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:commerce/Resources/Public/Icons/Table/globus.gif']
        );
        // icon to show for system folders with contains commerce selected
        $iconRegistry->registerIcon(
            'apps-pagetree-folder-contains-commerce',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:commerce/Resources/Public/Icons/Table/folder.gif']
        );
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-commerce'] =
            'apps-pagetree-folder-contains-commerce';

        // positioning of main module is not considered in the core
        // that's why we need to use the following loop to add the main module
        if (!isset($GLOBALS['TBE_MODULES']['commerce'])) {
            $tbeModules = [];
            foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
                $tbeModules[$key] = $val;
                if ($key == 'file') {
                    $tbeModules['commerce'] = 'category';
                }
            }
            $GLOBALS['TBE_MODULES'] = $tbeModules;
        }

        // Main module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            '',
            '',
            '',
            [
                'script' => '_DISPATCH',
                'access' => 'user,group',
                'name' => $packageKey,
                'workspaces' => 'online',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_main.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_main.gif'
                    ],
                ],
            ]
        );

        // Category module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'category',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\CategoryModuleController::class . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_category',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_category.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_category.gif'
                    ],
                ],
            ]
        );
        // Category navigation frame
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent(
            'commerce_category',
            'commerce-categorytree',
            'commerce'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
            'TYPO3.Components.CategoryTree.DataProvider',
            \CommerceTeam\Commerce\Tree\CategoryTree\ExtdirectTreeDataProvider::class
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
            'TYPO3.Components.CategoryTree.Commands',
            \CommerceTeam\Commerce\Tree\CategoryTree\ExtdirectTreeCommands::class
        );

        // Permission Module
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'CommerceTeam.Commerce',
            'commerce',
            'commerce_permission',
            '',
            [
                'PermissionModule' => 'index, edit, update'
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:beuser/Resources/Public/Icons/module-permission.svg',
                'labels' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf',
                'navigationComponentId' => 'commerce-permissiontree'
            ]
        );
        // Permission navigation frame
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent(
            'commerce_permission',
            'commerce-permissiontree',
            'commerce'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
            'TYPO3.Components.PermissionTree.DataProvider',
            \CommerceTeam\Commerce\Tree\CategoryTree\ExtdirectTreeDataProvider::class
        );

        // Orders module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'order',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\OrdersModuleController::class . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_order',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_orders.gif'
                    ],
                ],
            ]
        );
        // Order navigation frame
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent(
            'commerce_order',
            'commerce-ordertree',
            'commerce'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
            'TYPO3.Components.OrderTree.DataProvider',
            \CommerceTeam\Commerce\Tree\OrderTree\ExtdirectTreeDataProvider::class
        );

        // Systemdata module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'systemdata',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\SystemdataModuleAttributeController::class
                    . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_systemdata',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_systemdata.gif'
                    ],
                ],
                'navigationFrameModule' => 'commerce_systemdata_navigation',
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_systemdata',
            \CommerceTeam\Commerce\Controller\SystemdataModuleAttributeController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_attributes'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_systemdata',
            \CommerceTeam\Commerce\Controller\SystemdataModuleManufacturerController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_manufacturer'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_systemdata',
            \CommerceTeam\Commerce\Controller\SystemdataModuleSupplierController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_supplier'
        );

        // Statistic Module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'statistic',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\StatisticModuleShowStatisticsController::class
                    . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_statistic',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_statistic.gif'
                    ],
                ],
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_statistic',
            \CommerceTeam\Commerce\Controller\StatisticModuleShowStatisticsController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf:statistics'
        );

        if (\CommerceTeam\Commerce\Utility\ConfigurationUtility::getInstance()->getExtConf('allowAggregation') == 1) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
                'commerce_statistic',
                \CommerceTeam\Commerce\Controller\StatisticModuleIncrementalAggregationController::class,
                null,
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf:incremental_aggregation'
            );

            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
                'commerce_statistic',
                \CommerceTeam\Commerce\Controller\StatisticModuleCompleteAggregationController::class,
                null,
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf:complete_aggregation'
            );
        }

        // Add default User TS config
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
            '
        options.saveDocNew {
            tx_commerce_products = 1
            tx_commerce_article_types = 1
            tx_commerce_attributes = 1
            tx_commerce_attribute_values = 1
            tx_commerce_categories = 1
            tx_commerce_trackingcodes = 1
            tx_commerce_moveordermails = 1
        }
            '
        );

        // Add default page TS config
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '
        # CONFIGURATION of RTE in table "tx_commerce_products", field "description"
        RTE.config.tx_commerce_products.description {
            hidePStyleItems = H1, H4, H5, H6
            proc.exitHTMLparser_db = 1
            proc.exitHTMLparser_db {
                keepNonMatchedTags = 1
                tags.font.allowedAttribs = color
                tags.font.rmTagIfNoAttrib = 1
                tags.font.nesting = global
            }
        }

        # CONFIGURATION of RTE in table "tx_commerce_articles", field "description_extra"
        RTE.config.tx_commerce_articles.description_extra < RTE.config.tx_commerce_products.description
            '
        );
    }

    // Add context menu for category trees in BE
    $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
        'name' => \CommerceTeam\Commerce\Utility\ClickmenuUtility::class,
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_categories');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_products');
}, 'commerce');
