<?php
namespace CommerceTeam\Commerce\Xclass;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class NewRecordController.
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class NewRecordController extends \TYPO3\CMS\Backend\Controller\NewRecordController
{
    /**
     * Main processing, creating the list of new record tables to select from.
     *
     * @return void
     */
    public function main()
    {
        // if commerce parameter is missing use default controller
        if (!GeneralUtility::_GP('parentCategory')) {
            parent::main();

            return;
        }

        // If there was a page - or if the user is admin
        // (admins has access to the root) we proceed:
        if ($this->pageinfo['uid'] || $this->getBackendUserAuthentication()->isAdmin()) {
            if (empty($this->pageinfo)) {
                // Explicitly pass an empty array to the docHeader
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
            } else {
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
            }
            // Acquiring TSconfig for this module/current page:
            $this->web_list_modTSconfig = BackendUtility::getModTSconfig((int) $this->pageinfo['uid'], 'mod.web_list');

            // allow only commerce related tables
            $this->allowedNewTables = array(
                'tx_commerce_categories',
                'tx_commerce_products'
            );

            $this->deniedNewTables = GeneralUtility::trimExplode(
                ',',
                $this->web_list_modTSconfig['properties']['deniedNewTables'],
                true
            );
            // Acquiring TSconfig for this module/parent page:
            $this->web_list_modTSconfig_pid = BackendUtility::getModTSconfig(
                (int) $this->pageinfo['pid'],
                'mod.web_list'
            );
            $this->allowedNewTables_pid = GeneralUtility::trimExplode(
                ',',
                $this->web_list_modTSconfig_pid['properties']['allowedNewTables'],
                true
            );
            $this->deniedNewTables_pid = GeneralUtility::trimExplode(
                ',',
                $this->web_list_modTSconfig_pid['properties']['deniedNewTables'],
                true
            );
            // More init:
            if (!$this->showNewRecLink('pages')) {
                $this->newPagesInto = 0;
            }
            if (!$this->showNewRecLink('pages', $this->allowedNewTables_pid, $this->deniedNewTables_pid)) {
                $this->newPagesAfter = 0;
            }
            // Set header-HTML and return_url
            if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
                $title = strip_tags($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
            } else {
                $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            }
            $this->moduleTemplate->setTitle($title);
            // GENERATE the HTML-output depending on mode (pagesOnly is the page wizard)
            // Regular new element:
            if (!$this->pagesOnly) {
                $this->regularNew();
            } elseif ($this->showNewRecLink('pages')) {
                // Pages only wizard
                $this->pagesOnly();
            }
            // Add all the content to an output section
            $this->content .= '<div>' . $this->code . '</div>';
            // Setting up the buttons and markers for docheader
            $this->getButtons();
            // Build the <body> for the module
            $this->moduleTemplate->setContent($this->content);
        }
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
        $parameters = '&edit[' . $table . '][' . $pid . ']=new';

        $contentTable = $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'];
        $contentTableConfig = ConfigurationUtility::getInstance()->getTcaValue($contentTable);
        if ($table == 'pages'
            && $contentTable
            && $contentTableConfig
            && $addContentTable) {
            $parameters .= '&edit[' . $contentTable . '][prev]=new&returnNewPageId=1';
        } elseif ($table == 'pages_language_overlay') {
            $parameters .= '&overrideVals[pages_language_overlay][doktype]=' . (int) $this->pageinfo['doktype'];
        }

        $parameters = $this->addCommerceParameter($parameters, $table);
        $onClick = BackendUtility::editOnClick($parameters, '', $this->returnUrl);

        return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $linkText . '</a>';
    }

    /**
     * Add commerce parameters.
     *
     * @param string $parameters Parameters
     * @param string $table Table
     *
     * @return string
     */
    protected function addCommerceParameter($parameters, $table)
    {
        if (GeneralUtility::_GP('parentCategory')) {
            switch ($table) {
                case 'tx_commerce_categories':
                    $parameters .= '&defVals[tx_commerce_categories][parent_category]=' .
                        GeneralUtility::_GP('parentCategory');
                    break;

                case 'tx_commerce_products':
                    $parameters .= '&defVals[tx_commerce_products][categories]=' .
                        GeneralUtility::_GP('parentCategory');
                    break;

                default:
            }
        }

        return $parameters;
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
}
