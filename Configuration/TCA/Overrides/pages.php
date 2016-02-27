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
