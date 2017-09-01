<?php
namespace CommerceTeam\Commerce\Xclass;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class NewRecordController
 */
class NewRecordController extends \TYPO3\CMS\Backend\Controller\NewRecordController
{
    /**
     * Create a regular new element (pages and records)
     */
    public function regularNew()
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $defaultValue = GeneralUtility::_GP('defVals');
        if (!is_array($defaultValue)
            || !isset($defaultValue['tx_commerce_categories'])
            || !isset($defaultValue['tx_commerce_categories']['uid'])) {
            parent::regularNew();
            return;
        }
        $this->newPagesInto = 1;
        $this->newPagesAfter = 1;

        $this->pageinfo = $categoryRepository->findByUid($defaultValue['tx_commerce_categories']['uid']);

        // needed for table allowed checks
        $this->pageinfo['doktype'] = 254;

        $lang = $this->getLanguageService();
        // load additional language file
        $lang->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_newwizard.xlf');
        // Initialize array for accumulating table rows:
        $this->tRows = [];
        // Get TSconfig for current page
        $pageTS = BackendUtility::getPagesTSconfig($this->id);
        // Finish initializing new pages options with TSconfig
        // Each new page option may be hidden by TSconfig
        // Enabled option for the position of a new page
        $this->newPagesSelectPosition = !empty(
            $pageTS['mod.']['wizards.']['newRecord.']['tx_commerce_categories.']['show.']['pageSelectPosition']
        );
        // Pseudo-boolean (0/1) for backward compatibility
        $displayNewPagesIntoLink = $this->newPagesInto
            && !empty($pageTS['mod.']['wizards.']['newRecord.']['tx_commerce_categories.']['show.']['pageInside']);
        $displayNewPagesAfterLink = $this->newPagesAfter
            && !empty($pageTS['mod.']['wizards.']['newRecord.']['tx_commerce_categories.']['show.']['pageAfter']);

        // Slight spacer from header:
        $this->code .= '';

        // New Page
        $table = 'tx_commerce_categories';
        $v = $GLOBALS['TCA'][$table];
        $pageIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord(
            $table,
            [],
            Icon::SIZE_SMALL
        )->render();
        $newPageIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-add', Icon::SIZE_SMALL)->render();
        $rowContent = '';
        // New pages INSIDE this pages
        $newPageLinks = [];
        if ($displayNewPagesIntoLink
            && $this->isTableAllowedForThisPage($this->pageinfo, 'tx_commerce_categories')
            && $this->getBackendUserAuthentication()->check('tables_modify', 'tx_commerce_categories')
            && $this->getBackendUserAuthentication()->workspaceCreateNewRecord(
                ($this->pageinfo['_ORIG_uid'] ?: $this->id),
                'tx_commerce_categories'
            )
        ) {
            // Create link to new page inside:
            $newPageLinks[] = $this->linkWrap(
                $this->moduleTemplate->getIconFactory()->getIconForRecord($table, [], Icon::SIZE_SMALL)->render()
                . htmlspecialchars($lang->sL($v['ctrl']['title'])) . ' ('
                . htmlspecialchars($lang->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:db_new.php.inside'
                ))
                . ')',
                $table,
                $this->id
            );
        }
        // New pages AFTER this pages
        if ($displayNewPagesAfterLink
            && $this->isTableAllowedForThisPage($this->pidInfo, 'tx_commerce_categories')
            && $this->getBackendUserAuthentication()->check('tables_modify', 'tx_commerce_categories')
            && $this->getBackendUserAuthentication()->workspaceCreateNewRecord(
                (int) $this->pidInfo['uid'],
                'tx_commerce_categories'
            )
        ) {
            // we need to override the value so better save it
            $backup = (int) $this->pageinfo['uid'];
            // get parent category of current category to create new category on same level
            $this->pageinfo['uid'] = $categoryRepository->getParentCategory($backup);

            $newPageLinks[] = $this->linkWrap(
                $pageIcon . htmlspecialchars($lang->sL($v['ctrl']['title'])) . ' ('
                . htmlspecialchars($lang->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:db_new.php.after'
                ))
                . ')',
                'tx_commerce_categories',
                $this->pageinfo['pid']
            );

            // restore previous value
            $this->pageinfo['uid'] = $backup;
        }
        // New pages at selection position
        if ($this->newPagesSelectPosition && $this->showNewRecLink('tx_commerce_categories')) {
            // Link to page-wizard:
            $newPageLinks[] = '<a href="'
                . htmlspecialchars(GeneralUtility::linkThisScript(['pagesOnly' => 1])) . '">' . $pageIcon
                . htmlspecialchars($lang->getLL('categorySelectPosition')) . '</a>';
        }
        // Assemble all new page links
        $numPageLinks = count($newPageLinks);
        for ($i = 0; $i < $numPageLinks; $i++) {
            $rowContent .= '<li>' . $newPageLinks[$i] . '</li>';
        }
        if ($this->showNewRecLink('tx_commerce_categories')) {
            $rowContent = '<ul class="list-tree"><li>' . $newPageIcon . '<strong>' .
                $lang->getLL('createNewCategory') . '</strong><ul>' . $rowContent . '</ul></li>';
        } else {
            $rowContent = '<ul class="list-tree"><li><ul>' . $rowContent . '</li></ul>';
        }
        // Compile table row
        $startRows = [$rowContent];
        $iconFile = [];
        // New tables (but not pages) INSIDE this pages
        $isAdmin = $this->getBackendUserAuthentication()->isAdmin();
        $newContentIcon = $this->moduleTemplate->getIconFactory()->getIcon(
            'actions-document-new',
            Icon::SIZE_SMALL
        )->render();
        if ($this->newContentInto) {
            if (is_array($GLOBALS['TCA'])) {
                $groupName = '';
                foreach ($GLOBALS['TCA'] as $table => $v) {
                    if ($table != 'tx_commerce_categories'
                        && $this->showNewRecLink($table)
                        && $this->isTableAllowedForThisPage($this->pageinfo, $table)
                        && $this->getBackendUserAuthentication()->check('tables_modify', $table)
                        && (($v['ctrl']['rootLevel'] xor $this->id) || $v['ctrl']['rootLevel'] == -1)
                        && $this->getBackendUserAuthentication()->workspaceCreateNewRecord(
                            ($this->pageinfo['_ORIG_uid'] ? $this->pageinfo['_ORIG_uid'] : $this->id),
                            $table
                        )
                    ) {
                        $newRecordIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord(
                            $table,
                            [],
                            Icon::SIZE_SMALL
                        )->render();
                        $rowContent = '';
                        $thisTitle = '';
                        // Create new link for record:
                        $newLink = $this->linkWrap(
                            $newRecordIcon . htmlspecialchars($lang->sL($v['ctrl']['title'])),
                            $table,
                            $this->id
                        );
                        // If the table is 'tt_content', create link to wizard
                        if ($table == 'tt_content') {
                            $groupName = $lang->getLL('createNewContent');
                            $rowContent = $newContentIcon . '<strong>' . $lang->getLL('createNewContent')
                                . '</strong><ul>';
                            // If mod.newContentElementWizard.override is set, use that extension's wizard instead:
                            $tsConfig = BackendUtility::getModTSconfig($this->id, 'mod');
                            $moduleName = isset($tsConfig['properties']['newContentElementWizard.']['override'])
                                ? $tsConfig['properties']['newContentElementWizard.']['override']
                                : 'new_content_element';
                            $url = BackendUtility::getModuleUrl(
                                $moduleName,
                                [
                                    'id' => $this->id,
                                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                                ]
                            );
                            $rowContent .= '<li>' . $newLink . ' ' . BackendUtility::wrapInHelp($table, '')
                                . '</li><li><a href="' . htmlspecialchars($url) . '">' . $newContentIcon
                                . htmlspecialchars($lang->getLL('clickForWizard')) . '</a></li></ul>';
                        } else {
                            // Get the title
                            if ($v['ctrl']['readOnly'] || $v['ctrl']['hideTable'] || $v['ctrl']['is_static']) {
                                continue;
                            }
                            if ($v['ctrl']['adminOnly'] && !$isAdmin) {
                                continue;
                            }
                            $nameParts = explode('_', $table);
                            $thisTitle = '';
                            $_EXTKEY = '';
                            if ($nameParts[0] == 'tx' || $nameParts[0] == 'tt') {
                                // Try to extract extension name
                                if (substr($v['ctrl']['title'], 0, 8) == 'LLL:EXT:') {
                                    $_EXTKEY = substr($v['ctrl']['title'], 8);
                                    $_EXTKEY = substr($_EXTKEY, 0, strpos($_EXTKEY, '/'));
                                    if ($_EXTKEY != '') {
                                        // First try to get localisation of extension title
                                        $temp = explode(':', substr($v['ctrl']['title'], 9 + strlen($_EXTKEY)));
                                        $langFile = $temp[0];
                                        $thisTitle = $lang->sL(
                                            'LLL:EXT:' . $_EXTKEY . '/' . $langFile . ':extension.title'
                                        );
                                        // If no localisation available, read title from ext_emconf.php
                                        $extPath = ExtensionManagementUtility::extPath($_EXTKEY);
                                        $extEmConfFile = $extPath . 'ext_emconf.php';
                                        if (!$thisTitle && is_file($extEmConfFile)) {
                                            $EM_CONF = [];
                                            include $extEmConfFile;
                                            $thisTitle = $EM_CONF[$_EXTKEY]['title'];
                                        }
                                        $iconFile[$_EXTKEY] = '<img ' . 'src="'
                                            . PathUtility::getAbsoluteWebPath(
                                                ExtensionManagementUtility::getExtensionIcon($extPath, true)
                                            )
                                            . '" ' . 'width="16" height="16" ' . 'alt="' . $thisTitle . '" />';
                                    }
                                }
                                if (empty($thisTitle)) {
                                    $_EXTKEY = $nameParts[1];
                                    $thisTitle = $nameParts[1];
                                    $iconFile[$_EXTKEY] = '';
                                }
                            } else {
                                if ($table === 'pages_language_overlay' && !$this->checkIfLanguagesExist()) {
                                    continue;
                                }
                                $_EXTKEY = 'system';
                                $thisTitle = $lang->getLL('system_records');
                                $iconFile['system'] = $this->moduleTemplate->getIconFactory()->getIcon(
                                    'apps-pagetree-root',
                                    Icon::SIZE_SMALL
                                )->render();
                            }
                            if ($groupName == '' || $groupName != $_EXTKEY) {
                                $groupName = empty($v['ctrl']['groupName']) ? $_EXTKEY : $v['ctrl']['groupName'];
                            }
                            $rowContent .= $newLink;
                        }
                        // Compile table row:
                        if ($table == 'tt_content') {
                            $startRows[] = '<li>' . $rowContent . '</li>';
                        } else {
                            $this->tRows[$groupName]['title'] = $thisTitle;
                            $this->tRows[$groupName]['html'][] = $rowContent;
                            $this->tRows[$groupName]['table'][] = $table;
                        }
                    }
                }
            }
        }
        // User sort
        if (isset($pageTS['mod.']['wizards.']['newRecord.']['order'])) {
            $this->newRecordSortList = GeneralUtility::trimExplode(
                ',',
                $pageTS['mod.']['wizards.']['newRecord.']['order'],
                true
            );
        }
        uksort($this->tRows, [$this, 'sortNewRecordsByConfig']);
        // Compile table row:
        $finalRows = [];
        $finalRows[] = implode('', $startRows);
        foreach ($this->tRows as $key => $value) {
            $row = '<li>' . $iconFile[$key] . ' <strong>' . $value['title'] . '</strong><ul>';
            foreach ($value['html'] as $recordKey => $record) {
                $row .= '<li>' . $record . ' ' . BackendUtility::wrapInHelp($value['table'][$recordKey], '') . '</li>';
            }
            $row .= '</ul></li>';
            $finalRows[] = $row;
        }

        $finalRows[] = '</ul>';
        // Make table:
        $this->code .= implode('', $finalRows);
    }

    /**
     * Links the string $code to a create-new form for a record
     * in $table created on page $pid.
     *
     * @param string $linkText Link text
     * @param string $table Table name (in which to create new record)
     * @param int $pid PID value for the
     *      "&edit['.$table.']['.$pid.']=new" command (positive/negative)
     * @param bool $addContentTable If $addContentTable is set,
     *      then a new contentTable record is created together with pages
     *
     * @return string The link.
     */
    public function linkWrap($linkText, $table, $pid, $addContentTable = false)
    {
        $urlParameters = [
            'edit' => [
                $table => [
                    $pid => 'new'
                ]
            ],
            'returnUrl' => $this->returnUrl
        ];
        if ($table == 'pages' && $addContentTable) {
            $urlParameters['tt_content']['prev'] = 'new';
            $urlParameters['returnNewPageId'] = 1;
        } elseif ($table == 'pages_language_overlay') {
            $urlParameters['overrideVals']['pages_language_overlay']['doktype'] = (int)$this->pageinfo['doktype'];
        }

        $urlParameters = $this->addCommerceParameter($urlParameters, $table);

        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
        return '<a href="' . htmlspecialchars($url) . '">' . $linkText . '</a>';
    }

    /**
     * Add commerce parameters.
     *
     * @param array $urlParameters Parameters
     * @param string $table Table
     *
     * @return array
     */
    protected function addCommerceParameter($urlParameters, $table)
    {
        if ($table == 'tx_commerce_categories') {
            $urlParameters['defVals'][$table]['parent_category'] = $this->pageinfo['uid'];
        } elseif ($table == 'tx_commerce_products') {
            $urlParameters['defVals'][$table]['categories'] = [$this->pageinfo['uid']];
        }

        return $urlParameters;
    }
}
