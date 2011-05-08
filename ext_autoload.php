<?php

$extensionPath = t3lib_extMgm::extPath('commerce');

return array(
	'tx_commerce_articlecreator' => $extensionPath . 'class.tx_commerce_articlecreator.php',
	'tx_commerce_attributeeditor' => $extensionPath . 'class.tx_commerce_attributeeditor.php',

	'tx_commerce_element_alib' => 'lib/class.tx_commerce_element_alib.php',
	'tx_commerce_article' => $extensionPath . 'lib/class.tx_commerce_article.php',
	'tx_commerce_basket' => $extensionPath . 'lib/class.tx_commerce_basket.php',
	'tx_commerce_basket_item' => $extensionPath . 'lib/class.tx_commerce_basket_item.php',
	'tx_commerce_belib' => $extensionPath . 'lib/class.tx_commerce_belib.php',
	'tx_commerce_category' => $extensionPath . 'lib/class.tx_commerce_category.php',
	'tx_commerce_create_folder' => $extensionPath . 'lib/class.tx_commerce_create_folder.php',
	'tx_commerce_db_price' => $extensionPath . 'lib/class.tx_commerce_db_price.php',
	'tx_commerce_div' => $extensionPath . 'lib/class.tx_commerce_div.php',
	'tx_commerce_folder_db' => $extensionPath . 'lib/class.tx_commerce_folder_db.php',
	'tx_commerce_forms_select' => $extensionPath . 'lib/class.tx_commerce_forms_select.php',
	'tx_commerce_pibase' => $extensionPath . 'lib/class.tx_commerce_pibase.php',
	'tx_commerce_product' => $extensionPath . 'lib/class.tx_commerce_product.php',

	'tx_commerce_pi1' => $extensionPath . 'pi1/class.tx_commerce_pi1.php',
	'tx_commerce_pi2' => $extensionPath . 'pi2/class.tx_commerce_pi2.php',
	'tx_commerce_pi3' => $extensionPath . 'pi3/class.tx_commerce_pi3.php',
	'tx_commerce_pi4' => $extensionPath . 'pi4/class.tx_commerce_pi4.php',
	'tx_commerce_pi6' => $extensionPath . 'pi6/class.tx_commerce_pi6.php',

	'tx_commerce_categorytree' => $extensionPath . 'treelib/class.tx_commerce_categorytree.php',
	'tx_commerce_categorymounts' => $extensionPath . 'treelib/class.tx_commerce_categorymounts.php',
	'tx_commerce_treelib_browser' => $extensionPath . 'treelib/class.tx_commerce_treelib_browser.php',
);
?>