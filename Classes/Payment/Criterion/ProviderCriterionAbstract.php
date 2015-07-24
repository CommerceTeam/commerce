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

use CommerceTeam\Commerce\Payment\Provider\ProviderInterface;

/**
 * Abstract payment criterion implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Criterion\ProviderCriterionAbstract
 *
 * @author 2009-2011 Volker Graubaum <vg@e-netconsulting.de>
 */
abstract class ProviderCriterionAbstract implements \CommerceTeam\Commerce\Payment\Provider\ProviderCriterionInterface
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
     * Provider object.
     *
     * @var \CommerceTeam\Commerce\Payment\Provider\ProviderInterface
     */
    protected $providerObject;

    /**
     * Options of this criterion.
     *
     * @var array Option array from ext_localconf
     */
    protected $options = array();

    /**
     * Constructor.
     *
     * @param ProviderInterface $providerObject Parent payment
     * @param array             $options        Configuration array
     *
     * @return self
     */
    public function __construct(ProviderInterface $providerObject, array $options = array())
    {
        $this->providerObject = $providerObject;
        $this->paymentObject = $this->providerObject->getPaymentObject();
        $this->pibaseObject = $this->paymentObject->getParentObject();
        $this->options = $options;
    }
}
