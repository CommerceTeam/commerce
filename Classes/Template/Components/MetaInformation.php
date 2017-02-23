<?php
namespace CommerceTeam\Commerce\Template\Components;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetaInformation extends \TYPO3\CMS\Backend\Template\Components\MetaInformation
{
    /**
     * Setting page icon with clickMenu + uid for docheader
     *
     * @return string Record info
     */
    public function getRecordInformation()
    {
        $pageRecord = $this->recordArray;
        if (empty($pageRecord)) {
            return '';
        } elseif (!isset($pageRecord['_is_category'])) {
            return parent::getRecordInformation();
        }

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $additionalInfo = (!empty($pageRecord['_additional_info']) ? $pageRecord['_additional_info'] : '');
        // Add icon with clickMenu, etc:
        // If there IS a real page
        if (is_array($pageRecord) && $pageRecord['uid']) {
            $toolTip = BackendUtility::getRecordToolTip($pageRecord, 'tx_commerce_categories');
            $iconImg = '<span ' . $toolTip . '>' . $iconFactory->getIconForRecord(
                'tx_commerce_categories',
                $pageRecord,
                Icon::SIZE_SMALL
            ) . '</span>';
            // Make Icon:
            $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'tx_commerce_categories', $pageRecord['uid']);
            $uid = $pageRecord['uid'];
            $title = BackendUtility::getRecordTitle('tx_commerce_categories', $pageRecord);
        } else {
            // On root-level of page tree
            // Make Icon
            $iconImg = '<span title="' .
                htmlspecialchars('Commerce') .
                '">'
                . $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . '</span>';
            if ($this->getBackendUser()->isAdmin()) {
                $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'pages', 0);
            } else {
                $theIcon = $iconImg;
            }
            $uid = '0';
            $title = 'Commerce';
        }
        // Setting icon with clickMenu + uid
        return $theIcon
            . ' <strong>' . htmlspecialchars($title) . ($uid !== '' ? '&nbsp;[' . $uid . ']' : '') . '</strong>'
            . (!empty($additionalInfo) ? ' ' . htmlspecialchars($additionalInfo) : '');
    }
}
