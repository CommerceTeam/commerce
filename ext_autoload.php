<?php

$extensionPath = t3lib_extMgm::extPath('commerce');

return array(
	'tx_commerce_articlecreator' => $extensionPath . 'class.tx_commerce_articlecreator.php',
	'tx_commerce_attributeeditor' => $extensionPath . 'class.tx_commerce_attributeeditor.php',

	'tx_commerce_article' => $extensionPath . 'lib/class.tx_commerce_article.php',
	'tx_commerce_article_price' => $extensionPath . 'lib/class.tx_commerce_article_price.php',
	'tx_commerce_attribute' => $extensionPath . 'lib/class.tx_commerce_attribute.php',
	'tx_commerce_attribute_value' => $extensionPath . 'lib/class.tx_commerce_attribute_value.php',
	'tx_commerce_basic_basket' => $extensionPath . 'lib/class.tx_commerce_basic_basket.php',
	'tx_commerce_basket' => $extensionPath . 'lib/class.tx_commerce_basket.php',
	'tx_commerce_basket_item' => $extensionPath . 'lib/class.tx_commerce_basket_item.php',
	'tx_commerce_belib' => $extensionPath . 'lib/class.tx_commerce_belib.php',
	'tx_commerce_category' => $extensionPath . 'lib/class.tx_commerce_category.php',
	'tx_commerce_create_folder' => $extensionPath . 'lib/class.tx_commerce_create_folder.php',
	'tx_commerce_db_alib' => $extensionPath . 'lib/class.tx_commerce_db_alib.php',
	'tx_commerce_db_article' => $extensionPath . 'lib/class.tx_commerce_db_article.php',
	'tx_commerce_db_attribute' => $extensionPath . 'lib/class.tx_commerce_db_attribute.php',
	'tx_commerce_db_attribute_value' => $extensionPath . 'lib/class.tx_commerce_db_attribute_value.php',
	'tx_commerce_db_category' => $extensionPath . 'lib/class.tx_commerce_db_category.php',
	'tx_commerce_db_list' => $extensionPath . 'lib/class.tx_commerce_db_list.php',
	'tx_commerce_db_product' => $extensionPath . 'lib/class.tx_commerce_db_product.php',
	'tx_commerce_db_price' => $extensionPath . 'lib/class.tx_commerce_db_price.php',
	'tx_commerce_div' => $extensionPath . 'lib/class.tx_commerce_div.php',
	'tx_commerce_element_alib' => $extensionPath . 'lib/class.tx_commerce_element_alib.php',
	'tx_commerce_folder_db' => $extensionPath . 'lib/class.tx_commerce_folder_db.php',
	'tx_commerce_forms_select' => $extensionPath . 'lib/class.tx_commerce_forms_select.php',
	'tx_commerce_feusers_localrecordlist' => $extensionPath . 'lib/class.tx_commerce_feusers_localrecordlist.php',
	'tx_commerce_order_localrecordlist' => $extensionPath . 'lib/class.tx_commerce_order_localrecordlist.php',
	'tx_commerce_pibase' => $extensionPath . 'lib/class.tx_commerce_pibase.php',
	'tx_commerce_product' => $extensionPath . 'lib/class.tx_commerce_product.php',
	'tx_commerce_statistics' => $extensionPath . 'lib/class.tx_commerce_statistics.php',

	'tx_commerce_pi1' => $extensionPath . 'pi1/class.tx_commerce_pi1.php',
	'tx_commerce_pi2' => $extensionPath . 'pi2/class.tx_commerce_pi2.php',
	'tx_commerce_pi3' => $extensionPath . 'pi3/class.tx_commerce_pi3.php',
	'tx_commerce_pi4' => $extensionPath . 'pi4/class.tx_commerce_pi4.php',
	'tx_commerce_pi6' => $extensionPath . 'pi6/class.tx_commerce_pi6.php',

	'tx_commerce_categorytree' => $extensionPath . 'treelib/class.tx_commerce_categorytree.php',
	'tx_commerce_categorymounts' => $extensionPath . 'treelib/class.tx_commerce_categorymounts.php',
	'tx_commerce_leaf_article' => $extensionPath . 'treelib/class.tx_commerce_leaf_article.php',
	'tx_commerce_leaf_articledata' => $extensionPath . 'treelib/class.tx_commerce_leaf_articledata.php',
	'tx_commerce_leaf_articleview' => $extensionPath . 'treelib/class.tx_commerce_leaf_articleview.php',
	'tx_commerce_leaf_category' => $extensionPath . 'treelib/class.tx_commerce_leaf_category.php',
	'tx_commerce_leaf_categorydata' => $extensionPath . 'treelib/class.tx_commerce_leaf_categorydata.php',
	'tx_commerce_leaf_categoryview' => $extensionPath . 'treelib/class.tx_commerce_leaf_categoryview.php',
	'tx_commerce_leaf_product' => $extensionPath . 'treelib/class.tx_commerce_leaf_product.php',
	'tx_commerce_leaf_productdata' => $extensionPath . 'treelib/class.tx_commerce_leaf_productdata.php',
	'tx_commerce_leaf_productview' => $extensionPath . 'treelib/class.tx_commerce_leaf_productview.php',
	'tx_commerce_treelib_browser' => $extensionPath . 'treelib/class.tx_commerce_treelib_browser.php',
	'tx_commerce_treelib_link_categorytree' => $extensionPath . 'treelib/link/class.tx_commerce_treelib_link_categorytree.php',
	'tx_commerce_treelib_link_leaf_categoryview' => $extensionPath . 'treelib/link/class.tx_commerce_treelib_link_leaf_categoryview.php',
	'tx_commerce_treelib_link_leaf_productview' => $extensionPath . 'treelib/link/class.tx_commerce_treelib_link_leaf_productview.php',
	'tx_commerce_treelib_tceforms' => $extensionPath . 'treelib/class.tx_commerce_treelib_tceforms.php',


	'tx_commerce_payment' => $extensionPath . 'payment/interfaces/interface.tx_commerce_payment.php',
	'tx_commerce_payment_abstract' => $extensionPath . 'payment/class.tx_commerce_payment_abstract.php',
	'tx_commerce_payment_cashondelivery' => $extensionPath . 'payment/class.tx_commerce_payment_cashondelivery.php',
	'tx_commerce_payment_creditcard' => $extensionPath . 'payment/class.tx_commerce_payment_creditcard.php',
	'tx_commerce_payment_debit' => $extensionPath . 'payment/class.tx_commerce_payment_debit.php',
	'tx_commerce_payment_invoice' => $extensionPath . 'payment/class.tx_commerce_payment_invoice.php',
	'tx_commerce_payment_prepayment' => $extensionPath . 'payment/class.tx_commerce_payment_prepayment.php',

	'tx_commerce_payment_provider' => $extensionPath . 'payment/provider/interfaces/interface.tx_commerce_payment_provider.php',
	'tx_commerce_payment_provider_abstract' => $extensionPath . 'payment/provider/class.tx_commerce_payment_provider_abstract.php',
	'tx_commerce_payment_provider_wirecard' => $extensionPath . 'payment/provider/class.tx_commerce_payment_provider_wirecard.php',

    'tx_commerce_payment_criterion' => $extensionPath . 'payment/criteria/interfaces/interface.tx_commerce_payment_criterion.php',
	'tx_commerce_payment_criterion_abstract' => $extensionPath . 'payment/criteria/class.tx_commerce_payment_criterion_abstract.php',

	'tx_commerce_payment_ccvs' => $extensionPath . 'payment/ccvs/class.tx_commerce_payment_ccvs.php',
    'feusers_observer' => $extensionPath . 'dao/class.feusers_observer.php'    
);
?>