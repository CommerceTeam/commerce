<?php
namespace CommerceTeam\Commerce\Template\Components;

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
            $iconImg = '<span title="' . htmlspecialchars('Commerce') . '">'
                . $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL) . '</span>';
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
