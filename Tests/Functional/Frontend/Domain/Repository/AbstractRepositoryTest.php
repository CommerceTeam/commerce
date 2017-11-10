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
 * Test case for \CommerceTeam\Commerce\Domain\Repository\AbstractRepository
 */
class AbstractRepositoryTest extends \CommerceTeam\Commerce\Tests\Functional\Frontend\AbstractTestCase
{
    /**
     * Use ProductRepository as the AbstractRepository can not be instantiated directly
     *
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
    public function getData()
    {
        $this->markTestSkipped('BE getData only works in frontend context');

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
        $this->markTestSkipped('BE getData only works in frontend context');

        $this->getTypoScriptFrontendController()->sys_language_uid = 1;

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
        $this->markTestSkipped('BE getData only works in frontend context');

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
        $this->markTestSkipped('BE getData only works in frontend context');

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
        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');

        $response = $this->subject->findByUid(
            6,
            $queryBuilder->expr()->inSet('uname', '42')
        );
        $this->assertEquals(
            array_merge($this->emptyProduct, [
                'uid' => 6,
                'uname' => '23,42',
                'title' => 'product_with_group_set',
            ]),
            $response
        );
    }

    /**
     * @test
     */
    public function countWithTableAndWhere()
    {
        $this->markTestSkipped('FE countWithTableAndWhere only works in backend context');

        $response = $this->subject->countWithTableAndWhere(
            'tx_commerce_products',
            'uid = 1 AND hidden = 0'
        );
        $this->assertEquals(
            1,
            $response
        );
    }

    /**
     * @test
     */
    public function isValidUid()
    {
        $this->assertTrue(
            $this->subject->isValidUid(1)
        );
    }

    /**
     * @test
     */
    public function isValidUidInvalidUidParameter()
    {
        $response = $this->subject->isValidUid('abc');
        $this->assertEquals(
            false,
            $response
        );
    }

    /**
     * @test
     */
    public function isAccessible()
    {
        $this->assertTrue(
            $this->subject->isAccessible(1)
        );
    }

    /**
     * @test
     */
    public function isAccessibleNot()
    {
        $response = $this->subject->isAccessible(123456);
        $this->assertEquals(
            false,
            $response
        );
    }

    /**
     * @test
     */
    public function isAccessibleInvalidUidParameter()
    {
        $response = $this->subject->isAccessible('abc');
        $this->assertEquals(
            false,
            $response
        );
    }

    /**
     * @test
     */
    public function updateRecord()
    {
        $this->assertTrue(
            $this->subject->updateRecord(1, ['title' => 'updated'])
        );
    }

    /**
     * @test
     */
    public function updateRecordInvalidUidParameter()
    {
        $response = $this->subject->updateRecord(123456, ['title' => 'updated']);
        $this->assertEquals(
            false,
            $response
        );
    }

    /**
     * @test
     */
    public function updateRecordEmptyData()
    {
        $response = $this->subject->updateRecord(1, []);
        $this->assertEquals(
            false,
            $response
        );
    }

    /**
     * @test
     */
    public function enableFields()
    {
        $this->markTestSkipped('Deprecated methods do not get tests.');
    }

    /**
     * @test
     */
    public function addRecord()
    {
        $fixture = [
            'pid' => 1,
            'title' => 'add_test',
        ];
        $fixture['uid'] = $this->subject->addRecord($fixture);

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $response = $queryBuilder
            ->select('*')
            ->from('tx_commerce_products')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($fixture['uid'], \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        $this->assertEquals(
            array_merge($this->emptyProduct, $fixture),
            $response
        );
    }

    /**
     * @test
     */
    public function deleteRecord()
    {
        $this->subject->deleteRecord(7);

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $response = $queryBuilder
            ->count('*')
            ->from('tx_commerce_products')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(7, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();

        $this->assertEquals(
            0,
            $response
        );
    }

    /**
     * @test
     */
    public function insertWithTable()
    {
        $table = 'tx_commerce_products';
        $countPreInsert = $this->subject->countWithTableAndWhere($table, '1=1');

        $this->subject->insertWithTable(
            $table,
            array_merge($this->emptyProduct, ['uid' => 8, 'title' => 'insert_test'])
        );

        $countPostInsert = $this->subject->countWithTableAndWhere($table, '1=1');

        $this->assertEquals(
            $countPreInsert + 1,
            $countPostInsert
        );
    }

    /**
     * @test
     */
    public function updateWithTable()
    {
        $this->subject->updateWithTable('tx_commerce_products', 'uid = 9', ['title' => 'update']);

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $response = $queryBuilder
            ->select('*')
            ->from('tx_commerce_products')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(9, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        $this->assertEquals(
            array_merge(
                $this->emptyProduct,
                [
                    'uid' => 9,
                    'title' => 'update',
                ]
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function deleteWithTable()
    {
        $this->subject->deleteWithTable('tx_commerce_products', 'uid = 10');

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $response = $queryBuilder
            ->count('*')
            ->from('tx_commerce_products')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(8, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();

        $this->assertEquals(
            0,
            $response
        );
    }

    /**
     * @test
     */
    public function deleteTranslations()
    {
        $this->subject->deleteTranslations(10);

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $response = $queryBuilder
            ->count('*')
            ->from('tx_commerce_products')
            ->where(
                $queryBuilder->expr()->eq(
                    'l18n_parent',
                    $queryBuilder->createNamedParameter(10, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();

        $this->assertEquals(
            0,
            $response
        );
    }

    /**
     * @test
     */
    public function hasColumn()
    {
        $this->assertTrue(
            $this->subject->hasColumn('uid')
        );
    }

    /**
     * @test
     */
    public function hasTable()
    {
        $this->assertTrue(
            $this->subject->hasTable('pages')
        );
    }

    /**
     * @test
     */
    public function getTable()
    {
        $this->assertEquals(
            'tx_commerce_products',
            $this->subject->getTable()
        );
    }
}
