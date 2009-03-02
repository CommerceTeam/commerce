<?php
/**
 * $Id: ext_tables.php 566 2007-03-02 15:57:16Z ingo $
 */
 
 
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");


t3lib_extMgm::addStaticFile($_EXTKEY,'static/','COMMERCE');


// mountpoints field in be_groups, be_users
$GLOBALS['T3_VAR']['ext'][COMMERCE_EXTkey]['TCA']['mountpoints_config'] = array (
		// a special format is stored - that's why 'passthrough'
		// see: flag TCEFormsSelect_prefixTreeName
		// see: tx_dam_treelib_tceforms::getMountsForTree()
	'type' => 'passthrough',
	'form_type' => 'user',
	'userFunc' => 'EXT:'.COMMERCE_EXTkey.'/treelib/class.tx_commerce_tcefunc.php:&tx_commerce_tceFunc->getSingleField_selectCategories',

	'treeViewBrowseable' => true,
	'size' => 10,
	'autoSizeMax' => 30,
	'minitems' => 0,
	'maxitems' => 20,
);

###UNCOMMENTED - THIS IS THE ONLY LINES WHERE THIS IS USED IN THE WHOLE EXTENSION - IF IT WORKS WITHOUT IT, DELETE###
/*$GLOBALS['T3_VAR']['ext'][COMMERCE_EXTkey]['TCA']['category_config'] =
		Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_categories',
				'form_type' => 'user',
				'userFunc' => 'tx_graytree_tceFunc->getSingleField_selectTree',
				'treeView' => 1,
				'treeClass' => 'tx_commerce_tceFunc_categoryTree',
				'size' => 7,
				'autoSizeMax' => 10,
				'minitems' => 0,
				'maxitems' => 100,
		);
		
$GLOBALS['T3_VAR']['ext'][COMMERCE_EXTkey]['TCA']['category_field'] =
		Array (
			'label' => 'LLL:EXT:'.COMMERCE_EXTkey.'/locallang_db.php:tx_commerce_item.category',
			'config' => $GLOBALS['T3_VAR']['ext'][COMMERCE_EXTkey]['TCA']['category_config'],
		);
*/


if (TYPO3_MODE=='BE')	{

		// add module after 'File'
	if (!isset($TBE_MODULES['txcommerceM1']))	{
		$temp_TBE_MODULES = array();
		foreach($TBE_MODULES as $key => $val) {
			if ($key=='file') {
				$temp_TBE_MODULES[$key] = $val;
				$temp_TBE_MODULES['txcommerceM1'] = $val;
			} else {
				$temp_TBE_MODULES[$key] = $val;
			}
		}
		$TBE_MODULES = $temp_TBE_MODULES;
		
	}

		// add main module
	t3lib_extMgm::addModule('txcommerceM1','','',t3lib_extmgm::extPath('commerce').'mod_main/');
		// add category module
	t3lib_extMgm::addModule('txcommerceM1','category','',t3lib_extmgm::extPath('commerce').'mod_category/');
	//Access Module
	t3lib_extMgm::addModule('txcommerceM1','access','',t3lib_extmgm::extPath('commerce').'mod_access/');
	//Performance Module
	//t3lib_extMgm::addModule('txcommerceM1','performance','',t3lib_extmgm::extPath('commerce').'mod_perftest/');
	// Orders module
	t3lib_extMgm::addModule('txcommerceM1','orders','',t3lib_extmgm::extPath('commerce').'mod_orders/');

	// Systemdata module
	t3lib_extMgm::addModule('txcommerceM1','systemdata','',t3lib_extmgm::extPath('commerce').'mod_systemdata/');

	// Tracking module
	// t3lib_extMgm::addModule('txcommerceM1','tracking','',t3lib_extmgm::extPath('commerce').'mod_tracking/');

	// Statistic Module
	t3lib_extMgm::addModule('txcommerceM1','statistic','',t3lib_extMgm::extPath('commerce').'mod_statistic/');

	// commerce icon
	$ICON_TYPES['commerce'] = Array('icon' => PATH_txcommerce_icon_table_rel.'commerce_folder.gif');
	
	$TCA['pages']['columns']['module']['config']['items'][] = Array('LLL:EXT:commerce/locallang_be.xml:commerce', 'commerce');	

	
	include_once(t3lib_extMgm::extPath('commerce').'lib/class.tx_commerce_article_price.php');
}




$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceCategoryTree'] = 'EXT:'.COMMERCE_EXTkey.'/lib/class.tx_commerce_treecategory.php:&tx_commerce_treeCategory';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceCategory'] = 'EXT:'.COMMERCE_EXTkey.'/lib/class.tx_commerce_leafcategoryview.php:&tx_commerce_leafCategoryView';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceProduct'] = 'EXT:'.COMMERCE_EXTkey.'/lib/class.tx_commerce_leafproductview.php:&tx_commerce_leafProductView';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceAttribute'] = 'EXT:'.COMMERCE_EXTkey.'/lib/class.tx_commerce_leafattributeview.php:&tx_commerce_leafAttributeView';
 

// add context menu
$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
	'name' => 'tx_commerce_clickmenu',
	'path' => PATH_txcommerce.'mod_clickmenu/commerce_clickmenu.php'
);

t3lib_extMgm::addToInsertRecords('tx_commerce_categories');
t3lib_extMgm::addToInsertRecords('tx_commerce_products');

$tempColumns = Array (
	'surname' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:tt_address.surname',
		'config' => Array (
			'type' => 'input',
			'size' => '40',
			'max' => '50',
			
			
		)
	),
	'tx_commerce_default_values' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:tt_address.tx_commerce_default_values',
		'config' => Array (
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => Array (
				'upper' => '1000',
				'lower' => '10'
			),
			'default' => 0
		)
	),
	'tx_commerce_fe_user_id' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:tt_address.tx_commerce_fe_user_id',
		'config' => Array (
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'fe_users',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_address_type_id' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:tt_address.tx_commerce_address_type_id',
		'config' => Array (
			'type' => 'select',
			'item' => Array (
				Array('', 0),
			),
			'foreign_table' => 'tx_commerce_address_types',
			'foreign_table_where' => 'AND tx_commerce_address_types.pid=0',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_is_main_address' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:tt_address.tx_commerce_is_main_address',
		'config' => array (
			'type' => 'check',
		),
	),
);


t3lib_div::loadTCA('tt_address');
/**
 * Put surename directly to name
 */
 
$ttaddressparts = explode("name,",$TCA['tt_address']['interface']['showRecordFieldList']);
$partnums = count($ttaddressparts);
$countto = $partnums - 1;
for($i=0;$i < $countto ; ++$i) {
	    if (strlen($ttaddressparts[$i])==0 OR substr($ttaddressparts[$i], -1, 1)== ','){
               $ttaddressparts[$i] = $ttaddressparts[$i] . 'name,surname,';
      } else {
              $ttaddressparts[$i] = $ttaddressparts[$i] . 'name,';    
       }
}
$TCA['tt_address']['interface']['showRecordFieldList'] = implode('',$ttaddressparts);
t3lib_extMgm::addTCAcolumns('tt_address',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_address','tx_commerce_default_values;;;;1-1-1,tx_commerce_fe_user_id, tx_commerce_address_type_id, surname,tx_commerce_is_main_address');

$tempColumns = Array (
	'tx_commerce_user_state_id' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:fe_users.tx_commerce_user_state_id',
		'config' => Array (
			'type' => 'select',
			'item' => Array (
				Array('', 0),
			),
			
			'foreign_table' => 'tx_commerce_user_states',
#			'foreign_table_where' => 'AND tx_commerce_address_types.pid=0',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
        'tx_commerce_tt_address_id' => Array (
                'exclude' => 1,
                'label' => 'LLL:EXT:commerce/locallang_db.xml:fe_users.tx_commerce_tt_address_id',
                'config' => Array (
                    'type' => 'select',
                    'foreign_table' => 'tt_address',
                    'foreign_table_where' => 'AND tt_address.tx_commerce_fe_user_id=###THIS_UID###',
                        'minitems' => 0,
                    'maxitems' => 1,
                    'wizards' => Array(
	                    '_PADDING' => 1,
	                    '_VERTICAL' => 1,
	                    'edit' => Array(
	                            'type' => 'popup',
	                            'notNewRecords' => true,
	                            'title' => 'Edit',
	                            'script' => 'wizard_edit.php',
	                            'popup_onlyOpenIfSelected' => 1,
	                            'icon' => 'edit2.gif',
	                            'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
	                    )
	            )
		)
	),
	'tx_commerce_orders' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:fe_users.tx_commerce_feuser_orders',
		
			'config' => Array (
				'type' => 'user',
				'userFunc' => 'user_orderedit_func->fe_user_orders',
			)
			
		
	),
);																																																																				    

t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users','tx_commerce_tt_address_id,tx_commerce_user_state_id,tx_commerce_orders;;;;1-1-1');

if (TYPO3_MODE=='BE') {
     require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'mod_orders/class.user_orderedit_func.php');
}


$tempColumns = Array (
	'tx_commerce_foldereditorder' => Array (
		'displayCond' => 'FIELD:tx_graytree_foldername:REQ:true',
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_pages.tx_commerce_foldereditorder',
		'config' => Array (
			'type' => 'check',
			'default' => '0'
		)
		
	),
);


t3lib_div::loadTCA('pages');


t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages','tx_commerce_foldereditorder;;;;1-1-1');


// extend beusers/begroups for access control
$tempColumns = array(
	'tx_commerce_mountpoints' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/locallang_db.xml:label.tx_commerce_mountpoints',
		'config' => $GLOBALS['T3_VAR']['ext'][COMMERCE_EXTkey]['TCA']['mountpoints_config'],
	),
);

t3lib_div::loadTCA('be_groups');
t3lib_extMgm::addTCAcolumns('be_groups',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_groups','tx_commerce_mountpoints','','after:file_mountpoints');

t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_users','tx_commerce_mountpoints','','after:fileoper_perms');

unset($tempColumns);
	

$TCA['tx_commerce_products'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_products',
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
		//'shadowColumnsForNewPlaceholders' => 'categories, subtitle',
		'delete' => 'deleted',
		'thumbnail' => 'images',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_products.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'products.gif',
		'dividers2tabs' => '1',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, subtitle, navtitle, description, images, teaser, teaserimages, categories, manufacturer_uid, attributes',
	)
);


$TCA['tx_commerce_article_types'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_article_types',
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
		'enablecolumns' => Array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_articles.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'icon_tx_commerce_article_types.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, title',
	)
);

$TCA['tx_commerce_articles'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles',
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
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_articles.tca.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_txcommerce_icon_table_rel.'article.gif',		
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, subtitle, navtitle, images, ordernumber, eancode, description_extra,plain_text, price_gross, price_net, purchase_price, tax, article_type_uid, products_uid, article_attributes',
	)
);

$TCA['tx_commerce_article_prices'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_article_prices',
		'label' => 'price_net',
		/**
		 * @TODO Extension Config for choosing price to display
		 */
		'label_alt' => 'price_net,price_gross,purchase_price',
		'label_alt_force' => 1,
		'label_userFunc' => 'tx_commerce_article_price->getTCARecordTitle',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_articles.tca.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_txcommerce_icon_table_rel.'price.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, price_gross, price_net, price_scale_amount, purchase_price',
	)
);

$TCA['tx_commerce_baskets'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_baskets.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'baskets.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sid, finished_time, article_id,price_id, price_gross, price_net, quantity',
	)
);

$TCA['tx_commerce_attributes'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_attributes',
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
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_attributes.tca.php',
		'typeicon_column' => 'has_valuelist',
		'typeicons' => array('0' => PATH_txcommerce_icon_table_rel.'attributes_free.gif',
							 '1' => PATH_txcommerce_icon_table_rel.'attributes_list.gif'), 
		'iconfile' => PATH_txcommerce_icon_table_rel.'attributes_free.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, has_valuelist, title, internal_title, unit, valueformat, valuelist',
	)
);

$TCA['tx_commerce_attribute_values'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_attribute_values',
		'label' => 'value',
		'label_alt' =>'attributes_uid',
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
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_attribute_values.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'attribute_value.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, value, showvalue, attributes_uid',
	)
);

$TCA['tx_commerce_categories'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories',
		'label' => 'title',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
// 		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_categories.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'categories.gif',
		'dividers2tabs' => '1',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, subtitle, description, images, navtitle, keywords, attributes, parent_category, teaser, teaserimages',
	)
);

$TCA['tx_commerce_trackingcodes'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_trackingcodes',
		'label' => 'description',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_tracking.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'tracking_codes.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title, description',
	)
);



$TCA['tx_commerce_order_types'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_order_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_orders.tca.php',
		'iconfile' =>  PATH_txcommerce_icon_table_rel.'order_types.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	)
);

$TCA['tx_commerce_tracking'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_tracking',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_tracking.tca.php',
		'iconfile' =>  PATH_txcommerce_icon_table_rel.'tracking.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'orders_uid, trackingcodes_uid, msg',
	)
);
/**
 * Changed to Singlequotes
 */

$TCA['tx_commerce_orders'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders',
		'label' => 'order_id',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'dividers2tabs' => '1',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_orders.tca.php',
		'iconfile' => t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice, paymenttype, sum_price_net, sum_price_gross,pid,cu_iso_3_uid,order_sys_language_uid,pricefromnet',
	)
);

$TCA['tx_commerce_order_articles'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_order_articles',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' =>t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_order_articles.tca.php',
		'iconfile' =>  PATH_txcommerce_icon_table_rel.'order_articles.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'article_type_uid, article_uid, article_number, title, subtitle, price_net, price_gross, tax, amount, order_id',
	)
);




$TCA['tx_commerce_address_types'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_address_types',
		'label' => 'title',
		'readOnly' => 1,
		'adminOnly' => 1,
		'rootLevel' => 1,
		'is_static' => 1,
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tca.php',
		'iconfile' =>  PATH_txcommerce_icon_table_rel.'address_types.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	)
);


$TCA['tx_commerce_user_states'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_user_states',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tca.php',
		'iconfile' =>  PATH_txcommerce_icon_table_rel.'user_states.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	)
);

$TCA['tx_commerce_moveordermails'] = Array (
    'ctrl' => Array (
        'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails',        
        'label' => 'name',    
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',    
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'enablecolumns' => Array (        
            'disabled' => 'hidden',    
            'starttime' => 'starttime',    
            'endtime' => 'endtime',    
            'fe_group' => 'fe_group',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_moveordermails.tca.php',
        'iconfile' => PATH_txcommerce_icon_table_rel.'moveordermails.gif',
    ),
    'feInterface' => Array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, name, mailkind, mailtemplate, htmltemplate, mailcharset, sendername, senderemail,otherreceiver,BCC',
    )
);

$TCA['tx_commerce_salesfigures'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_salesfigures',		
		'label' => 'uid',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly'	=> '1',
		'adminOnly'	=> '1',	
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_salesfigures.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'salesfigures.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'year, month, day, dow, hour, pricegross, pricenet, amount, orders',
	)
);

$TCA['tx_commerce_newclients'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_newclients',		
		'label' => 'uid',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly'	=> '1',
		'adminOnly'	=> '1',	
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_newclients.tca.php',
		'iconfile' => PATH_txcommerce_icon_table_rel.'newclients.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'year, month, day, dow, hour, registration',
	)
);

$TCA['tx_commerce_manufacturer'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_manufacturer',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY title,uid',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_manufacturer.tca.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_txcommerce_icon_table_rel.'manufacturer.gif',		
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo',
	)
);

$TCA['tx_commerce_supplier'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_supplier',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY title,uid',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath(COMMERCE_EXTkey).'tcafiles/tx_commerce_supplier.tca.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_txcommerce_icon_table_rel.'supplier.gif',		
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo',
	)
);



/*
 * Definition Plugins
 */

t3lib_div::loadTCA('tt_content');

/* ################# PI1 (product listing) ##################### */
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';


t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/locallang_be.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY .'_pi1', 'FILE:EXT:commerce/pi1/flexform_product.xml');


/* ################# PI2 (basket) ##################### */
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/locallang_be.php:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');


/* ################# PI3 (checkout) ##################### */
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY .'_pi3'] = 'layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/locallang_be.php:tt_content.list_type_pi3', $_EXTKEY .'_pi3'), 'list_type');


/* ################# PI4 (addresses) ##################### */
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY .'_pi4'] = 'layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/locallang_be.php:tt_content.list_type_pi4', $_EXTKEY .'_pi4'), 'list_type');

/* ################# PI5 (checkout - old) ##################### */
#$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY .'_pi5'] = 'layout,select_key,pages';
#t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/locallang_be.php:tt_content.list_type_pi5', $_EXTKEY .'_pi5'), 'list_type');

/* ################ PI6 (invoice)############################*/

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi6']='layout,select_key,pages';
t3lib_extMgm::addPlugin(Array('LLL:EXT:commerce/locallang_be.php:tt_content.list_type_pi6', $_EXTKEY.'_pi6'),'list_type');


/*  WIZZICON */

if (TYPO3_MODE=='BE') {
  $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_commerce_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_commerce_pi1_wizicon.php';
  $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_commerce_pi2_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi2/class.tx_commerce_pi2_wizicon.php';
  $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_commerce_pi3_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi3/class.tx_commerce_pi3_wizicon.php';
  $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_commerce_pi4_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi4/class.tx_commerce_pi4_wizicon.php';
  $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_commerce_pi6_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi6/class.tx_commerce_pi6_wizicon.php';
}
?>