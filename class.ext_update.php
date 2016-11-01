<?php
namespace CommerceTeam\Commerce;

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
 * Update Class for DB Updates.
 *
 * Basically checks for the new Tree, if all records have a MM
 * relation to Record UID 0 if not, these records are created
 *
 * Class ext_update
 */
class ext_update extends \CommerceTeam\Commerce\Utility\UpdateUtility
{
}
