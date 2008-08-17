<?php
/**
 * $Id: ext_localconf.php 562 2007-03-02 10:16:12Z ingo $
 */


if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (!defined ('COMMERCE_EXTkey')) {
	define('COMMERCE_EXTkey',$_EXTKEY);
}

if (!defined ('PATH_txcommerce')) {
	define('PATH_txcommerce', t3lib_extMgm::extPath(COMMERCE_EXTkey));
}
if (!defined ('PATH_txcommerce_rel')) {
	define('PATH_txcommerce_rel', t3lib_extMgm::extRelPath(COMMERCE_EXTkey));
}
if (!defined ('PATH_txcommerce_icon_table_rel')) {
	define('PATH_txcommerce_icon_table_rel', PATH_txcommerce_rel.'res/icons/table/');
}
if (!defined ('PATH_txcommerce_icon_tree_rel')) {
	define('PATH_txcommerce_icon_tree_rel', PATH_txcommerce_rel.'res/icons/table/');
}
if (!defined ('PATH_txgraytree')) {
	define('PATH_txgraytree', t3lib_extMgm::extPath('graytree'));
}
if (!defined ('PATH_txgraytree_rel')) {
	define('PATH_txgraytree_rel', t3lib_extMgm::extRelPath(GRAYTREE_EXTkey));
}

if (t3lib_div::int_from_ver(phpversion()) < 4004000) {
        define(PHP_INT_MAX,9999999);
}


// Einfuegen der SonderArtikel
define(NORMALArticleType,1);
define(PAYMENTArticleType,2);
define(DELIVERYArticleType,3);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT'] = array(
	'tablefields' => array (
		'title' => 'SYSTEMPRODUCT_PAYMENT',
		'description' => 'product zum Verwalten der Bezahlung',
	)
);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['invoice'] = array (
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_invoice.php',
	'class' => 'tx_commerce_payment_invoice',
	'type'=>PAYMENTArticleType,
);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['prepayment'] = array (
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_prepayment.php',
	'class' => 'tx_commerce_payment_prepayment',
	'type'=>PAYMENTArticleType,
);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['creditcard'] = array (
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_creditcard.php',
	'class' => 'tx_commerce_payment_creditcard',
	'type'=>PAYMENTArticleType,
);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['cashondelivery'] = array (
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_cashondelivery.php',
	'class' => 'tx_commerce_payment_cashondelivery',
	'type'=>PAYMENTArticleType,
);




$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['DELIVERY'] = array(
	'tablefields' => array (
		'title' => 'SYSTEMPRODUCT_DELIVERY',
		'description' => 'product zum Verwalten der Lieferarten',
	)
);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['DELIVERY']['types'] = array(
		'sysdelivery' => array ('type'=>DELIVERYArticleType),
		#'POST Europa' => array (),
		#'UPS Weltweit' => array (
		#	'finishingFunction' => 'script->funktion'
		#),
	);





/*
 * Inclusion for Frontend Plugins
 *
 */


// add Test PI for abstract classes
/**
 *
 * @TODO: Herausnehmen des Test PI
 */

t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi1/class.tx_commerce_pi1.php', '_pi1', 'list_type', 1);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi2/class.tx_commerce_pi2.php', '_pi2', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi3/class.tx_commerce_pi3.php', '_pi3', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi4/class.tx_commerce_pi4.php', '_pi4', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi5/class.tx_commerce_pi5.php', '_pi5', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi6/class.tx_commerce_pi6.php', '_pi6', 'list_type', 0);

t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_commerce_pi6 = < plugin.tx_commerce_pi6.CSS_editor
',43);

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_products=1
');
t3lib_extMgm::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_products", field "description"
	# ***************************************************************************************
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

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_products", field "materialaufbau"
	# ***************************************************************************************
RTE.config.tx_commerce_products.materialaufbau < RTE.config.tx_commerce_products.description

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_products", field "anwendungsfelder"
	# ***************************************************************************************
RTE.config.tx_commerce_products.anwendungsfelder < RTE.config.tx_commerce_products.description
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_article_types=1
');
t3lib_extMgm::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_articles", field "description_extra"
	# ***************************************************************************************
RTE.config.tx_commerce_articles.description_extra {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db=1
  proc.exitHTMLparser_db {
    keepNonMatchedTags=1
    tags.font.allowedAttribs= color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_attributes=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_attribute_values=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_categories=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_trackingcodes=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_moveordermails=1
');

//$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.alt_menu_functions.inc']=t3lib_extMgm::extPath(COMMERCE_EXTkey).'class.ux_alt_menu_functions.php';
//$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.alt_menu_functions.inc']=t3lib_extMgm::extPath(GRAYTREE_EXTkey).'class.ux_alt_menu_functions.php';



// only in old TYPO3 versions below 4.0 apply extension to TCA Non type
if (t3lib_div::int_from_ver(TYPO3_version) < '4000000') {
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey).'class.ux_t3lib_tceforms.php';
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_doc.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey).'class.ux_sc_alt_doc.php';

}

// only in TYPO3 versions greater or equal 4.2
if (t3lib_div::int_from_ver(TYPO3_version) >= '4002000') {
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/classes/class.modulemenu.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey).'class.ux_modulemenu.php';
}

// add special in db list, to have the ability to search for OrderIds in TYPO 4.0
$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc']=t3lib_extMgm::extPath(COMMERCE_EXTkey).'class.ux_localrecordlist.php';


// Hooks for datamap procesing
// for processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_dmhooks.php:tx_commerce_dmhooks';

// Hooks for commandmap processing
// for new drawing of the category tree after having deleted a record
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_cmhooks.php:tx_commerce_cmhooks';


// Intiantation the Basket in the FE User class
// removed, since TYPO3 3.8.0 comes with a handy hook in initi fe_user :-)
//$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_fe.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey)."class.ux_tslib_fe.php";

// Hook for registering the basket
# $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'][] = 'EXT:commerce/hooks/class.tx_commerce_feuserhooks.php:tx_commerce_feuserhooks->user_addBasket';

require_once(PATH_txcommerce.'lib/class.tx_commerce_tcefunc_categorytree.php');

// adding some hooks for tx_commerce_article_processing
// as basic hook for calculation the delivery_costs
if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'] = 'EXT:commerce/hooks/class.tx_commerce_articlehooks.php:tx_commerce_articlehooks';
}

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'][] = 'EXT:commerce/hooks/class.tx_commerce_ordermailhooks.php:tx_commerce_ordermailhooks';
}

// languagefile for external creditard check
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['SYSPRODUCTS']['PAYMENT']['types']['creditcard']['ccvs_language_files'] = PATH_txcommerce .'payment/ccvs_language';


// hooks for sr_feuser_register


$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it
$_EXTKEY='commerce';
// pid for new tt_address records
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['create_address_pid'] = $_EXTCONF['create_address_pid'] ? $_EXTCONF['create_address_pid'] : '0';
// fe_user <-> tt_address field mapping
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['feuser_address_mapping'] = $_EXTCONF['feuser_address_mapping'] ? $_EXTCONF['feuser_address_mapping'] : 'company,company;name,name;last_name,surname;title,title;address,address;zip,zip;city,city;country,country;telephone,phone;fax,fax;email,email;www,www;';

// storage pid for baskets
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['BasketStoragePid'] = $_EXTCONF['BasketStoragePid'] ? $_EXTCONF['BasketStoragePid'] : 0;

// Basket locking
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['lockBasket'] = $_EXTCONF['lockBasket'] ? $_EXTCONF['lockBasket'] : 0;

// Show article number
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['showArticleNumber'] = $_EXTCONF['showArticleNumber'] ? $_EXTCONF['showArticleNumber'] : 0;

// Show article name
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['showArticleTitle'] = $_EXTCONF['showArticleTitle'] ? $_EXTCONF['showArticleTitle'] : 0;


// This line configures to process the code selectConf with the class "tx_commerce_hooks"
require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'hooks/class.tx_commerce_tcehooksHandler.php');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'tx_commerce_tcehooksHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'tx_commerce_tcehooksHandler';

require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'hooks/class.tx_srfeuserregister_commerce_hooksHandler.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] = 'tx_srfeuserregister_commerce_hooksHandler';

require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'hooks/class.tx_commerce_pi4hooksHandler.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']['deleteAddress'][] = 'tx_commerce_pi4hooksHandler';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']['saveAddress'][] = 'tx_commerce_pi4hooksHandler';

// CLI Skript configration
if (TYPO3_MODE=='BE')    {
    // Setting up scripts that can be run from the cli_dispatch.phpsh script.
    $TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:'.$_EXTKEY.'/cli/class.cli_commerce.php','_CLI_commerce');
}



require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'lib/class.tx_commerce_forms_select.php');
?>
