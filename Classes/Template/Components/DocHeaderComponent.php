<?php
namespace CommerceTeam\Commerce\Template\Components;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class DocHeaderComponent extends \TYPO3\CMS\Backend\Template\Components\DocHeaderComponent
{
    /**
     * Sets up buttonBar and MenuRegistry
     */
    public function __construct()
    {
        parent::__construct();
        $this->metaInformation = GeneralUtility::makeInstance(MetaInformation::class);
    }
}
