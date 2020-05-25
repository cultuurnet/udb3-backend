<?php

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

    /**
     * @param $itemId
     *  The id of the item that is targeted by the command.
     *
     * @param Label $label
     *  The label that is used in the command.
     */
    public function __construct($itemId, Label $label)
    {
        parent::__construct($itemId);
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getNames()
    {
        return [
            new StringLiteral((string)$this->label),
        ];
    }
}
