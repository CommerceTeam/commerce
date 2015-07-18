<?php

namespace CommerceTeam\Commerce\Payment\Criterion;

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
 * Abstract payment criterion implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Criterion\CriterionAbstract
 *
 * @author 2009-2011 Volker Graubaum <vg@e-netconsulting.com>
 */
abstract class CriterionAbstract implements \CommerceTeam\Commerce\Payment\Criterion\CriterionInterface
{
    /**
     * Parent commerce pibase object.
     *
     * @var \CommerceTeam\Commerce\Controller\BaseController
     */
    protected $pibaseObject = null;

    /**
     * Payment object.
     *
     * @var \CommerceTeam\Commerce\Payment\PaymentInterface
     */
    protected $paymentObject = null;

    /**
     * Options of this criterion.
     *
     * @var array Option array from ext_localconf
     */
    protected $options = array();

    /**
     * Constructor.
     *
     * @param \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject Payment
     * @param array                                           $options       Configuration array
     *
     * @return self
     */
    public function __construct(\CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject, array $options = array())
    {
        $this->paymentObject = $paymentObject;
        $this->pibaseObject = $this->paymentObject->getParentObject();
        $this->options = $options;
    }
}
