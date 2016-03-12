<?php
namespace CommerceTeam\Commerce\Payment\Provider;

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
 * Payment provider criterion interface.
 *
 * Class \CommerceTeam\Commerce\Payment\Provider\ProviderCriterionInterface
 */
interface ProviderCriterionInterface
{
    /**
     * Constructor.
     *
     * @param ProviderInterface $providerObject Parent payment
     * @param array $options Configuration array
     */
    public function __construct(ProviderInterface $providerObject, array $options = []);

    /**
     * Return TRUE if this payment type is allowed.
     *
     * @return bool
     */
    public function isAllowed();
}
