<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

class Category
{
    /**
     * @var CategoryID
     */
    private $id;

    /**
     * @var CategoryLabel|null
     */
    private $label;

    /**
     * @var CategoryDomain|null
     */
    protected $domain;


    public function __construct(
        CategoryID $id,
        CategoryLabel $label = null,
        CategoryDomain $domain = null
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->domain = $domain;
    }

    /**
     * @return CategoryID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CategoryLabel|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return CategoryDomain|null
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
