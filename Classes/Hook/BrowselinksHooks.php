<?php

namespace CommerceTeam\Commerce\Hook;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook to adjust linkwizard (linkbrowser).
 *
 * Class \CommerceTeam\Commerce\Hook\BrowselinksHooks
 *
 * @author 2008-2011 Christian Ehret <chris@ehret.name>
 */
class BrowselinksHooks implements \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface
{
    /**
     * Local content object cObj parent.
     *
     * @var \tx_rtehtmlarea_browse_links
     */
    protected $pObj;

    /**
     * Category tree.
     *
     * @var \CommerceTeam\Commerce\ViewHelpers\Browselinks\CategoryTree
     */
    protected $treeObj;

    /**
     * Script.
     *
     * @var string
     */
    protected $script;

    /**
     * Commerce tab.
     *
     * @var string
     */
    protected $tabKey = 'commerce_tab';

    /**
     * Initialisation (additionalParameters is an empty array).
     *
     * @param \tx_rtehtmlarea_browse_links $parentObject         Parent object
     * @param array                        $additionalParameters Parameter
     */
    public function init($parentObject, $additionalParameters)
    {
        $this->pObj = $parentObject;

        // initialize the tree
        $this->initTree();

        // add js
        // has to be added as script tags to the body since parentObject
        // is not passed by reference first we go from html path to typo3 path
        $linkToTreeJs = '/'.TYPO3_mainDir.'js/tree.js';

        $this->script = '<script src="'.$linkToTreeJs.'" type="text/javascript"></script>';
        $this->script .= GeneralUtility::wrapJS('
			Tree.ajaxID = "CommerceTeam\\Commerce\\Hook\\BrowselinksHooks::ajaxExpandCollapse";
		');

        if ($parentObject->RTEtsConfigParams) {
            $this->script .= GeneralUtility::wrapJS('
				/**
				 * needed because link_folder contains the side domain lately
				 */
				function link_commerce(theLink) {
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
					if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
					browse_links_setAdditionalValue("data-htmlarea-external", "");
					plugin.createLink(theLink, cur_target, cur_class, cur_title, additionalValues);
					return false;
				}
			');
        } else {
            $this->script .= GeneralUtility::wrapJS('
				function link_commerce(theLink) {
					updateValueInMainForm(theLink);
					close();
					return false;
				}
			');
        }
    }

    /**
     * Initialize tree.
     */
    protected function initTree()
    {
        // initialize the tree
        $this->treeObj = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\ViewHelpers\\Browselinks\\CategoryTree');
        $this->treeObj->init();
    }

    /**
     * Add allowed items.
     *
     * @param array $currentlyAllowedItems Allowed items
     *
     * @return array
     */
    public function addAllowedItems($currentlyAllowedItems)
    {
        $currentlyAllowedItems[] = 'commerce_tab';

        return $currentlyAllowedItems;
    }

    /**
     * Modify menu definition.
     *
     * @param array $menuDefinition Menu definition
     *
     * @return array
     */
    public function modifyMenuDefinition($menuDefinition)
    {
        $menuDefinition[$this->tabKey] = array(
            'isActive' => $this->pObj->act == $this->tabKey,
            'label' => 'Commerce',
            'url' => '#',
            'addParams' => 'onclick="jumpToUrl(\'?act='.$this->tabKey.'&editorNo='.$this->pObj->editorNo.
                '&contentTypo3Language='.$this->pObj->contentTypo3Language.'&contentTypo3Charset='.
                $this->pObj->contentTypo3Charset.'\');return false;"',
        );

        return $menuDefinition;
    }

    /**
     * Content of new tab.
     *
     * @param string $act Active tab
     *
     * @return string
     */
    public function getTab($act)
    {
        $content = '';
        if ($act == $this->tabKey) {
            // strip http://commerce: in front of url
            $url = $this->pObj->curUrlInfo['value'];
            $url = substr($url, stripos($url, 'commerce:') + strlen('commerce:'));

            $productUid = 0;
            $categoryUid = 0;

            $linkHandlerData = GeneralUtility::trimExplode('|', $url);

            foreach ($linkHandlerData as $linkData) {
                $params = GeneralUtility::trimExplode(':', $linkData);
                if (isset($params[0])) {
                    if ($params[0] == 'tx_commerce_products') {
                        $productUid = (int) $params[1];
                    } elseif ($params[0] == 'tx_commerce_categories') {
                        $categoryUid = (int) $params[1];
                    }
                }
                if (isset($params[2])) {
                    if ($params[2] == 'tx_commerce_products') {
                        $productUid = (int) $params[3];
                    } elseif ($params[2] == 'tx_commerce_categories') {
                        $categoryUid = (int) $params[3];
                    }
                }
            }

            if ($this->isRichTextEditor()) {
                $this->pObj->classesAnchorJSOptions[$this->tabKey] = $this->pObj->classesAnchorJSOptions['page'];
            }

                // set product/category of current link for the tree to expand it there
            if ($productUid > 0) {
                $this->treeObj->setOpenProduct($productUid);
            }

            if ($categoryUid > 0) {
                $this->treeObj->setOpenCategory($categoryUid);
            }

                // get the tree
            $tree = $this->treeObj->getBrowseableTree();

            $content = $this->script;

            $content .= '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
				<tr>
					<td class="c-wCell" valign="top">
			';

            if ($this->isRichTextEditor()) {
                $content .= $this->pObj->addAttributesForm();
            }

            $content .= '
						<h3>Category Tree:</h3>
						'.$tree.'
					</td>
				</tr>
			</table>
			';
        }

        return $content;
    }

    /**
     * Parse current url for commerce fragments.
     *
     * @param string $href    Link
     * @param string $siteUrl Site url
     * @param array  $info    Information
     *
     * @return array
     */
    public function parseCurrentUrl($href, $siteUrl, $info)
    {
        if (strpos(strtolower($href), 'commerce:tx_commerce') !== false) {
            $info['act'] = $this->tabKey;
            unset($this->pObj->curUrlArray['external']);
        }

        return $info;
    }

    /**
     * Check if call of hook is valid.
     *
     * @param string $type Type
     *
     * @return bool
     */
    public function isValid($type)
    {
        return $type === 'rte';
    }

    /**
     * Returns additional addon parameters - required to keep several
     * informations for the RTE linkwizard.
     *
     * @return string
     */
    public function getaddPassOnParams()
    {
        $result = '';

        if (!$this->isRichTextEditor()) {
            $result = GeneralUtility::implodeArrayForUrl('P', GeneralUtility::_GP('P'));
        }

        return $result;
    }

    /**
     * Check if mode is rte.
     *
     * @return bool
     */
    protected function isRichTextEditor()
    {
        return $this->pObj->mode == 'rte';
    }

    /**
     * Makes the AJAX call to expand or collapse the categorytree.
     * Called by typo3/ajax.php.
     *
     * @param array                                   $params  Additional parameters (not used here)
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Ajax object
     */
    public function ajaxExpandCollapse(array $params, \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj)
    {
        $parameter = GeneralUtility::_GP('PM');
            // IE takes anchor as parameter
        if (($parameterPosition = strpos($parameter, '#')) !== false) {
            $parameter = substr($parameter, 0, $parameterPosition);
        }
        $parameter = GeneralUtility::trimExplode('_', $parameter);

            // Load the tree
        $this->initTree();
        $tree = $this->treeObj->getBrowseableAjaxTree($parameter);

        $ajaxObj->addContent('tree', $tree);
    }
}
