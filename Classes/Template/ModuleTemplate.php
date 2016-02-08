<?php
namespace CommerceTeam\Commerce\Template;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use CommerceTeam\Commerce\Template\Components\DocHeaderComponent;

class ModuleTemplate extends \TYPO3\CMS\Backend\Template\ModuleTemplate
{
    /**
     * DocHeaderComponent
     *
     * @var DocHeaderComponent
     */
    protected $docHeaderComponent;

    /**
     * Class constructor
     * Sets up view and property objects
     */
    public function __construct()
    {
        parent::__construct();
        $this->docHeaderComponent = GeneralUtility::makeInstance(DocHeaderComponent::class);
    }
}
