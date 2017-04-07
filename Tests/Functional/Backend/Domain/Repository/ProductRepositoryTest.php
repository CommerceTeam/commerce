<?php
namespace CommerceTeam\Commerce\Tests\Functional\Backend\Domain\Repository;

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
 * Test case for \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class ProductRepositoryTest extends \CommerceTeam\Commerce\Tests\Functional\Backend\AbstractTestCase
{
    /**
     * @var \CommerceTeam\Commerce\Domain\Repository\ProductRepository
     */
    private $subject;

    /**
     * @var array
     */
    private $emptyProduct = [
        'title' => '',
        'uid' => '0',
        'pid' => '1',
        't3ver_oid' => '0',
        't3ver_id' => '0',
        't3ver_wsid' => '0',
        't3ver_label' => '',
        't3ver_state' => '0',
        't3ver_stage' => '0',
        't3ver_count' => '0',
        't3ver_tstamp' => '0',
        't3ver_move_id' => '0',
        'tstamp' => '0',
        'crdate' => '0',
        'sorting' => '0',
        'cruser_id' => '0',
        'sys_language_uid' => '0',
        'l18n_parent' => '0',
        'l18n_diffsource' => null,
        'deleted' => '0',
        'hidden' => '0',
        'starttime' => '0',
        'endtime' => '0',
        'fe_group' => '0',
        'subtitle' => '',
        'navtitle' => '',
        'keywords' => null,
        'description' => null,
        'teaser' => null,
        'teaserimages' => null,
        'images' => null,
        'categories' => '',
        'manufacturer_uid' => '0',
        'attributes' => null,
        'articles' => null,
        'attributesedit' => null,
        'uname' => '',
        'relatedpage' => '0',
        'relatedproducts' => null,
        'l10n_state' => null,
    ];

    /**
     * Sets up this test suite.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(ORIGINAL_ROOT . $this->fixturePath . 'tx_commerce_products.xml');

        $this->subject = new \CommerceTeam\Commerce\Domain\Repository\ProductRepository();
    }


    /**
     * @test
     */
    public function getData()
    {
        $this->markTestSkipped('getData only works in frontend context');
    }

    /**
     * @test
     */
    public function getTranslatedData()
    {
        $this->markTestSkipped('getData with translation only works in frontend context');
    }

    /**
     * @test
     */
    public function getDataOfDeletedProductLeadsToEmptyResult()
    {
        $this->markTestSkipped('getData of deleted record only works in frontend context');
    }

    /**
     * @test
     */
    public function getDataOfAccessRestrictedProductLeadsToEmptyResult()
    {
        $this->markTestSkipped('getData of access restricted record only works in frontend context');
    }


    /**
     * @test
     */
    public function findByUid()
    {
        $response = $this->subject->findByUid(1);
        $this->assertEquals(
            array_merge($this->emptyProduct, [
                'uid' => '1',
                'title' => 'product_default',
            ]),
            $response
        );
    }

    /**
     * @test
     */
    public function findByUidWithAccessRestriction()
    {
        $response = $this->subject->findByUid(5);
        $this->assertEquals(
            array_merge($this->emptyProduct, [
                'uid' => '5',
                'fe_group' => '3,5',
                'title' => 'deleted_product',
            ]),
            $response
        );
    }
}
