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

$GLOBALS['TCA']['tx_commerce_articles'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'cruser_id' => 'cruser_id',
        'versioning' => '1',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY sorting,crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'dividers2tabs' => '1',
        'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'article.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
            title, subtitle, navtitle, description_extra, plain_text, price_gross, price_net, purchase_price, tax,
            article_type_uid, products_uid',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime,
            fe_group, title, subtitle, navtitle, images, ordernumber, eancode, description_extra,plain_text,
            price_gross, price_net, purchase_price, tax, article_type_uid, products_uid, article_attributes',
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0),
                ),
            ),
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_commerce_articles',
                'foreign_table_where' => ' AND tx_commerce_articles.pid = ###CURRENT_PID###
                    AND tx_commerce_articles.sys_language_uid IN (-1,0)',
            ),
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0',
            ),
        ),
        'starttime' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
            'config' => array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0',
            ),
        ),
        'endtime' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
            'config' => array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => '0',
                'range' => array(
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y')),
                ),
            ),
        ),
        'fe_group' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
            'config' => array(
                'type' => 'select',
                'size' => 5,
                'maxitems' => 50,
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--'),
                ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ),
        ),
        'title' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.title',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'required,trim',
            ),
        ),
        'subtitle' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.subtitle',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ),
        ),
        'navtitle' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.navtitle',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'images' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.images',
            'l10n_mode' => 'mergeIfNotBlank',
            'config' => array(
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
            ),
        ),
        'ordernumber' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.ordernumber',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
            ),
        ),
        'eancode' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.eancode',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'eval' => 'trim',
            ),
        ),
        'description_extra' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.description_extra',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
                'wizards' => array(
                    '_PADDING' => 2,
                    'RTE' => array(
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
                        'icon' => 'wizard_rte2.gif',
                        'script' => 'wizard_rte.php',
                    ),
                ),
            ),
        ),
        'plain_text' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.plain_text',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            ),
        ),
        'prices' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.prices',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'inline',
                'appearance' => array(
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                            ),
                'foreign_table' => 'tx_commerce_article_prices',
                'foreign_field' => 'uid_article',
            ),
        ),
        'tax' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.tax',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ),
        ),
        'supplier_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.title',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'tx_commerce_supplier',
                'items' => array(
                    array(
                        'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.noManufacturer',
                        0,
                    ),
                ),
            ),
        ),
        'article_type_uid' => array(
            'exclude' => 1,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.article_type_uid',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'tx_commerce_article_types',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 1,
            ),
        ),
        'relatedpage' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.relatedpage',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'uid_product' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.products_uid',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_products',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'attributesedit' => array(
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.edit_attributes',
            'l10n_display' => 'hideDiff',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<ROOT>
		<type>array</type>
		<el>
			<dummy>
				<TCEforms>
					<config>
						<type>input</type>
					</config>
				</TCEforms>
			</dummy>
		</el>
	</ROOT>
</T3DataStructure>',
                ),
            ),
        ),
    ),
    'types' => array(
        '0' => array(
            'showitem' => '
            hidden;;1, title, subtitle, ordernumber,eancode,
            description_extra;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/],
            images, plain_text, tax, supplier_uid, article_type_uid, relatedpage;;;;1-1-1, products_uid,
            article_attributes,' .
                (
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode'] ? '' : '
            --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.edit_attributes,
            attributesedit,
                    '
                ) .
                '
            --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.prices, prices',
        ),
    ),
    'palettes' => array(
        '1' => array('showitem' => 'starttime, endtime, fe_group'),
    ),
);

return $GLOBALS['TCA']['tx_commerce_articles'];
