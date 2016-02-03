<?php
namespace CommerceTeam\Commerce\Hook;

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

use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Hook\LocalRecordListHooks.
 *
 * @author 2014 Sebastian Fischer <typo3@evoweb.de>
 */
class LocalRecordListHooks implements \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface
{
    /**
     * Modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row.
     *
     * @param string $table Database table
     * @param array $row Record row
     * @param array $cells Clip-icons to get modified
     * @param \CommerceTeam\Commerce\ViewHelpers\CategoryRecordList $parentObject Parent
     *
     * @return array the modified clip-icons
     */
    public function makeClip($table, $row, $cells, &$parentObject)
    {
        if ($parentObject->id
            && !SettingsFactory::getInstance()->getTcaValue($table . '.ctrl.readOnly')
            && $this->getController()->MOD_SETTINGS['bigControlPanel']
            && $table == 'tx_commerce_orders'
        ) {
            $cells['moveOrder'] = '<input type="checkbox" name="orderUid[]" value="' . $row['uid'] .
                '" class="smallCheckboxes">';
        }

        return $cells;
    }

    /**
     * Modifies Web>List control icons of a displayed row
     * just to satisfy interface.
     *
     * @param string $table The current database table
     * @param array $row The current record row
     * @param array $cells The default control-icons to get modified
     * @param \CommerceTeam\Commerce\ViewHelpers\CategoryRecordList $parentObject Parent
     *
     * @return array the modified control-icons
     */
    public function makeControl($table, $row, $cells, &$parentObject)
    {
        return $cells;
    }

    /**
     * Render list header.
     *
     * @param string $table Table
     * @param array $currentIdList Current id list
     * @param array $headerColumns Header columns
     * @param \CommerceTeam\Commerce\ViewHelpers\OrderRecordList $parentObject Parent
     *
     * @return array
     */
    public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $language = $this->getLanguageService();

        if (get_class($parentObject) == \CommerceTeam\Commerce\ViewHelpers\OrderRecordList::class) {
            $icon = '';
            foreach ($parentObject->fieldArray as $fCol) {
                // Calculate users permissions to edit records in the table:
                $permsEdit = $parentObject->calcPerms & ($table == 'pages' ? 2 : 16);

                switch ((string) $fCol) {
                    // Path
                    case '_PATH_':
                        $headerColumns[$fCol] = '<i>[' .
                            $language->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_', 1) . ']</i>';
                        break;

                    // References
                    case '_REF_':
                        $headerColumns[$fCol] = '<i>[' .
                            $language->sL('LLL:EXT:lang/locallang_mod_file_list.xml:c__REF_', 1) . ']</i>';
                        break;

                    // Path
                    case '_LOCALIZATION_':
                        $headerColumns[$fCol] = '<i>[' .
                            $language->sL('LLL:EXT:lang/locallang_core.php:labels._LOCALIZATION_', 1) . ']</i>';
                        break;

                    // Path
                    case '_LOCALIZATION_b':
                        $headerColumns[$fCol] = $language->getLL('Localize', 1);
                        break;

                    // Clipboard:
                    case '_CLIPBOARD_':
                        if ($parentObject->id
                            && !SettingsFactory::getInstance()->getTcaValue($table . '.ctrl.readOnly')
                            && $this->getController()->MOD_SETTINGS['bigControlPanel']
                        ) {
                            $headerColumns[$fCol] = $language->getLL('moveorderto');
                        } else {
                            $headerColumns[$fCol] = '';
                        }
                        break;

                    // Control panel:
                    case '_CONTROL_':
                        if (!SettingsFactory::getInstance()->getTcaValue($table . '.ctrl.readOnly')) {
                            // If new records can be created on this page, add links:
                            if ($parentObject->calcPerms & ($table == 'pages' ? 8 : 16)
                                && $parentObject->showNewRecLink($table)
                            ) {
                                if ($table == 'pages') {
                                    $sprite = $iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL);
                                } else {
                                    $sprite = $iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL);
                                }

                                if ($table == 'tt_content' && $parentObject->newWizards) {
                                    // If mod.web_list.newContentWiz.overrideWithExtension is set,
                                    // use that extension's create new content wizard instead:
                                    $tmpTypoScript = BackendUtility::getModTSconfig($parentObject->id, 'mod.web_list');
                                    $tmpTypoScript =
                                        $tmpTypoScript['properties']['newContentWiz.']['overrideWithExtension'];
                                    $newContentWizScriptPath =
                                        (ExtensionManagementUtility::isLoaded($tmpTypoScript)) ?
                                        (ExtensionManagementUtility::extRelPath($tmpTypoScript) .
                                            'mod1/db_new_content_el.php') :
                                        'sysext/cms/layout/db_new_content_el.php';

                                    $icon = '<a href="#" onclick="' . htmlspecialchars(
                                        'return jumpExt(\'' . $newContentWizScriptPath . '?id=' . $parentObject->id .
                                        '\');'
                                    ) . '" title="' . $language->getLL('new', true) . '">' . $sprite . '</a>';
                                } elseif ($table == 'pages' && $parentObject->newWizards) {
                                    $icon = '<a href="' . htmlspecialchars(
                                        'db_new.php?id=' . $parentObject->id .
                                        '&pagesOnly=1&returnUrl=' .
                                        rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'))
                                    ) . '" title="' . $language->getLL('new', true) . '">' . $sprite . '</a>';
                                } else {
                                    $params = '&edit[' . $table . '][' . $parentObject->id . ']=new';
                                    if ($table == 'pages_language_overlay') {
                                        $params .= '&overrideVals[pages_language_overlay][doktype]=' .
                                            (int) $parentObject->pageRow['doktype'];
                                    }

                                    $icon = '<a href="#" onclick="' . htmlspecialchars(
                                        BackendUtility::editOnClick($params, '', -1)
                                    ) . '" title="' . $language->getLL('new', true) . '">' . $sprite . '</a>';
                                }
                            }

                            // If the table can be edited, add link for editing
                            // ALL SHOWN fields for all listed records:
                            if ($permsEdit && $parentObject->table && is_array($currentIdList)) {
                                $editIdList = implode(',', $currentIdList);
                                if ($parentObject->clipNumPane()) {
                                    $editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
                                }
                                $params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' .
                                    implode(',', $parentObject->fieldArray) . '&disHelp=1';
                                $icon .= '<a href="#" onclick="' . htmlspecialchars(
                                    BackendUtility::editOnClick($params, '', -1)
                                ) . '" title="' . $language->getLL('editShownColumns', true) . '">' .
                                    $iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL) . '</a>';
                            }
                            // add an empty entry, so column count fits again after moving this into $icon
                            $headerColumns[$fCol] = '&nbsp;';
                        }
                        break;

                    // space column
                    case '_AFTERCONTROL_':
                        // space column
                    case '_AFTERREF_':
                        $headerColumns[$fCol] = '&nbsp;';
                        break;

                    // Regular fields header:
                    default:
                        $headerColumns[$fCol] = '';
                        if ($parentObject->table && is_array($currentIdList)) {
                            // If the numeric clipboard pads are selected, show duplicate sorting link:
                            if ($parentObject->clipNumPane()) {
                                $headerColumns[$fCol] .= '<a href="' . htmlspecialchars(
                                    $parentObject->listURL('', -1) . '&duplicateField=' . $fCol
                                ) . '" title="' . $language->getLL('clip_duplicates', true)
                                    . '">'
                                    .  $iconFactory->getIcon('actions-document-duplicates-select', Icon::SIZE_SMALL)
                                    . '</a>';
                            }

                            // If the table can be edited, add link for
                            // editing THIS field for all listed records:
                            if (!SettingsFactory::getInstance()->getTcaValue($table . '.ctrl.readOnly')
                                && $permsEdit
                                && SettingsFactory::getInstance()->getTcaValue($table . '.columns.' . $fCol)
                            ) {
                                $editIdList = implode(',', $currentIdList);
                                if ($parentObject->clipNumPane()) {
                                    $editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
                                }
                                $params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol .
                                    '&disHelp=1';
                                $iTitle = sprintf(
                                    $language->getLL('editThisColumn'),
                                    rtrim(trim($language->sL(BackendUtility::getItemLabel($table, $fCol))), ':')
                                );
                                $headerColumns[$fCol] .= '<a href="#" onclick="' . htmlspecialchars(
                                    BackendUtility::editOnClick($params, '', -1)
                                ) . '" title="' . htmlspecialchars($iTitle) . '">' .
                                    $iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL) . '</a>';
                            }
                        }
                        $headerColumns[$fCol] .= $parentObject->addSortLink(
                            $language->sL(
                                BackendUtility::getItemLabel(
                                    $table,
                                    $fCol,
                                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xml:|'
                                )
                            ),
                            $fCol,
                            $table
                        );
                }
            }
        }

        return $headerColumns;
    }

    /**
     * Modifies Web>List header row clipboard/action icons
     * just to satisfy interface.
     *
     * @param string $table The current database table
     * @param array $currentIdList Array of the currently displayed uids of the table
     * @param array $cells An array of the current clipboard/action icons
     * @param object $parentObject Instance of calling (parent) object
     *
     * @return array Array of modified clipboard/action icons
     */
    public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject)
    {
        return $cells;
    }


    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get controller.
     *
     * @return \CommerceTeam\Commerce\Controller\CategoryModuleController
     */
    protected function getController()
    {
        return $GLOBALS['SOBE'];
    }
}
