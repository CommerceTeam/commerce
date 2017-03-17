<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_tracking',
        'versioningWS' => true,
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_tracking.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'orders_uid, trackingcodes_uid, msg',
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
