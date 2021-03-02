<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Security\LabelSecurityInterface;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractLabelCommand extends AbstractCommand implements LabelSecurityInterface
{
    /**
     * @var Label
     */
    protected $label;

    public function __construct(string $itemId, Label $label)
    {
        parent::__construct($itemId);
        $this->label = $label;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getNames(): array
    {
        return [
            new StringLiteral((string)$this->label),
        ];
    }
}
