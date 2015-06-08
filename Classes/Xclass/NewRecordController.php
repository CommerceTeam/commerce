<?php
/**
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class NewRecordController extends \TYPO3\CMS\Backend\Controller\NewRecordController {

	/**
	 * Links the string $code to a create-new form for a record
	 * in $table created on page $pid
	 *
	 * @param string $linkText Link text
	 * @param string $table Table name (in which to create new record)
	 * @param int $pid PID value for the
	 *  "&edit['.$table.']['.$pid.']=new" command (positive/negative)
	 * @param bool $addContentTable If $addContentTable is set,
	 *  then a new contentTable record is created together with pages
	 *
	 * @return string The link.
	 */
    public function linkWrap($linkText, $table, $pid, $addContentTable = FALSE) {
        $parameters = '&edit[' . $table . '][' . $pid . ']=new';

		if ($table == 'pages'
			&& $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']
			&& isset($GLOBALS['TCA'][$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']])
			&& $addContentTable) {
            $parameters .= '&edit[' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'] . '][prev]=new&returnNewPageId=1';
        } elseif ($table == 'pages_language_overlay') {
            $parameters .= '&overrideVals[pages_language_overlay][doktype]=' . (int)$this->pageinfo['doktype'];
        }

		$parameters = $this->addCommerceParameter($parameters, $table);
        $onClick = BackendUtility::editOnClick($parameters, '', $this->returnUrl);

		return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $linkText . '</a>';
	}

	/**
	 * @param string $parameters
	 * @param string $table
	 * @return string
	 */
	protected function addCommerceParameter($parameters, $table) {
		if (GeneralUtility::_GP('parentCategory')) {
			switch ($table) {
				case 'tx_commerce_categories':
					$parameters .= '&defVals[tx_commerce_categories][parent_category]=' . GeneralUtility::_GP('parentCategory');
					break;

				case 'tx_commerce_products':
					$parameters .= '&defVals[tx_commerce_products][categories]=' . GeneralUtility::_GP('parentCategory');
					break;

				default:
			}
		}

		return $parameters;
	}
}