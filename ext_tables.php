<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(COMMERCE_EXTKEY, 'Configuration/TypoScript/', 'COMMERCE');


// mountpoints field in be_groups, be_users
$GLOBALS['T3_VAR']['ext'][COMMERCE_EXTKEY]['TCA']['mountpoints_config'] = array(
	// a special format is stored - that's why 'passthrough'
	// see: flag TCEFormsSelect_prefixTreeName
	// see: tx_dam_treelib_tceforms::getMountsForTree()
	'type' => 'passthrough',
	'form_type' => 'user',
	'userFunc' => 'Tx_Commerce_ViewHelpers_TceFunc->getSingleField_selectCategories',
	'treeViewBrowseable' => TRUE,
	'size' => 10,
	'autoSizeMax' => 30,
	'minitems' => 0,
	'maxitems' => 20,
);

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
			'MOD:txcommerceM1_access/../../../Resources/Public/Icons/mod_access.gif' =>
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
	// Access Module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'txcommerceM1',
		'access',
		'',
		PATH_TXCOMMERCE . 'Classes/Module/Access/'
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
		'EXT:commerce/Resources/Public/Icons/Table/commerce_folder.gif'
	);


	$TCA['pages']['columns']['module']['config']['items'][] = array(
		'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:commerce',
		'commerce'
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
			proc.exitHTMLparser_db=1
			proc.exitHTMLparser_db {
				keepNonMatchedTags=1
				tags.font.allowedAttribs= color
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


$tempColumns = array(
	'surname' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.surname',
		'config' => array(
			'type' => 'input',
			'size' => '40',
			'max' => '50',
		)
	),
	'tx_commerce_default_values' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_default_values',
		'config' => array(
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => array(
				'upper' => '1000',
				'lower' => '10'
			),
			'default' => 0
		)
	),
	'tx_commerce_fe_user_id' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_fe_user_id',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'fe_users',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_address_type_id' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_address_type_id',
		'config' => array(
			'type' => 'select',
			'item' => array(
				array('', 0),
			),
			'foreign_table' => 'tx_commerce_address_types',
			'foreign_table_where' => 'AND tx_commerce_address_types.pid=0',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_is_main_address' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_is_main_address',
		'config' => array(
			'type' => 'check',
		),
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_address', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_address', 'tx_commerce_default_values,tx_commerce_fe_user_id, tx_commerce_address_type_id, surname,tx_commerce_is_main_address');

/**
 * Put surename directly to name
 */
$ttaddressparts = explode('name,', $GLOBALS['TCA']['tt_address']['interface']['showRecordFieldList']);
$countto = count($ttaddressparts) - 1;
for ($i = 0; $i < $countto; ++$i) {
	if (strlen($ttaddressparts[$i]) == 0 || substr($ttaddressparts[$i], -1, 1) == ',') {
		$ttaddressparts[$i] = $ttaddressparts[$i] . 'name,surname,';
	} else {
		$ttaddressparts[$i] = $ttaddressparts[$i] . 'name,';
	}
}
$GLOBALS['TCA']['tt_address']['interface']['showRecordFieldList'] = implode('', $ttaddressparts);


$tempColumns = array(
	'tx_commerce_user_state_id' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce_user_state_id',
		'config' => array(
			'type' => 'select',
			'item' => array(
				array('', 0),
			),
			'foreign_table' => 'tx_commerce_user_states',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_tt_address_id' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce_tt_address_id',
		'config' => array(
			'type' => 'select',
			'foreign_table' => 'tt_address',
			'foreign_table_where' => 'AND tt_address.tx_commerce_fe_user_id=###THIS_UID###' .
			' AND tt_address.tx_commerce_fe_user_id!=0 AND tt_address.pid = ' .
				(int) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']['create_address_pid'],
			'minitems' => 0,
			'maxitems' => 1,
			'wizards' => array(
				'_PADDING' => 1,
				'_VERTICAL' => 1,
				'edit' => array(
					'type' => 'popup',
					'notNewRecords' => TRUE,
					'title' => 'Edit',
					'script' => 'wizard_edit.php',
					'popup_onlyOpenIfSelected' => 1,
					'icon' => 'edit2.gif',
					'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
				)
			)
		)
	),
	'tx_commerce_orders' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce_feuser_orders',
		'config' => array(
			'type' => 'user',
			'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->feUserOrders',
		)
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'fe_users',
	'--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:fe_users.tx_commerce,
		tx_commerce_tt_address_id,tx_commerce_user_state_id,tx_commerce_orders'
);


$tempColumns = array(
	'tx_commerce_foldereditorder' => array(
		'displayCond' => 'FIELD:tx_graytree_foldername:REQ:true',
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_pages.tx_commerce_foldereditorder',
		'config' => array(
			'type' => 'check',
			'default' => '0'
		)
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_commerce_foldereditorder;;;;1-1-1');


	// extend beusers/begroups for access control
$tempColumns = array(
	'tx_commerce_mountpoints' => array(
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:label.tx_commerce_mountpoints',
		'config' => $GLOBALS['T3_VAR']['ext'][COMMERCE_EXTKEY]['TCA']['mountpoints_config'],
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_commerce_mountpoints', '', 'after:file_mountpoints');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_commerce_mountpoints', '', 'after:fileoper_perms');

unset($tempColumns);


$GLOBALS['TCA']['tx_commerce_articles'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'sortby' => 'sorting',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY sorting,crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Articles.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'article.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime,
			fe_group, title, subtitle, navtitle, images, ordernumber, eancode, description_extra,plain_text,
			price_gross, price_net, purchase_price, tax, article_type_uid, products_uid, article_attributes',
	)
);

$GLOBALS['TCA']['tx_commerce_categories'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories',
		'label' => 'title',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Categories.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'categories.gif',
		'dividers2tabs' => '1',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title,
			subtitle, description, images, navtitle, keywords, attributes, parent_category, teaser, teaserimages',
	)
);

$GLOBALS['TCA']['tx_commerce_products'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'sortby' => 'sorting',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'versioningWS' => TRUE,
		'delete' => 'deleted',
		'thumbnail' => 'images',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Products.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'products.gif',
		'dividers2tabs' => '1',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
			title, subtitle, navtitle, description, images, teaser, teaserimages, categories, manufacturer_uid, attributes',
	)
);
