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

require_once PATH_site . 'typo3conf/ext/commerce/Classes/Utility/UpdateUtility.php';

/**
 * Update Class for DB Updates.
 *
 * Basically checks for the new Tree, if all records have a MM
 * relation to Record UID 0 if not, these records are created
 *
 * Class ext_update
 *
 * @author 2008 - 2009 Ingo Schmitt <is@marketing-factory.de>
 */
class ext_update extends \CommerceTeam\Commerce\Utility\UpdateUtility
{
}
