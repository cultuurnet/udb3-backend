<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;

class AddLabelToQuery
{
    /**
     * @var string
     */
    protected $query;

    protected Label $label;

    public function __construct($query, Label $label)
    {
        $this->query = $query;
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }
}
