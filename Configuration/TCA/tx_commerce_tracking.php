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
        'title' => $languageFile . 'tx_commerce_tracking',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_tracking.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'orders_uid, trackingcodes_uid, msg',
    ],
    'interface' => [
        'showRecordFieldList' => 'orders_uid,trackingcodes_uid,msg',
    ],
    'columns' => [
        'orders_uid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_tracking.orders_uid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_orders',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'trackingcodes_uid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_tracking.trackingcodes_uid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_trackingcodes',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'msg' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_tracking.msg',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                orders_uid, trackingcodes_uid, msg
            '
        ],
    ],
];
