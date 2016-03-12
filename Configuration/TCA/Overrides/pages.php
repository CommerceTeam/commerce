<?php

$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:commerce',
    'commerce',
];

$tempColumns = [
    'tx_commerce_foldereditorder' => [
        'displayCond' => 'FIELD:tx_commerce_foldername:REQ:true',
        'exclude' => 1,
        'label' =>
        'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_pages.tx_commerce_foldereditorder',
        'config' => [
            'type' => 'check',
            'default' => '0',
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_commerce_foldereditorder');
