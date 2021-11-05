<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

class RemoveLabelTest extends TestCase
{
    /**
     * @test
     */
    public function it_derives_from_abstract_label_command()
    {
        $removeLabel = new RemoveLabel('organizerId', new Label(new LabelName('foo')));

        $this->assertInstanceOf(AbstractLabelCommand::class, $removeLabel);
    }
}
