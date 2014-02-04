<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

t3lib_extMgm::addStaticFile(COMMERCE_EXTKEY, 'Configuration/TypoScript/', 'COMMERCE');


	// mountpoints field in be_groups, be_users
$GLOBALS['T3_VAR']['ext'][COMMERCE_EXTKEY]['TCA']['mountpoints_config'] = array(
		// a special format is stored - that's why 'passthrough'
		// see: flag TCEFormsSelect_prefixTreeName
		// see: tx_dam_treelib_tceforms::getMountsForTree()
	'type' => 'passthrough',
	'form_type' => 'user',
	'userFunc' => 'EXT:commerce/treelib/class.tx_commerce_tcefunc.php:&tx_commerce_tceFunc->getSingleField_selectCategories',
	'treeViewBrowseable' => TRUE,
	'size' => 10,
	'autoSizeMax' => 30,
	'minitems' => 0,
	'maxitems' => 20,
);


/**
 * Definition Plugins
 */
t3lib_div::loadTCA('tt_content');

/* ################# PI1 (product listing) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi1'] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][COMMERCE_EXTKEY . '_pi1'] = 'pi_flexform';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi1', COMMERCE_EXTKEY . '_pi1'), 'list_type');
t3lib_extMgm::addPiFlexFormValue(COMMERCE_EXTKEY . '_pi1', 'FILE:EXT:commerce/Configuration/FlexForms/flexform_pi1.xml');

/* ################# PI2 (basket) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi2'] = 'layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi2', COMMERCE_EXTKEY . '_pi2'), 'list_type');

/* ################# PI3 (checkout) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi3'] = 'layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi3', COMMERCE_EXTKEY . '_pi3'), 'list_type');

/* ################# PI4 (addresses) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi4'] = 'layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi4', COMMERCE_EXTKEY . '_pi4'), 'list_type');

/* ################ PI6 (invoice) ############################*/
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi6'] = 'layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi6', COMMERCE_EXTKEY . '_pi6'), 'list_type');


if (TYPO3_MODE == 'BE') {
	/**
	 * WIZICON
	 * Default PageTS
	 */
	t3lib_extMgm::addPageTSConfig(
		'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . COMMERCE_EXTKEY . '/Configuration/PageTS/ModWizards.ts">'
	);

		// add module after 'File'
	if (!isset($TBE_MODULES['txcommerceM1'])) {
		$temp_TBE_MODULES = array();
		foreach ($TBE_MODULES as $key => $val) {
			if ($key == 'file') {
				$temp_TBE_MODULES[$key] = $val;
				$temp_TBE_MODULES['txcommerceM1'] = $val;
			} else {
				$temp_TBE_MODULES[$key] = $val;
			}
		}
		$TBE_MODULES = $temp_TBE_MODULES;
	}

		// add main module
	t3lib_extMgm::addModule('txcommerceM1', '', '', PATH_TXCOMMERCE . 'Classes/Module/Main/');
		// add category module
	t3lib_extMgm::addModule('txcommerceM1', 'category', '', PATH_TXCOMMERCE . 'Classes/Module/Category/');
		// Access Module
	t3lib_extMgm::addModule('txcommerceM1', 'access', '', PATH_TXCOMMERCE . 'Classes/Module/Access/');
		// Orders module
	t3lib_extMgm::addModule('txcommerceM1', 'orders', '', PATH_TXCOMMERCE . 'Classes/Module/Orders/');
		// Systemdata module
	t3lib_extMgm::addModule('txcommerceM1', 'systemdata', '', PATH_TXCOMMERCE . 'Classes/Module/Systemdata/');
		// Statistic Module
	t3lib_extMgm::addModule('txcommerceM1', 'statistic', '', PATH_TXCOMMERCE . 'Classes/Module/Statistic/');

		// commerce icon
	if (t3lib_div::int_from_ver(TYPO3_version) >= 4004000) {
		t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-commerce', '../typo3conf/ext/commerce/Resources/Public/Icons/Table/commerce_folder.gif');
	} else {
		$ICON_TYPES['commerce'] = '../typo3conf/ext/commerce/Resources/Public/Icons/Table/commerce_folder.gif';
	}

	$TCA['pages']['columns']['module']['config']['items'][] = array(
		'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:commerce',
		'commerce'
	);


		// Add default User TS config
	t3lib_extMgm::addUserTSConfig('
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
	t3lib_extMgm::addPageTSConfig('
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


$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['leafClasses']['txcommerceCategoryTree'] =
	'EXT:' . COMMERCE_EXTKEY . '/lib/class.tx_commerce_treecategory.php:&tx_commerce_treeCategory';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['leafClasses']['txcommerceCategory'] =
	'EXT:' . COMMERCE_EXTKEY . '/lib/class.tx_commerce_leafcategoryview.php:&tx_commerce_leafCategoryView';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['leafClasses']['txcommerceProduct'] =
	'EXT:' . COMMERCE_EXTKEY . '/lib/class.tx_commerce_leafproductview.php:&tx_commerce_leafProductView';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['leafClasses']['txcommerceAttribute'] =
	'EXT:' . COMMERCE_EXTKEY . '/lib/class.tx_commerce_leafattributeview.php:&tx_commerce_leafAttributeView';


	// Add context menu for category trees in BE
$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
	'name' => 'Tx_Commerce_Utility_ClickmenuUtility',
	'path' => PATH_TXCOMMERCE . 'Classes/Utility/ClickmenuUtility.php'
);

t3lib_extMgm::addToInsertRecords('tx_commerce_categories');
t3lib_extMgm::addToInsertRecords('tx_commerce_products');


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
				Array('', 0),
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

t3lib_div::loadTCA('tt_address');
t3lib_extMgm::addTCAcolumns('tt_address', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('tt_address', 'tx_commerce_default_values;;;;1-1-1,tx_commerce_fe_user_id, tx_commerce_address_type_id, surname,tx_commerce_is_main_address');

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
				Array('', 0),
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
				intval($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf']['create_address_pid']),
			'minitems' => 0,
			'maxitems' => 1,
			'wizards' => Array(
				'_PADDING' => 1,
				'_VERTICAL' => 1,
				'edit' => Array(
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
			'userFunc' => 'EXT:commerce/mod_orders/class.user_orderedit_func.php:user_orderedit_func->fe_user_orders',
		)
	),
);

t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_commerce_tt_address_id,tx_commerce_user_state_id,tx_commerce_orders;;;;1-1-1');


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

t3lib_div::loadTCA('pages');
t3lib_extMgm::addTCAcolumns('pages', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('pages', 'tx_commerce_foldereditorder;;;;1-1-1');


	// extend beusers/begroups for access control
$tempColumns = array(
	'tx_commerce_mountpoints' => array(
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:label.tx_commerce_mountpoints',
		'config' => $GLOBALS['T3_VAR']['ext'][COMMERCE_EXTKEY]['TCA']['mountpoints_config'],
	),
);

t3lib_div::loadTCA('be_groups');
t3lib_extMgm::addTCAcolumns('be_groups', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('be_groups', 'tx_commerce_mountpoints', '', 'after:file_mountpoints');

t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('be_users', 'tx_commerce_mountpoints', '', 'after:fileoper_perms');

unset($tempColumns);


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
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTKEY) . 'Configuration/TCA/Products.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'products.gif',
		'dividers2tabs' => '1',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, subtitle, navtitle, description, images, teaser, teaserimages, categories, manufacturer_uid, attributes',
	)
);

$GLOBALS['TCA']['tx_commerce_article_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'readOnly' => 1,
		'adminOnly' => 1,
		'rootLevel' => 1,
		'is_static' => 1,
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Articles.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'icon_tx_commerce_article_types.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, title',
	)
);

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
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, subtitle, navtitle, images, ordernumber, eancode, description_extra,plain_text, price_gross, price_net, purchase_price, tax, article_type_uid, products_uid, article_attributes',
	)
);

$GLOBALS['TCA']['tx_commerce_article_prices'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices',
		'label' => 'price_net',
		'label_alt' => 'price_net,price_gross,purchase_price',
		'label_alt_force' => 1,
		'label_userFunc' => 'EXT:commerce/lib/class.tx_commerce_article_price.php:tx_commerce_article_price->getTCARecordTitle',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Articles.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'price.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, price_gross, price_net, price_scale_amount, purchase_price',
	)
);

$GLOBALS['TCA']['tx_commerce_baskets'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Baskets.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'baskets.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sid, finished_time, article_id,price_id, price_gross, price_net, quantity',
	)
);

$GLOBALS['TCA']['tx_commerce_attributes'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes',
		'label' => 'internal_title',
		'label_alt' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'versioning' => '1',
		'dividers2tabs' => '1',
		'requestUpdate' => 'has_valuelist',
		'languageField' => 'sys_language_uid',
		'thumbnail' => 'icon',
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
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Attributes.php',
		'typeicon_column' => 'has_valuelist',
		'typeicons' => array(
			'0' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attributes_free.gif',
			'1' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attributes_list.gif'
		),
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attributes_free.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, has_valuelist, title, internal_title, unit, valueformat, valuelist',
	)
);

$GLOBALS['TCA']['tx_commerce_attribute_values'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attribute_values',
		'label' => 'value',
		'label_alt' => 'attributes_uid',
		'label_alt_force' => 1,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'thumbnail' => 'icon',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY attributes_uid,value',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/AttributeValues.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attribute_value.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, value, showvalue, attributes_uid',
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
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, subtitle, description, images, navtitle, keywords, attributes, parent_category, teaser, teaserimages',
	)
);

$GLOBALS['TCA']['tx_commerce_trackingcodes'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_trackingcodes',
		'label' => 'description',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Tracking.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'tracking_codes.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title, description',
	)
);

$GLOBALS['TCA']['tx_commerce_order_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Orders.php',
		'iconfile' =>  PATH_TXCOMMERCE_ICON_TABLE_REL . 'order_types.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	)
);

$GLOBALS['TCA']['tx_commerce_tracking'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Tracking.php',
		'iconfile' =>  PATH_TXCOMMERCE_ICON_TABLE_REL . 'tracking.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'orders_uid, trackingcodes_uid, msg',
	)
);

$GLOBALS['TCA']['tx_commerce_orders'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders',
		'label' => 'order_id',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'dividers2tabs' => '1',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Orders.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'orders.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice, paymenttype, sum_price_net, sum_price_gross,pid,cu_iso_3_uid,order_sys_language_uid,pricefromnet',
	)
);

$GLOBALS['TCA']['tx_commerce_order_articles'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/OrderArticles.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'order_articles.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'article_type_uid, article_uid, article_number, title, subtitle, price_net, price_gross, tax, amount, order_id',
	)
);

$GLOBALS['TCA']['tx_commerce_address_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types',
		'label' => 'title',
		'readOnly' => 1,
		'adminOnly' => 1,
		'rootLevel' => 1,
		'is_static' => 1,
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'tcafiles/tca.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'address_types.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	)
);

$GLOBALS['TCA']['tx_commerce_user_states'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_user_states',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'tcafiles/tca.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'user_states.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	)
);

$GLOBALS['TCA']['tx_commerce_moveordermails'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_moveordermails',
		'label' => 'name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Moveordermails.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'moveordermails.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, name, mailkind, mailtemplate, htmltemplate, mailcharset, sendername, senderemail,otherreceiver,BCC',
	)
);

$GLOBALS['TCA']['tx_commerce_salesfigures'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly' => '1',
		'adminOnly' => '1',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Salesfigures.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'salesfigures.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'year, month, day, dow, hour, pricegross, pricenet, amount, orders',
	)
);

$GLOBALS['TCA']['tx_commerce_newclients'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly' => '1',
		'adminOnly' => '1',
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Newclients.php',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'newclients.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'year, month, day, dow, hour, registration',
	)
);

$GLOBALS['TCA']['tx_commerce_manufacturer'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY title,uid',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Manufacturer.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'manufacturer.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo',
	)
);

$GLOBALS['TCA']['tx_commerce_supplier'] = Array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY title,uid',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/Supplier.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'supplier.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo',
	)
);

?>