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

/**
 * Dynamic config file for tx_commerce_products.
 *
 * @author 2005-2010 Ingo Schmitt <is@marketing-factory.de>
 */
$GLOBALS['TCA']['tx_commerce_products'] = [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_products',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'versioningWS' => true,
        'delete' => 'deleted',
        'thumbnail' => 'images',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_products.gif',
        'dividers2tabs' => '1',
        'searchFields' => 'uid, title, subtitle, navtitle, description',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            title, subtitle, navtitle, description, images, teaser, teaserimages, manufacturer_uid',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            title, subtitle, navtitle, description, images, teaser, teaserimages, categories, manufacturer_uid,
            attributes',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 50,
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.any_login', -2],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--'],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ],
        ],
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
                'foreign_table' => 'tx_commerce_products',
                'foreign_table_where' => 'AND tx_commerce_products.pid = ###CURRENT_PID###
                    AND tx_commerce_products.sys_language_uid IN (-1,0)',
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
            'exclude' => 0,
            'label' => $languageFile . 'tx_commerce_products.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.subtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'navtitle' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.navtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ],
        ],
        'keywords' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.keywords',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'wizards' => [
                    '_PADDING' => 2,
                    'RTE' => [
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'Full screen Rich Text Editing',
                        'icon' => 'wizard_rte2.gif',
                        'module' => [
                            'name' => 'wizard_rte'
                        ]
                    ],
                ],
            ],
        ],
        'images' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.images',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'show_thumbs' => 1,
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 200,
                'autoSizeMax' => 40,
            ],
        ],
        'teaser' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.teaser',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'wizards' => [
                    '_PADDING' => 2,
                    'RTE' => [
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'Full screen Rich Text Editing',
                        'icon' => 'wizard_rte2.gif',
                        'module' => [
                            'name' => 'wizard_rte'
                        ]
                    ],
                ],
            ],
        ],
        'teaserimages' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.teaserimages',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
                'uploadfolder' => 'uploads/tx_commerce',
                'show_thumbs' => 1,
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 200,
                'autoSizeMax' => 40,
            ],
        ],
        'categories' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => $languageFile . 'tx_commerce_products.categories',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_categories',
                'form_type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\TceFunc::class . '->getSingleField_selectCategories',
                'treeView' => 1,
                'treeClass' => \CommerceTeam\Commerce\ViewHelpers\Browselinks\CategoryTree::class,
                'size' => 7,
                'autoSizeMax' => 10,
                'minitems' => 1,
                'maxitems' => 20,
            ],
        ],

        'manufacturer_uid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_manufacturer.title',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_manufacturer',
                'foreign_table_where' => 'ORDER BY tx_commerce_manufacturer.title ASC',
                'items' => [
                    [
                        $languageFile . 'tx_commerce_products.noManufacturer',
                        0
                    ],
                ],
            ],
        ],
        'relatedpage' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => $languageFile . 'tx_commerce_products.relatedpage',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'relatedproducts' => [
            'exclude' => 1,
            'label' =>
                $languageFile . 'tx_commerce_products.relatedproducts',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_products',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 20,
                'MM' => 'tx_commerce_products_related_mm',
                'foreign_table' => 'tx_commerce_products',
            ],
        ],
        'attributes' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.attributes',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<ROOT>
		<type>array</type>
	</ROOT>
</T3DataStructure>',
                ],
            ],
        ],
        'attributesedit' => [
            'exclude' => 1,
            'l10n_display' => 'hideDiff',
            'label' => $languageFile . 'tx_commerce_products.attributes',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<ROOT>
		<type>array</type>
	</ROOT>
</T3DataStructure>',
                ],
            ],
        ],
        'articles' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.articles',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<sheets>
		<sEXISTING>
			<ROOT>
				<TCEforms>
					<sheetTitle>' . $languageFile . 'tx_commerce_products.existing_articles</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
					<existingArticles>
						<TCEforms>
							<config>
								<type>user</type>
								<userFunc>' . \CommerceTeam\Commerce\Utility\ArticleCreatorUtility::class .
                        '->existingArticles</userFunc>
							</config>
						</TCEforms>
					</existingArticles>
				</el>
			</ROOT>
		</sEXISTING>
		<sCREATE>
			<ROOT>
				<TCEforms>
				<sheetTitle>' . $languageFile . 'tx_commerce_products.producible_articles</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
					<producibleArticles>
						<TCEforms>
							<config>
								<type>user</type>
								<userFunc>' . \CommerceTeam\Commerce\Utility\ArticleCreatorUtility::class .
                        '->producibleArticles</userFunc>
							</config>
						</TCEforms>
					</producibleArticles>
				</el>
			</ROOT>
		</sCREATE>
	</sheets>
</T3DataStructure>',
                ],
            ],
        ],

        'articleslok' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_products.articleslok',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<sheets>
		<sEXISTING>
			<ROOT>
				<TCEforms>
					<sheetTitle>' . $languageFile . 'tx_commerce_products.existing_articles</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
					<existingArticles>
						<TCEforms>
							<config>
								<type>user</type>
								<userFunc>' . \CommerceTeam\Commerce\Utility\ArticleCreatorUtility::class .
                        '->existingArticles</userFunc>
							</config>
						</TCEforms>
					</existingArticles>
				</el>
			</ROOT>
		</sEXISTING>
	</sheets>
</T3DataStructure>',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                    --palette--;' . $languageFile . 'palette.general;general,
                    title, subtitle, navtitle, keywords,
                    images, teaserimages,
                    description;;;richtext:rte_transform[fmode=ts_css|imgpath=uploads/tx_commerce/rte/],
                    teaser;;;richtext:rte_transform[mode=ts_css|imgpath=uploads/tx_commerce/rte/],
                --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tabs.references,
                    categories, manufacturer_uid, relatedpage, relatedproducts,
                --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
                    hidden,
                    --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access',
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => 'sys_language_uid, --linebreak--, l18n_parent',
        ],
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, fe_group',
        ],
    ],
];

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode'])
    && $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']['simpleMode']) {
    $GLOBALS['TCA']['tx_commerce_products']['columns']['articles'] = [
        'exclude' => 1,
        'label' => $languageFile . 'tx_commerce_products.articles',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_commerce_articles',
            'foreign_field' => 'uid_product',
            'minitems' => 0,
        ],
    ];
    $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem'] = str_replace(
        'articleslok',
        'articles',
        $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem']
    );
}

return $GLOBALS['TCA']['tx_commerce_products'];
