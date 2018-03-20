<?php
declare(strict_types=1);
namespace CommerceTeam\Commerce\Typolink;

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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;

/**
 * Builds a TypoLink to a certain page
 */
class CommerceLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @param array $linkDetails parsed link details by the LinkService
     * @param string $linkText the link text
     * @param string $target the target to point to
     * @param array $conf the TypoLink configuration array
     * @return array an array with three parts (URL, Link Text, Target)
     * @throws \RuntimeException if overridePid is neither in TypoScript nor in extension settings definded
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $addParams = '';

        if (isset($linkDetails['catUid']) && MathUtility::canBeInterpretedAsInteger($linkDetails['catUid'])) {
            $addParams .= '&tx_commerce_pi1[catUid]=' . (int)$linkDetails['catUid'];
        }

        if (isset($linkDetails['proUid']) && MathUtility::canBeInterpretedAsInteger($linkDetails['proUid'])) {
            $addParams .= '&tx_commerce_pi1[showUid]=' . (int)$linkDetails['proUid'];
        }

        if (!empty($addParams) && strpos($addParams, 'showUid') === false) {
            $addParams .= '&tx_commerce_pi1[showUid]=';
        }

        $url = '';
        if (!empty($addParams)) {
            $displayPageId =
                $this->getTypoScriptFrontendController()->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'];
            if (empty($displayPageId)) {
                $displayPageId = ConfigurationUtility::getInstance()->getExtConf('previewPageID');
            }
            if (empty($displayPageId)) {
                throw new \RuntimeException('ERROR: neither overridePid in TypoScript nor previewPageID in 
                Extension Settings are configured to render commerce category and product urls.', 1521539129);
            }


            $linkConfiguration = $conf;
            unset($linkConfiguration['parameter.']);
            $linkConfiguration['parameter'] = $displayPageId;
            $linkConfiguration['additionalParams'] .= $addParams;
            $linkConfiguration['useCacheHash'] = true;
            $linkConfiguration['returnLast'] = 'url';

            
            $url = $this->contentObjectRenderer->typoLink($linkText, $linkConfiguration);
        }
        return [$url, $linkText, $target];
    }
}
