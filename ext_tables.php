<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(COMMERCE_EXTKEY, 'Configuration/TypoScript/', 'COMMERCE');

if (TYPO3_MODE == 'BE') {
	/**
	 * WIZICON
	 * Default PageTS
	 */
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
		'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . COMMERCE_EXTKEY . '/Configuration/PageTS/ModWizards.ts">'
	);

		// add module after 'File'
	if (!isset($TBE_MODULES['txcommerceM1'])) {
		$tbeModules = array();
		foreach ($TBE_MODULES as $key => $val) {
			if ($key == 'file') {
				$tbeModules[$key] = $val;
				$tbeModules['txcommerceM1'] = $val;
			} else {
				$tbeModules[$key] = $val;
			}
		}
		$TBE_MODULES = $tbeModules;
	}

	if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3skin')) {
		$presetSkinImgs = is_array($GLOBALS['TBE_STYLES']['skinImg']) ? $GLOBALS['TBE_STYLES']['skinImg'] : array();

		$GLOBALS['TBE_STYLES']['skinImg'] = array_merge($presetSkinImgs, array(
			'MOD:txcommerceM1_permission/../../../Resources/Public/Icons/mod_access.gif' =>
				array(
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . 'icons/module_web_perms.png',
					'width="24" height="24"'
				),
		));
	}

	// add main module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Main/'
	);

	// add category module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'category',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Category/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
		'Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapseWithoutProduct',
		'Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper->ajaxExpandCollapseWithoutProduct'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
		'Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapse',
		'Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper->ajaxExpandCollapse'
	);

	// Access Module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'permission',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Permission/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
		'Tx_Commerce_Controller_PermissionAjaxController::dispatch',
		'Tx_Commerce_Controller_PermissionAjaxController->dispatch'
	);

	// Orders module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'orders',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Orders/'
	);

	// Statistic Module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'statistic',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Statistic/'
	);

	// Systemdata module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'systemdata',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Systemdata/'
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
	'name' => 'Tx_Commerce_Utility_ClickmenuUtility',
	'path' => PATH_TXCOMMERCE . 'Classes/Utility/ClickmenuUtility.php'
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_categories');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_commerce_products');