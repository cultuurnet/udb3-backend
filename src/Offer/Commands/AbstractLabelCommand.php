<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;
use CultuurNet\UDB3\StringLiteral;

abstract class AbstractLabelCommand extends AbstractCommand implements AuthorizableLabelCommand
{
    /**
     * @var LegacyLabel
     */
    protected $label;

    public function __construct(string $itemId, LegacyLabel $label)
    {
        parent::__construct($itemId);
        $this->label = $label;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getLabel(): LegacyLabel
    {
        return $this->label;
    }

    public function getLabelNames(): array
    {
        return [
            new StringLiteral($this->label->getName()->toString()),
        ];
    }
}
