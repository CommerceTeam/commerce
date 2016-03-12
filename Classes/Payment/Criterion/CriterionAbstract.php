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
 * Abstract payment criterion implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Criterion\CriterionAbstract
 */
abstract class CriterionAbstract implements \CommerceTeam\Commerce\Payment\Criterion\CriterionInterface
{
    /**
     * Parent commerce pibase object.
     *
     * @var \CommerceTeam\Commerce\Controller\BaseController
     */
    protected $pibaseObject;

    /**
     * Payment object.
     *
     * @var \CommerceTeam\Commerce\Payment\PaymentInterface
     */
    protected $paymentObject;

    /**
     * Options of this criterion.
     *
     * @var array Option array from ext_localconf
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject Payment
     * @param array $options Configuration array
     */
    public function __construct(
        \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject,
        array $options = []
    ) {
        $this->paymentObject = $paymentObject;
        $this->pibaseObject = $this->paymentObject->getParentObject();
        $this->options = $options;
    }
}
