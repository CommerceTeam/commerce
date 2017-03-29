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
        $response = $this->subject->getData(1);
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
    public function getTranslatedData()
    {
        $GLOBALS['TSFE']->sys_language_uid = 1;

        $response = $this->subject->getData(1);
        $this->assertEquals(
            array_merge($this->emptyProduct, [
                'uid' => '1',
                'l18n_parent' => '1',
                'sys_language_uid' => '1',
                'title' => 'product_translation_1',
                '_LOCALIZED_UID' => '2'
            ]),
            $response
        );
    }

    /**
     * @test
     */
    public function getDataOfDeletedProductLeadsToEmptyResult()
    {
        $response = $this->subject->getData(4);
        $this->assertEquals(
            [],
            $response
        );
    }

    /**
     * @test
     */
    public function getDataOfAccessRestrictedProductLeadsToEmptyResult()
    {
        $response = $this->subject->getData(5);
        $this->assertEquals(
            [],
            $response
        );
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
            [],
            $response
        );
    }
}
