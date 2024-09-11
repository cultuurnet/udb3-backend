<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

class Category
{
    private CategoryID $id;

    private ?CategoryLabel $label;

    protected ?CategoryDomain $domain;

    public function __construct(
        CategoryID $id,
        CategoryLabel $label = null,
        CategoryDomain $domain = null
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->domain = $domain;
    }

    public function getId(): CategoryID
    {
        return $this->id;
    }

    public function getLabel(): ?CategoryLabel
    {
        return $this->label;
    }

    public function getDomain(): ?CategoryDomain
    {
        return $this->domain;
    }
}
