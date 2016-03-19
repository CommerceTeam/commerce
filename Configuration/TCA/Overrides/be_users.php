<?php

// extend beusers/begroups for access control
$tempColumns = [
    'tx_commerce_mountpoints' => [
        'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:label.tx_commerce_mountpoints',
        'config' => [
            'type' => 'select',
            'renderType' => 'commerceCategoryTree',
            'foreign_table' => 'tx_commerce_categories',
            'foreign_table_where' => 'AND sys_language_uid = 0',
            'size' => 10,
            'minitems' => 0,
            'maxitems' => 20,
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'be_users',
    'tx_commerce_mountpoints',
    '',
    'after:db_mountpoints'
);
