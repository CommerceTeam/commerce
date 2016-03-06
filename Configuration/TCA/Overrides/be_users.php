<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'be_users',
    'tx_commerce_mountpoints',
    '',
    'after:fileoper_perms'
);
