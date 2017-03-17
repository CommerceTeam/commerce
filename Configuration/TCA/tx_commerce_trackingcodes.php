<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'label' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_trackingcodes',
        'versioningWS' => true,
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_trackingcodes.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'title, description, sys_language_uid, l18n_parent',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ],
        ],
        'l18n_parent' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_commerce_trackingcodes',
                'foreign_table_where' => 'AND tx_commerce_trackingcodes.pid = ###CURRENT_PID###
                    AND tx_commerce_trackingcodes.sys_language_uid IN (-1,0)',
                'default' => 0
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ],
        ],

        'title' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_trackingcodes.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_trackingcodes.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'columnsOverrides' => [
                'description' => [
                    'config' => [
                        'enableRichtext' => true,
                        'richtextConfiguration' => 'default',
                    ]
                ],
            ],
            'showitem' => '
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
                title, description
            '
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => 'sys_language_uid, --linebreak--, l18n_parent'
        ],
    ],
];
