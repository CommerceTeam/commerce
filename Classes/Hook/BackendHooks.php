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
     * Add javascript to be loaded in backend
     *
     * @param array $configuration Configuration
     * @param BackendController $parent Parent controller
     *
     * @return void
     */
    public function addJsFiles(array $configuration, &$parent)
    {
        $extensionPath = '../typo3conf/ext/commerce/';
        $parent->addJavascriptFile(
            $extensionPath . 'Resources/Public/JavaScript/extjs/components/systemdatanavframe/javascript/tree.js'
        );
    }
}
