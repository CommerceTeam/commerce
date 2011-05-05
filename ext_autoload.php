<?php

$extensionPath = t3lib_extMgm::extPath('commerce');

return array(
	'tx_commerce_basket' => $extensionPath . 'lib/class.tx_commerce_basket.php',
	'tx_commerce_basket_item' => $extensionPath . 'lib/class.tx_commerce_basket_item.php',
	'tx_commerce_category' => $extensionPath . 'lib/class.tx_commerce_category.php',
	'tx_commerce_div' => $extensionPath . 'lib/class.tx_commerce_div.php',
	'tx_commerce_folder_db' => $extensionPath . 'lib/class.tx_commerce_folder_db.php',
	'tx_commerce_pibase' => $extensionPath . 'lib/class.tx_commerce_pibase.php',
	'tx_commerce_product' => $extensionPath . 'lib/class.tx_commerce_product.php',
);
?>