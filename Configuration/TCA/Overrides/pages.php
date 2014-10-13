<?php

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
