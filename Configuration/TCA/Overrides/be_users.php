<?php

// extend beusers/begroups for access control
$tempColumns = array(
	'tx_commerce_mountpoints' => array(
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:label.tx_commerce_mountpoints',
		'config' => $GLOBALS['T3_VAR']['ext'][COMMERCE_EXTKEY]['TCA']['mountpoints_config'],
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'be_users', 'tx_commerce_mountpoints', '', 'after:fileoper_perms'
);
