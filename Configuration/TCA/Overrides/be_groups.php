<?php

// extend beusers/begroups for access control
$tempColumns = [
    'tx_commerce_mountpoints' => [
        'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:label.tx_commerce_mountpoints',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectCommerceCategoryTree',
            'size' => 10,
            'minitems' => 0,
            'maxitems' => 20,
            'foreign_table' => 'tx_commerce_categories',
            'foreign_table_where' => 'AND sys_language_uid = 0',
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'be_groups',
    'tx_commerce_mountpoints',
    '',
    'after:db_mountpoints'
);
