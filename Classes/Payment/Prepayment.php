<?php
namespace CommerceTeam\Commerce\Payment;

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

/**
 * Pre payment payment implementaton.
 *
 * Class \CommerceTeam\Commerce\Payment\Prepayment
 *
 * @author 2005-2011 Thomas Hempel <thomas@work.de>
 */
class Prepayment extends PaymentAbstract
{
    /**
     * Payment type.
     *
     * @var string
     */
    protected $type = 'prepayment';
}
