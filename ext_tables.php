<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    COMMERCE_EXTKEY,
    'Configuration/TypoScript/',
    'COMMERCE'
);

if (TYPO3_MODE == 'BE') {
    /*
     * WIZICON
     * Default PageTS
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . COMMERCE_EXTKEY . '/Configuration/PageTS/ModWizards.ts">'
    );

    if (!isset($TBE_MODULES['commerce'])) {
        $tbeModules = array();
        foreach ($TBE_MODULES as $key => $val) {
            $tbeModules[$key] = $val;
            if ($key == 'file') {
                $tbeModules['commerce'] = 'category';
            }
        }
        $TBE_MODULES = $tbeModules;
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3skin')) {
        $presetSkinImgs = is_array($GLOBALS['TBE_STYLES']['skinImg']) ? $GLOBALS['TBE_STYLES']['skinImg'] : array();

        $GLOBALS['TBE_STYLES']['skinImg'] = array_merge($presetSkinImgs, array(
            'MOD:commerce_permission/../../../Resources/Public/Icons/mod_access.gif' => array(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . 'icons/module_web_perms.png',
                'width="24" height="24"',
            ),
        ));
    }


    // add main module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'commerce',
        '',
        'after:file',
        PATH_TXCOMMERCE . 'Modules/Main/'
    );

    // add category module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'commerce',
        'category',
        '',
        PATH_TXCOMMERCE . 'Modules/Category/'
    );

    // Access Module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'commerce',
        'permission',
        '',
        PATH_TXCOMMERCE . 'Modules/Permission/'
    );

    // Orders module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'commerce',
        'orders',
        '',
        PATH_TXCOMMERCE . 'Modules/Orders/'
    );

    // Statistic Module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'commerce',
        'statistic',
        '',
        PATH_TXCOMMERCE . 'Modules/Statistic/'
    );

    // Systemdata module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'commerce',
        'systemdata',
        '',
        PATH_TXCOMMERCE . 'Modules/Systemdata/'
    );


    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'CommerceTeam_Commerce_CategoryViewHelper::ajaxExpandCollapseWithoutProduct',
        'CommerceTeam\\Commerce\\Controller\\CategoryNavigationFrameController->ajaxExpandCollapseWithoutProduct'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'CommerceTeam_Commerce_CategoryViewHelper::ajaxExpandCollapse',
        'CommerceTeam\\Commerce\\Controller\\CategoryNavigationFrameController->ajaxExpandCollapse'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'CommerceTeam_Commerce_PermissionAjaxController::dispatch',
        'CommerceTeam\\Commerce\\Controller\\PermissionAjaxController->dispatch'
    );


    // commerce icon
    \TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon(
        'pages',
        'contains-commerce',
        PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/commerce_folder.gif'
    );


    // Add default User TS config
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
        options.saveDocNew {
            tx_commerce_products = 1
            tx_commerce_article_types = 1
            tx_commerce_attributes = 1
            tx_commerce_attribute_values = 1
            tx_commerce_categories = 1
            tx_commerce_trackingcodes = 1
            tx_commerce_moveordermails = 1
        }
    ');

    // Add default page TS config
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
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
}

// Add context menu for category trees in BE
$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
    'name' => 'CommerceTeam\\Commerce\\Utility\\ClickmenuUtility',
    'path' => PATH_TXCOMMERCE . 'Classes/Utility/ClickmenuUtility.php',
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_categories');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_products');
