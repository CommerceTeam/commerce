<?php
namespace CommerceTeam\Commerce\Hooks;

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

/**
 * Implements the hooks for versioning and swapping.
 *
 * Class \CommerceTeam\Commerce\Hook\VersionHooks
 */
class VersionHook
{
    /**
     * After versioning for tx_commerce_products, this also
     * 1) copies the Attributes (flex and mm)
     * 2) copies the Articles and keeps their relations.
     *
     * @param string $table Tablename on which the swap happens
     * @param int $id Id of the LIVE Version to swap
     * @param int $swapWith Id of the Offline Version to swap with
     * @param int $swapIntoWorkspace If set, swaps online into workspace
     *      instead of publishing out of workspace.
     *
     * @return void
     */
    public function processSwap_postProcessSwap($table, $id, $swapWith, $swapIntoWorkspace)
    {
        if ('tx_commerce_products' == $table) {
            $copy = !is_null($swapIntoWorkspace);

            // give Attributes from swapWith to id
            \CommerceTeam\Commerce\Utility\BackendUtility::swapProductAttributes($swapWith, $id, $copy);

            // give Articles from swapWith to id
            \CommerceTeam\Commerce\Utility\BackendUtility::swapProductArticles($swapWith, $id, $copy);
        }
    }
}
