<?php
namespace CommerceTeam\Commerce\Controller;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use CommerceTeam\Commerce\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Controller\WizardController.
 *
 * @author 2008-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class WizardController
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Page info.
     *
     * @var array
     */
    public $pageinfo = array();

    /**
     * Pid info.
     *
     * @var array
     */
    public $pidInfo = array();

    /**
     * New content into.
     *
     * @var int
     */
    public $newContentInto;

    /**
     * Web list module configuration.
     *
     * @var array
     */
    public $web_list_modTSconfig = array();

    /**
     * Web list module configuration pid.
     *
     * @var array
     */
    public $web_list_modTSconfig_pid = array();

    /**
     * Allowed new tables.
     *
     * @var array
     */
    public $allowedNewTables = array();

    /**
     * Allowed new tables pid.
     *
     * @var array
     */
    public $allowedNewTables_pid = array();

    /**
     * Code.
     *
     * @var string
     */
    public $code = '';

    /**
     * Uid.
     *
     * @var int
     */
    protected $id;

    /**
     * Return url.
     *
     * @var string
     */
    protected $returnUrl = '';

    /**
     * PagesOnly flag.
     *
     * @var bool
     */
    protected $pagesOnly;

    /**
     * Permisson clause.
     *
     * @var string
     */
    protected $permsClause;

    /**
     * Document template.
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Accumulated HTML output.
     *
     * @var string
     */
    protected $content = '';

    /**
     * Head.
     *
     * @var string
     */
    protected $head = '';

    /**
     * Parameter.
     *
     * @var array
     */
    protected $param;

    /**
     * Default values to be used.
     *
     * @var array
     */
    protected $defVals;

    /**
     * WizardController constructor.
     *
     * @return self
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Constructor function for the class.
     *
     * @return void
     */
    public function init()
    {
        $backendUser = $this->getBackendUser();
        $language = $this->getLanguageService();

        // page-selection permission clause (reading)
        $this->permsClause = $backendUser->getPagePermsClause(1);

        // Setting GPvars:
        // The page id to operate from
        $this->id = GeneralUtility::_GP('id') ? (int) GeneralUtility::_GP('id') : BackendUtility::getProductFolderUid();
        $this->returnUrl = GeneralUtility::_GP('returnUrl');

        // this to be accomplished from the caller: &edit['.$table.'][-'.$uid.']=new&
        $this->param = GeneralUtility::_GP('edit');
        $this->defVals = GeneralUtility::_GP('defVals');

        // Create instance of template class for output
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->JScode = '';

        $this->head = $language->getLL('newRecordGeneral', 1);

        // Creating content
        $this->content = '';
        $this->content .= $this->doc->startPage($this->head);
        $this->content .= $this->doc->header($this->head);

        // Id a positive id is supplied, ask for the page record
        // with permission information contained:
        if ($this->id > 0) {
            $this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->permsClause);
        }

        // If a page-record was returned, the user had read-access to the page.
        if ($this->pageinfo['uid']) {
            // Get record of parent page
            $this->pidInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(
                'pages',
                (int) $this->pageinfo['pid']
            );
            // Checking the permissions for the user with regard to the
            // parent page: Can he create new pages, new content record, new page after?
            if ($backendUser->doesUserHaveAccess($this->pageinfo, 16)) {
                $this->newContentInto = 1;
            }
        } elseif ($backendUser->isAdmin()) {
            // Admins can do it all
            $this->newContentInto = 1;
        } else {
            // People with no permission can do nothing
            $this->newContentInto = 0;
        }
    }

    /**
     * Main processing, creating the list of new record tables to select from.
     *
     * @return void
     */
    public function main()
    {
        $backendUser = $this->getBackendUser();
        $language = $this->getLanguageService();

        // If there was a page - or if the user is admin
        // (admins has access to the root) we proceed:
        if ($this->pageinfo['uid'] || $backendUser->isAdmin()) {
            // Acquiring TSconfig for this module/current page:
            $this->web_list_modTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig(
                (int) $this->pageinfo['uid'],
                'mod.web_list'
            );
            $this->allowedNewTables = GeneralUtility::trimExplode(
                ',',
                $this->web_list_modTSconfig['properties']['allowedNewTables'],
                1
            );

                // Acquiring TSconfig for this module/parent page:
            $this->web_list_modTSconfig_pid = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig(
                (int) $this->pageinfo['pid'],
                'mod.web_list'
            );
            $this->allowedNewTables_pid = GeneralUtility::trimExplode(
                ',',
                $this->web_list_modTSconfig_pid['properties']['allowedNewTables'],
                1
            );

                // Set header-HTML and return_url
            $this->code = $this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']) . '<br />';

            $this->regularNew();

                // Create go-back link.
            if ($this->returnUrl) {
                $this->code .= '<br />
                    <a href="' . htmlspecialchars($this->returnUrl) . '" class="typo3-goBack" title="' .
                        $language->getLL('goBack', 1) . '">'.
                        $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL) .
                    '</a>';
            }
                // Add all the content to an output section
            $this->content .= $this->doc->section('', $this->code);
        }
        $this->content .= $this->doc->endPage();
    }

    /**
     * Ending page output and echo'ing content to browser.
     *
     * @return void
     */
    public function printContent()
    {
        echo $this->content;
    }

    /**
     * Create a regular new element (pages and records).
     *
     * @return void
     */
    protected function regularNew()
    {
        $backendUser = $this->getBackendUser();
        $language = $this->getLanguageService();

        // Slight spacer from header:
        // @todo test with x-tree-icon alternativly
        $this->code .= '<span class="x-tree-elbow-line"></span><br />';

        // New tables INSIDE this category
        foreach ($this->param as $table => $param) {
            if ($this->showNewRecLink($table)
                && $this->isTableAllowedForThisPage($this->pageinfo, $table)
                && $backendUser->check('tables_modify', $table)
                && ($param['ctrl']['rootLevel'] xor $this->id || $param['ctrl']['rootLevel'] == -1)
            ) {
                $val = key($param);
                $cmd = ($param[$val]);
                switch ($cmd) {
                    case 'new':
                        // Create new link for record:
                        $rowContent = '<span class="x-tree-ec-icon x-tree-elbow"></span>' .
                        $this->linkWrap(
                            $this->iconFactory->getIconForRecord($table, array(), Icon::SIZE_SMALL) .
                            $language->sL(ConfigurationUtility::getInstance()->getTcaValue($table . '.ctrl.title'), 1),
                            $table,
                            $this->id
                        );

                        // Compile table row:
                        $tRows[] = '
                <tr>
                    <td nowrap="nowrap">' . $rowContent . '</td>
                    <td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($table, '', $this->getBackPath(), '')
                            . '</td>
                </tr>
                ';
                        break;

                    default:
                }
            }
        }

        // Compile table row:
        $tRows[] = '
			<tr>
				<td><span class="x-tree-lines x-tree-elbow-end"></span></td>
				<td></td>
			</tr>
		';

        // Make table:
        $this->code .= '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-newRecord">
			' . implode('', $tRows) . '
			</table>
		';

        // Add CSH:
        $this->code .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem(
            'xMOD_csh_corebe',
            'new_regular',
            $this->getBackPath(),
            '<br/>'
        );
    }

    /**
     * Links the string $code to a create-new form for a record
     * in $table created on page $pid.
     *
     * @param string $code Link string
     * @param string $table Table name (in which to create new record)
     * @param int $pid PID value for the
     *      "&edit['.$table.']['.$pid.']=new" command (positive/negative)
     *
     * @return string The link.
     */
    protected function linkWrap($code, $table, $pid)
    {
        $params = '&edit[' . $table . '][' . $pid . ']=new' . $this->compileDefVals($table);
        $onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(
            $params,
            $this->getBackPath(),
            $this->returnUrl
        );

        return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $code . '</a>';
    }

    /**
     * Compile def values.
     *
     * @param string $table Table
     *
     * @return string
     */
    protected function compileDefVals($table)
    {
        $data = GeneralUtility::_GP('defVals');
        if (is_array($data[$table])) {
            $result = '';
            foreach ($data[$table] as $key => $value) {
                $result .= '&defVals[' . $table . '][' . $key . ']=' . urlencode($value);
            }
        } else {
            $result = '';
        }

        return $result;
    }

    /**
     * Returns true if the tablename $checkTable is allowed to be created
     * on the page with record $row.
     *
     * @param array $row Record for parent page.
     * @param string $checkTable Table name to check
     *
     * @return bool Returns true if the tablename $checkTable is allowed
     *      to be created on the page with record $row
     */
    protected function isTableAllowedForThisPage(array $row, $checkTable)
    {
        $result = false;

        if (!is_array($row)) {
            if ($this->getBackendUser()->isAdmin()) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            // be_users and be_groups may not be created anywhere but in the root.
            if ($checkTable == 'be_users' || $checkTable == 'be_groups') {
                $result = false;
            } else {
                // Checking doktype:
                $doktype = (int) $row['doktype'];
                if (!($allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'])) {
                    $allowedTableList = $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
                }

                    // If all tables or the table is listed as a allowed type, return true
                if (strstr($allowedTableList, '*') || GeneralUtility::inList($allowedTableList, $checkTable)) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Returns true if the $table tablename is found in $allowedNewTables
     * (or if $allowedNewTables is empty).
     *
     * @param string $table Table name to test if in allowedTables
     * @param array $allowedNewTables Array of new tables that are allowed.
     *
     * @return bool Returns true if the $table tablename is found in
     *      $allowedNewTables (or if $allowedNewTables is empty)
     */
    protected function showNewRecLink($table, array $allowedNewTables = array())
    {
        $allowedNewTables = is_array($allowedNewTables) ? $allowedNewTables : $this->allowedNewTables;

        return empty($allowedNewTables) || in_array($table, $allowedNewTables);
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
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
     * Get back path.
     *
     * @return string
     */
    protected function getBackPath()
    {
        return $GLOBALS['BACK_PATH'];
    }
}
