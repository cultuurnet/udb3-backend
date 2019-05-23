<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use \InvalidArgumentException;

class TitleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_be_at_least_one_character_long()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        new Title('');
    }
}
