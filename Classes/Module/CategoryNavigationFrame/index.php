<?php

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

// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
    /**
     * Category navigation viewhelper.
     *
     * @var \CommerceTeam\Commerce\ViewHelpers\Navigation\CategoryViewHelper
     */
    $SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        'CommerceTeam\\Commerce\\ViewHelpers\\Navigation\\CategoryViewHelper'
    );
    $SOBE->init();
    $SOBE->initPage();
    $SOBE->main();
    $SOBE->printContent();
}
