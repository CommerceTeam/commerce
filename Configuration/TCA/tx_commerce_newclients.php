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

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_newclients',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'readOnly' => '1',
        'adminOnly' => '1',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_newclients.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'year, month, day, dow, hour, registration',
    ],
    'interface' => [
        'showRecordFieldList' => 'year, month, day, dow, hour, registration',
    ],
    'columns' => [
        'year' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_newclients.year',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'month' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_newclients.month',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'day' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_newclients.day',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'dow' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_newclients.dow',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'hour' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_newclients.hour',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'registration' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_newclients.registration',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'year, month, day, dow, hour, registration'
        ],
    ],
];
