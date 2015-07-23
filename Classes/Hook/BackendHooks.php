<?php
namespace CommerceTeam\Commerce\Hook;

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $extensionPath = '../typo3conf/ext/commerce/';
        /*$parent->addJavascriptFile(
            '../typo3conf/ext/commerce/Resources/Public/JavaScript/extjs/components/categorytree/javascript/tree.js'
        );
        $parent->addJavascriptFile(
            '../typo3conf/ext/commerce/Resources/Public/JavaScript/extjs/components/ordertree/javascript/tree.js'
        );*/
        $parent->addJavascriptFile(
            $extensionPath . 'Resources/Public/JavaScript/extjs/components/systemdatanavframe/javascript/tree.js'
        );
    }
}
