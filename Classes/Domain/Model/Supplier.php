<?php
namespace CommerceTeam\Commerce\Domain\Model;

class Supplier extends AbstractEntity
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var array
     */
    protected $logo;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getLogo()
    {
        $this->initializeFileReferences($this->logo, 'logo');

        return $this->logo;
    }
}
