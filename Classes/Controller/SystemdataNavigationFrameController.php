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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main script class for the systemData navigation frame.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\Navigation\SystemdataViewHelper
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class SystemdataNavigationFrameController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * Has filter box.
     *
     * @var bool
     */
    protected $hasFilterBox = false;

    /**
     * Constructor
     *
     * @return self
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml'
        );

        $this->id = reset(\CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders(
            'Commerce',
            'commerce'
        ));
    }

    /**
     * Initializes the Page.
     *
     * @return void
     */
    public function initPage()
    {
        /**
         * Document template.
         *
         * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
         */
        $doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $this->doc = $doc;
        $this->doc->backPath = $this->getBackPath();
        $this->doc->setModuleTemplate('EXT:commerce/Resources/Private/Backend/mod_systemdata_navigation.html');
        $this->doc->showFlashMessages = false;

        $this->doc->JScode = $this->doc->wrapScriptTags('
            function jumpTo(func, linkObj) {
                var theUrl = top.TS.PATH_typo3 + top.currentSubScript + "&SET[function]=" + func;

                if (top.condensedMode) {
                    top.content.document.location = theUrl;
                } else {
                    parent.list_frame.document.location = theUrl;
                }

                ' . (!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}') . '

                return false;
            }
        ');

        $this->doc->postCode = $this->doc->wrapScriptTags('
            script_ended = 1;
            if (top.fsMod) {
                top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
            }
        ');

        $this->doc->bodyTagId = 'systemdata-navframe';
    }

    /**
     * Main method.
     *
     * @return void
     */
    public function main()
    {
        $docHeaderButtons = $this->getButtons();

        $markers = array(
            'ATTRIBUTES_TITLE' => $this->getLanguageService()->getLL('title_attributes'),
            'ATTRIBUTES_DESCRIPTION' => $this->getLanguageService()->getLL('desc_attributes'),

            'MANUFACTURER_TITLE' => $this->getLanguageService()->getLL('title_manufacturer'),
            'MANUFACTURER_DESCRIPTION' => $this->getLanguageService()->getLL('desc_manufacturer'),

            'SUPPLIER_TITLE' => $this->getLanguageService()->getLL('title_supplier'),
            'SUPPLIER_DESCRIPTION' => $this->getLanguageService()->getLL('desc_supplier'),
        );

        $subparts = array();
        if (!$this->hasFilterBox) {
            $subparts['###SECOND_ROW###'] = '';
        }

        // put it all together
        $this->content = $this->doc->startPage(
            $this->getLanguageService()->sl(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:mod_category.navigation_title'
            )
        );
        $this->content .= $this->doc->moduleBody('', $docHeaderButtons, $markers, $subparts);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);
    }

    /**
     * Outputting the accumulated content to screen.
     *
     * @return void
     */
    public function printContent()
    {
        echo $this->content;
    }

    /**
     * Create the panel of buttons for submitting the
     * form or otherwise perform operations.
     *
     * @return array all available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttons = array(
            'csh' => '',
            'refresh' => '',
        );

        // Refresh
        $buttons['refresh'] = '';

        // CSH
        $buttons['csh'] = str_replace(
            'typo3-csh-inline',
            'typo3-csh-inline show-right',
            BackendUtility::cshItem('xMOD_csh_commercebe', 'systemdata', $this->getBackPath())
        );

        return $buttons;
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
