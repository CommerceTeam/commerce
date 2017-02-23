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

class DocHeaderComponent extends \TYPO3\CMS\Backend\Template\Components\DocHeaderComponent
{
    /**
     * Sets up buttonBar and MenuRegistry
     */
    public function __construct()
    {
        parent::__construct();
        $this->metaInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MetaInformation::class);
    }
}
