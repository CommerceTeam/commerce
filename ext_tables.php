<?php

call_user_func(function ($packageKey) {
    $scOptions = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $packageKey,
        'Configuration/TypoScript/',
        'COMMERCE'
    );

    // register for RteHtmlParser::TS_links_rte and ContentObjectRenderer::resolveMixedLinkParameter
    $scOptions['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] =
        \CommerceTeam\Commerce\LinkHandler\CommerceLinkHandler::class;

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
            ['source' => 'EXT:commerce/Resources/Public/Icons/Table/commerce_globus.gif']
        );
        // icon to show for system folders with contains commerce selected
        $iconRegistry->registerIcon(
            'apps-pagetree-folder-contains-commerce',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:commerce/Resources/Public/Icons/Table/commerce_folder.gif']
        );
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-commerce'] =
            'apps-pagetree-folder-contains-commerce';

        // add commerce main module after file module
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
            'after:file',
            PATH_TXCOMMERCE . 'Modules/Main/',
            [
                'script' => '_DISPATCH',
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

        // Access Module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'permission',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\PermissionModuleController::class . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_permission',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_access.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:beuser/Resources/Public/Icons/module-permission.svg'
                    ],
                ],
                'navigationFrameModule' => 'CommerceTeam_commerce_CategoryNavigation',
            ]
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
                'navigationFrameModule' => 'CommerceTeam_commerce_OrderNavigation',
            ]
        );

        // Statistic Module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'statistic',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\StatisticModuleController::class . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_statistic',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_statistic.gif'
                    ],
                ],
                'navigationFrameModule' => 'CommerceTeam_commerce_OrderNavigation',
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_statistic',
            \CommerceTeam\Commerce\Controller\StatisticShowStatisticsModuleFunctionController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf:statistics'
        );

        if (\CommerceTeam\Commerce\Utility\ConfigurationUtility::getInstance()->getExtConf('allowAggregation') == 1) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
                'commerce_statistic',
                \CommerceTeam\Commerce\Controller\StatisticIncrementalAggregationModuleFunctionController::class,
                null,
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf:incremental_aggregation'
            );

            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
                'commerce_statistic',
                \CommerceTeam\Commerce\Controller\StatisticCompleteAggregationModuleFunctionController::class,
                null,
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xlf:complete_aggregation'
            );
        }

        // Systemdata module
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            $packageKey,
            'systemdata',
            '',
            '',
            [
                'routeTarget' => \CommerceTeam\Commerce\Controller\SystemdataModuleController::class . '::mainAction',
                'access' => 'user,group',
                'name' => 'commerce_systemdata',
                'labels' => [
                    'll_ref' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf',
                    'tabs_images' => [
                        'tab' => 'EXT:commerce/Resources/Public/Icons/mod_systemdata.gif'
                    ],
                ],
                'navigationFrameModule' => 'CommerceTeam_commerce_SystemdataNavigation',
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_systemdata',
            \CommerceTeam\Commerce\Controller\SystemdataAttributesModuleFunctionController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_attributes'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_systemdata',
            \CommerceTeam\Commerce\Controller\SystemdataManufacturerModuleFunctionController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_manufacturer'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'commerce_systemdata',
            \CommerceTeam\Commerce\Controller\SystemdataSupplierModuleFunctionController::class,
            null,
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_supplier'
        );


        // @todo obsolete?
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
            'CommerceTeam_Commerce_PermissionAjaxController::dispatch',
            \CommerceTeam\Commerce\Controller\PermissionAjaxController::class . '->dispatch'
        );

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
    $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
        'name' => \CommerceTeam\Commerce\Utility\ClickmenuUtility::class,
        'path' => PATH_TXCOMMERCE . 'Classes/Utility/ClickmenuUtility.php',
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_categories');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_products');
}, 'commerce');
