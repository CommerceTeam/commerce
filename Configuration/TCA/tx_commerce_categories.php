<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories',
		'label' => 'title',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'categories.gif',
		'dividers2tabs' => '1',
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title,
			subtitle, navtitle, description, images, keywords, teaser, teaserimages'
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title,
			subtitle, description, images, navtitle, keywords, attributes, parent_category, teaser, teaserimages',
	),
	'columns' => array(
		'sys_language_uid' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
				)
			)
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
				'foreign_table' => 'tx_commerce_categories',
				'foreign_table_where' => 'AND tx_commerce_categories.pid = ###CURRENT_PID###
					AND tx_commerce_categories.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'editlock' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:editlock',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xml:labels.enabled',
					),
				),
			),
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
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
				'checkbox' => '0'
			)
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
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
				)
			)
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
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',

			)
		),
		'extendToSubpages' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.extendToSubpages',
			'config' => array(
				'type' => 'check'
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.title',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.subtitle',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'description' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.description',
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
			)
		),
		'images' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.images',
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
				'maxitems' => 10,
			)
		),
		'navtitle' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.navtitle',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'keywords' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.keywords',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'parent_category' => array(
			'exclude' => 0,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.parent_category',
			'config' => array(
				'type' => 'passthrough',
				'form_type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_TceFunc->getSingleField_selectCategories',
				'treeViewBrowseable' => TRUE,
				'size' => 10,
				'autoSizeMax' => 30,
				'minitems' => 0,
				'maxitems' => 20,
				'eval' => 'required',
			),
		),
		'ts_config' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.ts_config',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '10',
			),
		),
		'attributes' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.attributes',
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
	</ROOT>
</T3DataStructure>
					'
				),
			),
		),
		'teaser' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.teaser',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '10',
			)
		),
		'teaserimages' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.teaserimages',
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
				'maxitems' => 5,
			)
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => '
				sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, title;;;;2-2-2, subtitle;;;;3-3-3,
				description;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/], images,
				teaser;;;;3-3-3, teaserimages, navtitle, keywords,parent_category;;;;1-1-1, relatedpage;;;;1-1-1, ts_config;;;;1-1-1,
			--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_categories.select_attributes, attributes'
		)
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime, fe_group, extendToSubpages')
	)
);
