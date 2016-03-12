<?php
namespace CommerceTeam\Commerce\Payment;

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
 * Cash on delivery payment implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Cashondelivery
 */
class Cashondelivery extends PaymentAbstract
{
    /**
     * Payment type.
     *
     * @var string
     */
    protected $type = 'cashondelivery';
}
