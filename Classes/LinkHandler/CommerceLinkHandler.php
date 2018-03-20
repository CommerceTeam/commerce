<?php
namespace CommerceTeam\Commerce\LinkHandler;

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

use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use CommerceTeam\Commerce\Tree\View\ElementBrowserCategoryTreeView;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;

/**
 * Class \CommerceTeam\Commerce\Hook\LinkhandlerHooks
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
     * The Base URN for this link handling to act on
     * @var string
     */
    protected $baseUrn = 't3://commerce';

    /**
     * CommerceLinkHandler constructor.
     */
    public function __construct()
    {
        if (TYPO3_MODE == 'BE') {
            $this->getLanguageService()->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_be.xlf');
        }
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

        if (is_array($url) && isset($url['catUid'])) {
            $this->linkParts = $linkParts;
            $this->linkParts['category'] = (int) $url['catUid'];

            if (isset($url['proUid'])) {
                $this->linkParts['product'] = (int) $url['proUid'];
            }

            return true;
        } elseif (isset($url['url'])) {
            $url = $this->fixDeprecatedParameter($url['url'], 'picking');

            if (strpos($url, 'commerce:') === false) {
                return false;
            }

            $url = str_replace('commerce:', '', $url);
            $parts = explode('|', $url);

            $this->linkParts = $linkParts;

            foreach ($parts as $part) {
                if (strpos($part, 'c') !== false) {
                    $categoryParts = explode(':', $part);
                    $this->linkParts['category'] = (int)$categoryParts[1];
                }

                if (strpos($part, 'p') !== false) {
                    $productParts = explode(':', $part);
                    $this->linkParts['product'] = (int)$productParts[1];
                }
            }

            return true;
        }
    }

    /**
     * @param array $values Values to be checked
     *
     * @return bool Returns true if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return !empty($this->linkParts) && (int)$this->linkParts['category'] === (int)$values['uid'];
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

        $parts = [];
        if ($this->linkParts['category'] > 0) {
            $parts[] = 'c:' . $this->linkParts['category'];
        }
        if ($this->linkParts['product'] > 0) {
            $parts[] = 'p:' . $this->linkParts['product'];
        }

        return [
            'data-current-link' => $this->asString([
                'url' => 'commerce:' . implode('|', $parts) ,
                'type' => 'commerce'
            ]),
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

            $title = htmlspecialchars($lang->getLL('tx_commerce_categories'));
            $path .= ' \'' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($categoryRow['title'], $titleLen)) . '\''
                . ' (ID:' . $id . ')';
        }

        if ($this->linkParts['product']) {
            $id = (int)$this->linkParts['product'];
            $productRow = BackendUtility::getRecordWSOL('tx_commerce_products', $id);

            $title = htmlspecialchars($lang->getLL('tx_commerce_products'));
            $path .= ' \'' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($productRow['title'], $titleLen)) . '\''
                . ' (ID:' . $id . ')';
        }

        return $title . $path;
    }

    /**
     * Returns all valid parameters for linking to a TYPO3 page as a string
     *
     * @param array $parameters
     * @return string
     */
    public function asString(array $parameters): string
    {
        $urn = $this->baseUrn;
        $divider = '?';

        if (!empty($parameters['proUid'])) {
            $urn .= $divider . 'p=' . $parameters['proUid'];
            $divider = '&';
        }

        if (!empty($parameters['catUid'])) {
            $urn .= $divider .'c=' . $parameters['catUid'];
        }


        if (!empty($parameters['type']) && $parameters['type'] == 'commerce') {
            $url = str_replace('commerce:', '', $parameters['url']);

            $url = $this->fixDeprecatedParameter($url, 'saving');

            $parts = explode('|', $url);


            foreach ($parts as $part) {
                if (strpos($part, 'p') !== false) {
                    $productParts = explode(':', $part);
                    $urn .= $divider . 'p=' .(int)$productParts[1];
                    $divider = '&';
                }

                if (strpos($part, 'c') !== false) {
                    $categoryParts = explode(':', $part);
                    $urn .= $divider . 'c=' . (int)$categoryParts[1];
                }

            }
        }

        return $urn;
    }

    /**
     * Returns all relevant information built in the link to a page (see asString())
     *
     * @param array $data
     * @return array
     */
    public function resolveHandlerData(array $data): array
    {
        $result = [];
        if (isset($data['c'])) {
            $result['catUid'] = $data['c'];
            unset($data['c']);
        }
        if (isset($data['p'])) {
            $result['proUid'] = $data['p'];
            unset($data['p']);
        }


        return $result;
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

        /** @var ElementBrowserCategoryTreeView $categoryTree */
        $categoryTree = GeneralUtility::makeInstance(ElementBrowserCategoryTreeView::class);
        $categoryTree->setLinkParameterProvider($this);
        $categoryTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $categoryTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $categoryTree->addField('navtitle');
        $tree = $categoryTree->getBrowsableTree();

        return '

				<!--
					Wrapper table for page tree / record list:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
					<tr>
						<td class="c-wCell" valign="top">
						    <h3>' . $this->getLanguageService()->getLL('linkhandler.category_tree') . ':</h3>'
                            . $tree
                    . '</td>
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

        /** @var BackendUserUtility $backendUserUtility */
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        // Draw the record list IF there is a page id to expand:
        if (!$expCategoryId
            || !MathUtility::canBeInterpretedAsInteger($expCategoryId)
            || !$backendUserUtility->isInWebMount($expCategoryId)) {
            return '';
        }

        // Set header:
        $out = '<h3>' . $this->getLanguageService()->getLL('linkhandler.products') . ':</h3>';
        // Create header for listing, showing the page title/icon:
        $mainPageRec = BackendUtility::getRecordWSOL('tx_commerce_categories', $expCategoryId);

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

        // Look up tt_content elements from the expanded page:
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $rows = $productRepository->findByCategoryUid($expCategoryId);
        // Traverse list of records:
        $c = 0;
        foreach ($rows as $row) {
            $c++;
            $icon = $this->iconFactory->getIconForRecord('tx_commerce_products', $row, Icon::SIZE_SMALL)->render();
            $selected = '';
            if (!empty($this->linkParts) && (int)$this->linkParts['product'] === (int)$row['uid']) {
                $selected = 'class="active"';
            }

            $url = GeneralUtility::makeInstance(LinkService::class)->asString([
                'type' => 'commerce',
                'catUid' => (int)$expCategoryId,
                'proUid' => (int)$row['uid']
                ]);
            // Putting list element HTML together:
            $out .= '
				<li ' . $selected . '>
					<span class="list-tree-group">
						<span class="list-tree-icon">
							' . $icon . '
						</span>
						<span class="list-tree-title">
							<a href="' . htmlspecialchars($url) .'" class="t3js-pageLink">' .
                                BackendUtility::getRecordTitle('tx_commerce_products', $row, true) . '
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
     * Main function to render urls in frontend with ContentObjectRenderer::resolveMixedLinkParameter
     *
     * @param string $linkText Link text
     * @param array $configuration Configuration
     * @param string $linkHandlerKeyword Keyword
     * @param string $linkHandlerValue Value
     * @param string $mixedLinkParameter Link parameter
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRender Parent
     *
     * @return string
     */
    public function main(
        $linkText,
        array $configuration,
        $linkHandlerKeyword,
        $linkHandlerValue,
        $mixedLinkParameter,
        \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &$contentObjectRender
    ) {
        if ($linkHandlerKeyword !== 'commerce') {
            return $linkText;
        }

        $linkHandlerValue = $this->fixDeprecatedParameter($linkHandlerValue, 'rendering');
        $parts = explode('|', $linkHandlerValue);

        $addparams = '';
        foreach ($parts as $part) {
            if (strpos($part, 'c') !== false) {
                $categoryParts = explode(':', $part);
                $addparams .= '&tx_commerce_pi1[catUid]=' . (int)$categoryParts[1];
            }

            if (strpos($part, 'p') !== false) {
                $productParts = explode(':', $part);
                $addparams .= '&tx_commerce_pi1[showUid]=' . (int)$productParts[1];
            }
        }

        if (!empty($addparams) && strpos($addparams, 'showUid') === false) {
            $addparams .= '&tx_commerce_pi1[showUid]=';
        }

        if (!$addparams) {
            return $linkText;
        }

        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $controller */
        $controller = $GLOBALS['TSFE'];
        $displayPageId = $controller->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'];
        if (empty($displayPageId)) {
            $displayPageId = ConfigurationUtility::getInstance()->getExtConf('previewPageID');
        }
        if (empty($displayPageId)) {
            return 'ERROR: neither overridePid in TypoScript nor previewPageID in Extension Settings are configured to
                render commerce categor and product urls';
        }

        $linkParamArray = explode(' ', $mixedLinkParameter);
        if (is_array($linkParamArray)) {
            // Remove first parameter as it must be the page id. If the array is still not empty
            // prepend the remaining parameters with the configured page id
            $linkParamArray = array_splice($linkParamArray, 1);
            if (!empty($linkParamArray)) {
                $mixedLinkParameter = $displayPageId . ' ' . implode(' ', $linkParamArray);
            } else {
                $mixedLinkParameter = $displayPageId;
            }
        } else {
            $mixedLinkParameter = $displayPageId;
        }

        $linkConfiguration = $configuration;
        unset($linkConfiguration['parameter.']);
        $linkConfiguration['parameter'] = $mixedLinkParameter;
        $linkConfiguration['additionalParams'] .= $addparams;
        $linkConfiguration['useCacheHash'] = true;

        return $contentObjectRender->typoLink($linkText, $linkConfiguration);
    }

    /**
     * Fix url if deprecated parameter are still present in url
     *
     * @param string $url
     * @param string $action [picking|rendering]
     *
     * @return string
     * @deprecated Remove in version 7. This is only a temporary fix
     */
    protected function fixDeprecatedParameter($url, $action)
    {
        if (strpos($url, 'tx_commerce_categories') !== false
            || strpos($url, 'tx_commerce_products') !== false) {
            GeneralUtility::deprecationLog('
                Commerce: deprecated parameter tx_commerce_categories or tx_commerce_products found in link "'
                . $action . '". See documentation section Deprecation/Version5.
            ');
        }

        $url = str_replace('tx_commerce_categories', 'c', $url);
        $url = str_replace('tx_commerce_products', 'p', $url);

        return $url;
    }
}
