<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';
$langFile = 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_supplier',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'default_sortby' => 'ORDER BY title,uid',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'dividers2tabs' => '1',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_supplier.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'title, street, number, zip, city, country, phone, fax, email, internet,
            contactperson, logo',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ],
        ],
        'street' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.street',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'number' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.number',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'zip' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.zip',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'city' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.city',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'country' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.country',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'static_countries',
                'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',

            ],
        ],
        'phone' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.phone',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'fax' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.fax',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'email' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.email',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'internet' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.internet',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'contactperson' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.contactperson',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'logo' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_supplier.logo',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('logo', [
                'appearance' => [
                    'createNewRelationLinkTitle' =>
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ],
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'foreign_types' => [
                    '0' => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.audioOverlayPalette;audioOverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.videoOverlayPalette;videoOverlayPalette,
                            --palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                        'showitem' => '
                            --palette--;' . $langFile . 'sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ]
                ]
            ], $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                hidden, title, 
                --palette--;' . $languageFile . 'palette.street;street,
                --palette--;' . $languageFile . 'palette.city;city,
                country, contactperson, phone, fax, email, internet, logo',
        ],
    ],
    'palettes' => [
        'street' => [
            'showitem' => 'street, number'
        ],
        'city' => [
            'showitem' => 'zip, city'
        ],
    ],
];
