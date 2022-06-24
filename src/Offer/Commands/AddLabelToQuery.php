<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;
use CultuurNet\UDB3\Label;

class AddLabelToQuery implements AsyncCommand
{
    use AsyncCommandTrait;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var Label
     */
    protected $label;

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

    /**
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }
}
