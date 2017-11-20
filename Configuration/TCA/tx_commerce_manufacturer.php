<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';
$langFile = 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:';

return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_manufacturer',
        'delete' => 'deleted',
        'versioningWS' => true,
        'default_sortby' => 'ORDER BY title,uid',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_manufacturer.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'title, street, number, zip, city, country, phone, fax, email, internet,
            contactperson, logo',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ]
        ],

        'title' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ]
        ],
        'street' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.street',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'number' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.number',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'zip' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.zip',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'city' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.city',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'country' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.country',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'static_countries',
                'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',
            ]
        ],
        'phone' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.phone',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'fax' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.fax',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'email' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.email',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'internet' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.internet',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'contactperson' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.contactperson',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ]
        ],
        'logo' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.logo',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'logo',
                ['maxitems' => 1],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, 
                    --palette--;' . $languageFile . 'palette.street;street,
                    --palette--;' . $languageFile . 'palette.city;city,
                    country, contactperson, phone, fax, email, internet, logo,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden
            ',
        ],
    ],
    'palettes' => [
        'street' => [
            'showitem' => 'street, number'
        ],
        'city' => [
            'showitem' => 'zip, city'
        ],
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
    ],
];
