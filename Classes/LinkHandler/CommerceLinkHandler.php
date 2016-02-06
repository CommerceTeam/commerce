<?php
namespace CommerceTeam\Commerce\LinkHandler;

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
use CommerceTeam\Commerce\Tree\View\ElementBrowserCategoryTreeView;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Class \CommerceTeam\Commerce\Hook\LinkhandlerHooks.
 *
 * @author 2008-2009 Ingo Schmitt <is@marketing-factory.de>
 */
class CommerceLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * @var int Category id to shop products from
     */
    protected $expandCategory;

    /**
     * Parts of the current link
     *
     * @var array
     */
    protected $linkParts = [];

    /**
     * CommerceLinkHandler constructor.
     *
     * @return self
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_be.xml');
    }

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (!$linkParts['url']) {
            return false;
        }

        $url = $linkParts['url'];

        // @deprecated Remove these replacements in Version 6 they are only for compatibility
        $url = str_replace('commerce:', 'commerce|', $url);
        $url = str_replace('tx_commerce_categories', 'c', $url);
        $url = str_replace('tx_commerce_products', 'p', $url);

        $parts = explode('|', $url);
        if ($parts[0] != 'commerce' || count($parts) < 2) {
            return false;
        }

        $this->linkParts = $linkParts;

        for ($i = 1; $i < count($parts); $i++) {
            if (strpos($parts[$i], 'c') !== false) {
                $categoryParts = explode(':', $parts[$i]);
                $this->linkParts['category'] = (int)$categoryParts[1];
            }

            if (strpos($parts[$i], 'p') !== false) {
                $productParts = explode(':', $parts[$i]);
                $this->linkParts['product'] = (int)$productParts[1];
            }
        }

        return true;
    }

    /**
     * @param array $values Values to be checked
     *
     * @return bool Returns true if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return !empty($this->linkParts) && (int)$this->linkParts['category'] === (int)$values['pid'];
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->linkBrowser->getScriptUrl();
    }

    /**
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $parameters = [
            'expandCategory' => isset($values['pid']) ? (int)$values['pid'] : $this->expandCategory
        ];
        return array_merge($this->linkBrowser->getUrlParameters($values), $parameters);
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        if (empty($this->linkParts)) {
            return [];
        }
        return [
            'data-current-link' => 'commerce'
                . ($this->linkParts['category'] > 0 ? '|c:' . $this->linkParts['category'] : '')
                . ($this->linkParts['product'] > 0 ? '|p:' . $this->linkParts['product'] : '')
        ];
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        $lang = $this->getLanguageService();
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];

        $title = '';
        $path = '';
        if ($this->linkParts['category']) {
            $id = (int)$this->linkParts['category'];
            $categoryRow = BackendUtility::getRecordWSOL('tx_commerce_categories', $id);

            $title = $lang->getLL('tx_commerce_categories', true);
            $path .= ' \'' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($categoryRow['title'], $titleLen)) . '\''
                . ' (ID:' . $id . ')';
        }

        if ($this->linkParts['product']) {
            $id = (int)$this->linkParts['product'];
            $productRow = BackendUtility::getRecordWSOL('tx_commerce_products', $id);

            $title = $lang->getLL('tx_commerce_products', true);
            $path .= ' \'' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($productRow['title'], $titleLen)) . '\''
                . ' (ID:' . $id . ')';
        }

        return $title . $path;
    }

    /**
     * Render the link handler
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/PageLinkHandler');

        $this->expandCategory = isset($request->getQueryParams()['expandCategory']) ?
            (int)$request->getQueryParams()['expandCategory'] :
            0;

        $backendUser = $this->getBackendUser();

        /** @var ElementBrowserCategoryTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance(ElementBrowserCategoryTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $pageTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $pageTree->addField('nav_title');
        $tree = $pageTree->getBrowsableTree();

        return '

				<!--
					Wrapper table for page tree / record list:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
					<tr>
						<td class="c-wCell" valign="top"><h3>'
            . $this->getLanguageService()->getLL('linkhandler.category_tree')
            . ':</h3>'
            . $tree . '</td>
						<td class="c-wCell" valign="top">' . $this->expandCategory($this->expandCategory) . '</td>
					</tr>
				</table>';
    }

    /**
     * This displays all products in a category and lets you create a link to the product.
     *
     * @param int $expCategoryId Category uid to expand
     *
     * @return string HTML output. Returns content only if the ->expandCategory value is set
     *  (pointing to a page uid to show tt_content records from ...)
     */
    public function expandCategory($expCategoryId)
    {
        // If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
        if (!$expCategoryId && isset($this->linkParts['product'])) {
            // Set to the current link page id.
            $expCategoryId = $this->linkParts['category'];
        }
        // Draw the record list IF there is a page id to expand:
        if (!$expCategoryId
            || !MathUtility::canBeInterpretedAsInteger($expCategoryId)
            || !$this->getBackendUser()->isInWebMount($expCategoryId)) {
            return '';
        }

        // Set header:
        $out = '<h3>' . $this->getLanguageService()->getLL('linkhandler.products') . ':</h3>';
        // Create header for listing, showing the page title/icon:
        $mainPageRec = BackendUtility::getRecordWSOL('tx_commerce_categories', $expCategoryId);
        $database = $this->getDatabaseConnection();
        $out .= '
			<ul class="list-tree list-tree-root list-tree-root-clean">
				<li class="list-tree-control-open">
					<span class="list-tree-group">
						<span class="list-tree-icon">'
            . $this->iconFactory->getIconForRecord('tx_commerce_categories', $mainPageRec, Icon::SIZE_SMALL) . '</span>
						<span class="list-tree-title">'
            . htmlspecialchars(BackendUtility::getRecordTitle('tx_commerce_categories', $mainPageRec, true)) . '</span>
					</span>
					<ul>
			';
        $database->store_lastBuiltQuery = 1;
        // Look up tt_content elements from the expanded page:
        $rows = $database->exec_SELECTgetRows(
            'tx_commerce_products.uid, tx_commerce_products.hidden, tx_commerce_products.starttime,
            tx_commerce_products.endtime, tx_commerce_products.title, tx_commerce_products.fe_group,
            tx_commerce_products.description',
            'tx_commerce_products
            INNER JOIN tx_commerce_products_categories_mm AS mm ON tx_commerce_products.uid = mm.uid_local',
            'mm.uid_foreign = ' . (int)$expCategoryId . BackendUtility::deleteClause('tx_commerce_products')
            . BackendUtility::versioningPlaceholderClause('tx_commerce_products'),
            '',
            'tx_commerce_products.sorting'
        );
        // Traverse list of records:
        $c = 0;
        foreach ($rows as $row) {
            $c++;
            $icon = $this->iconFactory->getIconForRecord('tx_commerce_products', $row, Icon::SIZE_SMALL)->render();
            $selected = '';
            if (!empty($this->linkParts) && (int)$this->linkParts['product'] === (int)$row['uid']) {
                $selected = ' class="active"';
            }
            // Putting list element HTML together:
            $out .= '
				<li' . $selected . '>
					<span class="list-tree-group">
						<span class="list-tree-icon">
							' . $icon . '
						</span>
						<span class="list-tree-title">
							<a href="#" class="t3js-pageLink" data-id="commerce|c:' . (int)$expCategoryId
                . '" data-anchor="|p:' . (int)$row['uid'] . '">
								'
                . htmlspecialchars(BackendUtility::getRecordTitle('tx_commerce_products', $row, true)) . '
							</a>
						</span>
					</span>
				</li>
				';
        }
        $out .= '
					</ul>
				</li>
			</ul>
			';

        return $out;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }




    // @todo move frontend link rendering methods to appropriate class

    /**
     * Main function.
     *
     * @param string $linktxt Link text
     * @param array $conf Configuration
     * @param string $linkHandlerKeyword Keyword
     * @param string $linkHandlerValue Value
     * @param string $linkParameter Link parameter
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $pObj Parent

     * @return string
     */
    public function main(
        $linktxt,
        array $conf,
        $linkHandlerKeyword,
        $linkHandlerValue,
        $linkParameter,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &$pObj
    ) {
        $linkHandlerData = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $linkHandlerValue);

        $addparams = '';
        foreach ($linkHandlerData as $linkData) {
            $params = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $linkData);
            if (isset($params[0])) {
                if ($params[0] == 'tx_commerce_products') {
                    $addparams .= '&tx_commerce_pi1[showUid]=' . (int) $params[1];
                } elseif ($params[0] == 'tx_commerce_categories') {
                    $addparams .= '&tx_commerce_pi1[catUid]=' . (int) $params[1];
                }
            }
            if (isset($params[2])) {
                if ($params[2] == 'tx_commerce_products') {
                    $addparams .= '&tx_commerce_pi1[showUid]=' . (int) $params[3];
                } elseif ($params[2] == 'tx_commerce_categories') {
                    $addparams .= '&tx_commerce_pi1[catUid]=' . (int) $params[3];
                }
            }
        }

        if (strpos($addparams, 'showUid') === false) {
            $addparams .= '&tx_commerce_pi1[showUid]=';
        }

        if (strlen($addparams) <= 0) {
            return $linktxt;
        }

        /**
         * Local content object.
         *
         * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
         */
        $localcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class
        );

        $displayPageId = $this->getFrontendController()->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'];
        if (empty($displayPageId)) {
            $displayPageId = SettingsFactory::getInstance()->getExtConf('previewPageID');
        }

        // remove the first param of '$link_param' (this is the page id wich is
        // set by $DisplayPID) and add all params left (e.g. css class,
        // target...) to the value of $lconf['paramter']
        $linkParamArray = explode(' ', $linkParameter);
        if (is_array($linkParamArray)) {
            $linkParamArray = array_splice($linkParamArray, 1);
            if (!empty($linkParamArray)) {
                $linkParameter = $displayPageId . ' ' . implode(' ', $linkParamArray);
            } else {
                $linkParameter = $displayPageId;
            }
        } else {
            $linkParameter = $displayPageId;
        }

        $lconf = $conf;
        unset($lconf['parameter.']);
        $lconf['parameter'] = $linkParameter;
        $lconf['additionalParams'] .= $addparams;
        $lconf['useCacheHash'] = true;

        return $localcObj->typoLink($linktxt, $lconf);
    }


    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
