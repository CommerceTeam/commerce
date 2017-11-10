<?php
namespace CommerceTeam\Commerce\Tests\Functional\Frontend\Domain\Repository;

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
 * Test case for \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class ProductRepositoryTest extends \CommerceTeam\Commerce\Tests\Functional\Frontend\AbstractTestCase
{
    /**
     * @var \CommerceTeam\Commerce\Domain\Repository\ProductRepository
     */
    protected $subject;

    /**
     * @var array
     */
    protected $emptyProduct = [
        'uid' => 0,
        'pid' => 1,
        't3ver_oid' => 0,
        't3ver_id' => 0,
        't3ver_wsid' => 0,
        't3ver_label' => '',
        't3ver_state' => 0,
        't3ver_stage' => 0,
        't3ver_count' => 0,
        't3ver_tstamp' => 0,
        't3ver_move_id' => 0,
        'tstamp' => 0,
        'crdate' => 0,
        'sorting' => 0,
        'cruser_id' => 0,
        'sys_language_uid' => 0,
        'l18n_parent' => 0,
        'l18n_diffsource' => null,
        'deleted' => 0,
        'hidden' => 0,
        'starttime' => 0,
        'endtime' => 0,
        'fe_group' => '0',
        'title' => '',
        'subtitle' => '',
        'navtitle' => '',
        'keywords' => null,
        'description' => null,
        'teaser' => null,
        'teaserimages' => null,
        'images' => null,
        'categories' => '',
        'manufacturer_uid' => 0,
        'attributes' => null,
        'articles' => null,
        'attributesedit' => null,
        'uname' => '',
        'relatedpage' => 0,
        'relatedproducts' => null,
        'l10n_state' => null,
        't3_origuid' => 0,
        'l10n_source' => 0,
    ];

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet($this->fixturePath . 'tx_commerce_products.xml');

        $this->subject = new \CommerceTeam\Commerce\Domain\Repository\ProductRepository();
    }

    /**
     * @test
     */
    public function getArticles()
    {

    }

    /**
     * @test
     */
    public function getAttributes()
    {

    }

    /**
     * @test
     */
    public function getAttributeRelations()
    {

    }

    /**
     * @test
     */
    public function getUniqueAttributeRelations()
    {

    }
}
