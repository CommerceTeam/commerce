<?php
namespace CommerceTeam\Commerce\Payment\Criterion;

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
 * Payment criterion interface.
 *
 * Class \CommerceTeam\Commerce\Payment\Criterion\CriterionInterface
 */
interface CriterionInterface
{
    /**
     * Constructor.
     *
     * @param \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject Parent
     * @param array $options Configuration array
     */
    public function __construct(
        \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject,
        array $options = []
    );

    /**
     * Return TRUE if this payment type is allowed.
     *
     * @return bool
     */
    public function isAllowed();
}
