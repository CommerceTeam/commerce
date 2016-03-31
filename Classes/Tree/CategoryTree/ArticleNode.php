<?php
namespace CommerceTeam\Commerce\Tree\CategoryTree;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Node designated for the page tree
 */
class ArticleNode extends CategoryNode implements NodeInterface
{
    /**
     * Indicator if the node can have children's
     *
     * @var boolean
     */
    protected $allowChildren = false;

    /**
     * @var int
     */
    protected $product = 0;

    /**
     * @var int
     */
    protected $category = 0;

    /**
     * Getter
     *
     * @return int
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Setter
     *
     * @param int $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * Getter
     *
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Setter
     *
     * @param int $category
     */
    public function setCategory($category)
    {
        $this->category = (int) $category;
    }

    /**
     * Returns the calculated id representation of this node
     *
     * @param string $prefix Defaults to 'a'
     * @return string
     */
    public function calculateNodeId($prefix = 'pa')
    {
        return $prefix . dechex($this->getId());
    }

    /**
     * @return string
     */
    public function getJumpUrl()
    {
        $params = '&edit[' . $this->getType() . '][' . $this->getId() . ']=edit';
        $id = FolderRepository::initFolders('Products', FolderRepository::initFolders());
// @todo change returnUrl
        return BackendUtility::getModuleUrl('record_edit') . '&id=' . $id . $params . '&returnUrl=T3_THIS_LOCATION';
    }

    /**
     * Returns the node in an array representation that can be used for serialization
     *
     * @param bool $addChildNodes
     * @return array
     */
    public function toArray($addChildNodes = true)
    {
        $arrayRepresentation = parent::toArray();
        $arrayRepresentation['nodeData']['product'] = $this->getProduct();
        $arrayRepresentation['nodeData']['category'] = $this->getCategory();
        return $arrayRepresentation;
    }

    /**
     * Sets data of the node by a given data array
     *
     * @param array $data
     * @return void
     */
    public function dataFromArray($data)
    {
        parent::dataFromArray($data);
        $this->setProduct($data['product']);
        $this->setCategory($data['category']);
    }
}
