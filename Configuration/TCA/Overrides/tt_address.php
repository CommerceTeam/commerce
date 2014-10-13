<?php

$tempColumns = array(
	'surname' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.surname',
		'config' => array(
			'type' => 'input',
			'size' => '40',
			'max' => '50',
		)
	),
	'tx_commerce_default_values' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_default_values',
		'config' => array(
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => array(
				'upper' => '1000',
				'lower' => '10'
			),
			'default' => 0
		)
	),
	'tx_commerce_fe_user_id' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_fe_user_id',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'fe_users',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_address_type_id' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_address_type_id',
		'config' => array(
			'type' => 'select',
			'item' => array(
				array('', 0),
			),
			'foreign_table' => 'tx_commerce_address_types',
			'foreign_table_where' => 'AND tx_commerce_address_types.pid=0',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_commerce_is_main_address' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tt_address.tx_commerce_is_main_address',
		'config' => array(
			'type' => 'check',
		),
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_address', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'tt_address',
	'tx_commerce_default_values,tx_commerce_fe_user_id, tx_commerce_address_type_id, surname,tx_commerce_is_main_address'
);

/**
 * Put surename directly to name
 */
$ttaddressparts = explode('name,', $GLOBALS['TCA']['tt_address']['interface']['showRecordFieldList']);
$countto = count($ttaddressparts) - 1;
for ($i = 0; $i < $countto; ++$i) {
	if (strlen($ttaddressparts[$i]) == 0 || substr($ttaddressparts[$i], -1, 1) == ',') {
		$ttaddressparts[$i] = $ttaddressparts[$i] . 'name,surname,';
	} else {
		$ttaddressparts[$i] = $ttaddressparts[$i] . 'name,';
	}
}
$GLOBALS['TCA']['tt_address']['interface']['showRecordFieldList'] = implode('', $ttaddressparts);