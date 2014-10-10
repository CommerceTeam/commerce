<?php

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi1'] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][COMMERCE_EXTKEY . '_pi1'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	COMMERCE_EXTKEY . '_pi1',
	'FILE:EXT:commerce/Configuration/FlexForms/flexform_pi1.xml'
);

/* ################# PI1 (product listing) ##################### */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi1', COMMERCE_EXTKEY . '_pi1'),
	'list_type',
	'commerce'
);

/* ################# PI2 (basket) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi2'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi2', COMMERCE_EXTKEY . '_pi2'),
	'list_type',
	'commerce'
);

/* ################# PI3 (checkout) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi3'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi3', COMMERCE_EXTKEY . '_pi3'),
	'list_type',
	'commerce'
);

/* ################# PI4 (addresses) ##################### */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi4'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi4', COMMERCE_EXTKEY . '_pi4'),
	'list_type',
	'commerce'
);

/* ################ PI6 (invoice) ############################*/
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][COMMERCE_EXTKEY . '_pi6'] = 'layout,select_key,pages';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tt_content.list_type_pi6', COMMERCE_EXTKEY . '_pi6'),
	'list_type',
	'commerce'
);