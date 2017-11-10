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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;

/**
 * Test case for \CommerceTeam\Commerce\Domain\Repository\FolderRepository
 */
class FolderRepositoryTest extends \CommerceTeam\Commerce\Tests\Functional\Frontend\AbstractTestCase
{
    /**
     * @var array
     */
    protected $emptyPages = [
        'uid' => 0,
        'pid' => 0,
        'title' => '',
        't3ver_oid' => 0,
        't3ver_id' => 0,
        't3ver_wsid' => 0,
        't3ver_label' => '',
        't3ver_state' => 0,
        't3ver_stage' => 0,
        't3ver_count' => 0,
        't3ver_tstamp' => 0,
        't3ver_move_id' => 0,
        't3_origuid' => 0,
        'tstamp' => 0,
        'sorting' => 0,
        'deleted' => 0,
        'perms_userid' => 0,
        'perms_groupid' => 0,
        'perms_user' => 0,
        'perms_group' => 0,
        'perms_everybody' => 0,
        'editlock' => 0,
        'crdate' => 0,
        'cruser_id' => 0,
        'hidden' => 0,
        'doktype' => 0,
        'TSconfig' => null,
        'is_siteroot' => 0,
        'php_tree_stop' => 0,
        'url' => '',
        'starttime' => 0,
        'endtime' => 0,
        'urltype' => 0,
        'shortcut' => 0,
        'shortcut_mode' => 0,
        'no_cache' => 0,
        'fe_group' => '0',
        'subtitle' => '',
        'layout' => 0,
        'target' => '',
        'media' => 0,
        'lastUpdated' => 0,
        'keywords' => null,
        'cache_timeout' => 0,
        'cache_tags' => '',
        'newUntil' => 0,
        'description' => null,
        'no_search' => 0,
        'SYS_LASTCHANGED' => 0,
        'abstract' => null,
        'module' => '',
        'extendToSubpages' => 0,
        'author' => '',
        'author_email' => '',
        'nav_title' => '',
        'nav_hide' => 0,
        'content_from_pid' => 0,
        'mount_pid' => 0,
        'mount_pid_ol' => 0,
        'alias' => '',
        'l18n_cfg' => 0,
        'fe_login_mode' => 0,
        'backend_layout' => '',
        'backend_layout_next_level' => '',
        'tsconfig_includes' => null,
        'tx_commerce_foldereditorder' => 0,
        'tx_commerce_foldername' => '',
        'categories' => 0,
    ];

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet($this->fixturePath . 'pages.xml');
    }

    /**
     * @test
     */
    public function initFolders()
    {
        $this->assertEquals(
            1,
            FolderRepository::initFolders()
        );
    }

    /**
     * @test
     */
    public function initFoldersProducts()
    {
        $this->assertEquals(
            2,
            FolderRepository::initFolders('Products', FolderRepository::initFolders())
        );
    }

    /**
     * @test
     */
    public function getFolder()
    {
        $this->assertEquals(
            [
                'uid' => 2,
                'pid' => 1,
                'title' => 'Products'
            ],
            FolderRepository::getFolder('Products', 1)
        );
    }

    /**
     * @test
     */
    public function createFolder()
    {
        $pageId = FolderRepository::createFolder('create_folder', 1);
        $expected = [
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'uid' => $pageId,
            'pid' => 1,
            'title' => 'create_folder',
            'doktype' => 254,
            'module' => 'commerce',
            'perms_user' => 31,
            'perms_group' => 31,
            'perms_everybody' => 31,
            'sorting' => 1,
            'tx_commerce_foldername' => 'create_folder',
        ];

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $response = $queryBuilder
            ->select(...array_keys($expected))
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'title',
                    $queryBuilder->createNamedParameter('create_folder', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        $this->assertEquals($expected, $response);
    }

    /**
     * @test
     */
    public function createBasicFolders()
    {
        $queryBuilder = $this->getQueryBuilderForTable('pages');
        $queryBuilder->getConnection()->truncate('pages');

        $class = new \ReflectionClass(FolderRepository::class);
        $method = $class->getMethod('createBasicFolders');
        $method->setAccessible(true);

        $repository = new FolderRepository();
        $method->invoke($repository);

        $titles = [
            'Commerce' => 1,
            'Products' => 2,
            'Attributes' => 3,
            'Orders' => 4,
            'Incoming' => 5,
            'Working' => 6,
            'Waiting' => 7,
            'Delivered' => 8,
        ];

        $response = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'title',
                    $queryBuilder->createNamedParameter(
                        array_keys($titles),
                        \TYPO3\CMS\Core\Database\Connection::PARAM_STR_ARRAY
                    )
                ),
                $queryBuilder->expr()->in(
                    'module',
                    $queryBuilder->createNamedParameter('commerce', \PDO::PARAM_STR)
                )
            )
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $result = array_column($response, 'uid');

        $this->assertEquals(
            array_values($titles),
            $result
        );
    }

    /**
     * @test
     */
    public function makeSystemCategoriesProductsArticlesAndPrices()
    {
        // prepare fixture data
        $this->importDataSet($this->fixturePath . 'tx_commerce_categories.xml');

        // invoke protected method
        $class = new \ReflectionClass(FolderRepository::class);
        $method = $class->getMethod('makeSystemCategoriesProductsArticlesAndPrices');
        $method->setAccessible(true);

        $repository = new FolderRepository();
        $method->invoke($repository, 1, 'DELIVERY', [
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'pid' => 2
        ]);

        // check for created products
        $products = [
            'DELIVERY' => [
                'uid' => 1,
                'pid' => 2,
                'uname' => 'DELIVERY',
                'title' => 'DELIVERY',
                'categories' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $result = $productQueryBuilder
            ->select(...array_keys($products['DELIVERY']))
            ->from('tx_commerce_products')
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            array_values($products),
            $result
        );

        // check for created articles
        $articles = [
            'DELIVERY' => [
                'uid' => 1,
                'classname' => 'sysdelivery',
                'title' => 'sysdelivery',
                'article_type_uid' => 3,
                'uid_product' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_articles');
        $result = $productQueryBuilder
            ->select(...array_keys($articles['DELIVERY']))
            ->from('tx_commerce_articles')
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            array_values($articles),
            $result
        );

        // check for created prices
        $prices = [
            0 => [
                'uid' => 1,
                'uid_article' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_article_prices');
        $result = $productQueryBuilder
            ->select(...array_keys($prices[0]))
            ->from('tx_commerce_article_prices')
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            $prices,
            $result
        );

        // check for created relations
        $categoryRelations = [
            0 => [
                'uid_local' => 1,
                'uid_foreign' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_products_categories_mm');
        $result = $productQueryBuilder
            ->select(...array_keys($categoryRelations[0]))
            ->from('tx_commerce_products_categories_mm')
            ->orderBy('uid_local')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            array_values($categoryRelations),
            $result
        );
    }

    /**
     * @test
     */
    public function makeProduct()
    {
        // prepare fixture data
        $this->importDataSet($this->fixturePath . 'tx_commerce_categories.xml');

        // invoke protected method
        $class = new \ReflectionClass(FolderRepository::class);
        $method = $class->getMethod('makeProduct');
        $method->setAccessible(true);

        $repository = new FolderRepository();
        $method->invoke($repository, 1, 'DELIVERY', [
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'pid' => 2
        ]);

        // check for created products
        $products = [
            'DELIVERY' => [
                'uid' => 1,
                'pid' => 2,
                'uname' => 'DELIVERY',
                'title' => 'DELIVERY',
                'categories' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $result = $productQueryBuilder
            ->select(...array_keys($products['DELIVERY']))
            ->from('tx_commerce_products')
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            array_values($products),
            $result
        );

        // check for created relations
        $categoryRelations = [
            0 => [
                'uid_local' => 1,
                'uid_foreign' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_products_categories_mm');
        $result = $productQueryBuilder
            ->select(...array_keys($categoryRelations[0]))
            ->from('tx_commerce_products_categories_mm')
            ->orderBy('uid_local')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            array_values($categoryRelations),
            $result
        );
    }

    /**
     * @test
     */
    public function checkProduct()
    {
        $this->importDataSet($this->fixturePath . 'tx_commerce_products.xml');
        $this->importDataSet($this->fixturePath . 'tx_commerce_categories.xml');
        $this->importDataSet($this->fixturePath . 'tx_commerce_products_categories_mm.xml');

        // invoke protected method
        $class = new \ReflectionClass(FolderRepository::class);
        $method = $class->getMethod('checkProduct');
        $method->setAccessible(true);

        $repository = new FolderRepository();
        $result = $method->invoke($repository, 1, 'product_with_uname');

        $this->assertEquals(
            13,
            $result
        );
    }

    /**
     * @test
     */
    public function makeArticle()
    {
        $articleUid = FolderRepository::makeArticle(
            1,
            'sysdelivery',
            [ 'type' => 3 ],
            [
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'crdate' => $GLOBALS['EXEC_TIME'],
                'pid' => 2
            ]
        );

        // check for created articles
        $articles = [
            'DELIVERY' => [
                'uid' => $articleUid,
                'classname' => 'sysdelivery',
                'title' => 'sysdelivery',
                'article_type_uid' => 3,
                'uid_product' => 1,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_articles');
        $result = $productQueryBuilder
            ->select(...array_keys($articles['DELIVERY']))
            ->from('tx_commerce_articles')
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            array_values($articles),
            $result
        );

        // check for created prices
        $prices = [
            0 => [
                'uid' => 1,
                'uid_article' => $articleUid,
            ]
        ];

        $productQueryBuilder = $this->getQueryBuilderForTable('tx_commerce_article_prices');
        $result = $productQueryBuilder
            ->select(...array_keys($prices[0]))
            ->from('tx_commerce_article_prices')
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $this->assertEquals(
            $prices,
            $result
        );
    }
}
