<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;

class AddLabelToQuery implements AsyncCommand
{
    use AsyncCommandTrait;

    protected string $query;

    protected Label $label;

    public function __construct(string $query, Label $label)
    {
        $this->query = $query;
        $this->label = $label;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }
}
