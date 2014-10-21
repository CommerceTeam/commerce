<?php

	// we want to avoid the pollution
namespace PHPSTORM_META {
		// just to have a green code below
	/** @noinspection PhpUnusedLocalVariableInspection */
	/** @noinspection PhpIllegalArrayKeyTypeInspection */
	$STATIC_METHOD_TYPES = [
		\t3lib_div::makeInstance('') => [
			'Tx_Commerce_Domain_Model_Product' instanceof \Tx_Commerce_Domain_Model_Product,
		],
	];

	/** @noinspection PhpUnusedLocalVariableInspection */
	/** @noinspection PhpIllegalArrayKeyTypeInspection */
	$STATIC_METHOD_TYPES = [
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('') => $STATIC_METHOD_TYPES[\t3lib_div::makeInstance('')],
		\TYPO3\CMS\Extbase\Object\ObjectManager::create('') => $STATIC_METHOD_TYPES[\t3lib_div::makeInstance('')],
		\TYPO3\CMS\Extbase\Object\ObjectManager::get('') => $STATIC_METHOD_TYPES[\t3lib_div::makeInstance('')],
	];
}

?>