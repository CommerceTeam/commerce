<?php
namespace CommerceTeam\Commerce\Hook;

use TYPO3\CMS\Backend\Controller\BackendController;

/**
 * Class \CommerceTeam\Commerce\Hook\BackendHooks
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class BackendHooks
{
    /**
     * @param array $configuration Configuration
     * @param BackendController $parent Parent controller
     *
     * @return void
     */
    public function addJsFiles(array $configuration, &$parent)
    {
        $parent->addJavascriptFile('../typo3conf/ext/commerce/Resources/Public/JavaScript/extjs/components/pagetree/javascript/tree.js');
    }
}
