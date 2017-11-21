<?php

call_user_func(function ($packageKey) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $packageKey,
        'Configuration/TypoScript/',
        'COMMERCE'
    );

    if (TYPO3_MODE == 'BE') {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
        // add mod wizard config
        <INCLUDE_TYPOSCRIPT: source="FILE:EXT:commerce/Configuration/PageTSconfig/NewContentElementWizard.ts">

        // register commerce link handlers
        TCEMAIN.linkHandler {
            commerce {
                handler = CommerceTeam\Commerce\LinkHandler\CommerceLinkHandler
                label = LLL:EXT:commerce/Resources/Private/Language/locallang_mod_main.xlf:mlang_tabs_tab
                displayAfter = page
                displayBefore = file
                scanAfter = page
            }
        }

        // Add default page TS config
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
        ');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
        // add mod wizard config
        <INCLUDE_TYPOSCRIPT: source="FILE:EXT:commerce/Configuration/UserTSconfig/ContextMenu.ts">
        
        // Add default User TS config
        options.saveDocNew {
            tx_commerce_products = 1
            tx_commerce_article_types = 1
            tx_commerce_attributes = 1
            tx_commerce_attribute_values = 1
            tx_commerce_categories = 1
            tx_commerce_moveordermails = 1
        }
        ');


        // add contains type to display commerce folders with custom icon
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-commerce'] =
            'apps-pagetree-folder-contains-commerce';

        // positioning of main module is not considered in the core
        // that's why we need to add our own main module
        if (!isset($GLOBALS['TBE_MODULES']['commerce'])) {
            $GLOBALS['TBE_MODULES'] = array_slice($GLOBALS['TBE_MODULES'], 0, 2, true) +
                ['commerce' => 'category'] +
                array_slice($GLOBALS['TBE_MODULES'], 2, count($GLOBALS['TBE_MODULES']) - 2, true);

            // Main module to house commerce modules
            $GLOBALS['TBE_MODULES']['_configuration']['commerce'] = [
                'access' => 'user,group',
                'name' => $packageKey,
                'iconIdentifier' => 'module-commerce',
                'labels' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_main.xlf',
                'workspaces' => 'online',
            ];
        }

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
                'iconIdentifier' => 'module-commerce-category',
                'labels' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_category.xlf',
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
                'icon' => 'EXT:commerce/Resources/Public/Icons/mod_access.svg',
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
                'iconIdentifier' => 'module-commerce-orders',
                'labels' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xlf',
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
                'routeTarget' => \CommerceTeam\Commerce\Controller\SystemdataModuleAttributeController::class .
                    '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_systemdata',
                'iconIdentifier' => 'module-commerce-systemdata',
                'labels' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf',
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
                'routeTarget' => \CommerceTeam\Commerce\Controller\StatisticModuleShowStatisticsController::class .
                    '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_statistic',
                'iconIdentifier' => 'module-commerce-statistic',
                'labels' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf',
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
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_categories');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_products');

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1489245663865] =
        \CommerceTeam\Commerce\ContextMenu\ItemProviders\CategoryProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1489245663866] =
        \CommerceTeam\Commerce\ContextMenu\ItemProviders\ProductProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1489245663867] =
        \CommerceTeam\Commerce\ContextMenu\ItemProviders\ArticleProvider::class;
}, 'commerce');
